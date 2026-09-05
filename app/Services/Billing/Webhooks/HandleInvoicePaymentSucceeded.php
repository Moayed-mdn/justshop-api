<?php

namespace App\Services\Billing\Webhooks;

use App\Actions\Billing\RecordInvoiceAction;
use App\Actions\Billing\RecordPaymentTransactionAction;
use App\Actions\Subscription\ReactivateSubscriptionAction;
use App\DTOs\Billing\RecordInvoiceDTO;
use App\DTOs\Billing\RecordPaymentTransactionDTO;
use App\DTOs\Subscription\ReactivateSubscriptionDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class HandleInvoicePaymentSucceeded
{
    public function __construct(
        private readonly ReactivateSubscriptionAction $reactivateSubscription,
        private readonly RecordInvoiceAction $recordInvoice,
        private readonly RecordPaymentTransactionAction $recordPaymentTransaction,
    ) {}

    /**
     * Handle invoice.payment_succeeded webhook.
     * 
     * 1. Records/updates invoice
     * 2. Records payment transaction
     * 3. If subscription was past_due or grace_period, reactivate it
     */
    public function handle(array $event): void
    {
        $invoiceData = $event['data']['object'];
        $stripeSubscriptionId = $invoiceData['subscription'] ?? null;
        $subscription = null;

        if ($stripeSubscriptionId) {
            $subscription = Subscription::where('provider_subscription_id', $stripeSubscriptionId)->first();
        }

        // Fallback: try to find subscription via line item metadata
        if (!$subscription) {
            $billingAccountId = $this->extractBillingAccountIdFromLines($invoiceData['lines']['data'] ?? []);
            if ($billingAccountId) {
                $subscription = Subscription::where('billing_account_id', $billingAccountId)
                    ->whereIn('status', ['active', 'trialing', 'incomplete'])
                    ->latest()
                    ->first();
            }
        }

        if (! $subscription) {
            Log::channel('billing')->warning('invoice.payment_succeeded.subscription_not_found', [
                'provider_subscription_id' => $stripeSubscriptionId,
                'invoice_id' => $invoiceData['id'],
            ]);
            return;
        }

        Log::channel('billing')->info('invoice.payment_succeeded', [
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoiceData['id'],
            'amount_paid' => $invoiceData['amount_paid'],
            'currency' => $invoiceData['currency'],
            'current_status' => $subscription->status,
        ]);

        // Record invoice
        $invoice = $this->recordInvoice->execute(
            RecordInvoiceDTO::fromStripeInvoice(
                $invoiceData,
                $subscription->billing_account_id,
                $subscription->id
            )
        );

        // Record payment transaction
        // Stripe invoices can have either 'charge' or 'payment_intent' depending on
        // the payment flow. For automatic collection (subscriptions), payment_intent
        // is usually present. For manual/send_invoice, charge might be present instead.
        $paymentId = $invoiceData['payment_intent'] ?? $invoiceData['charge'] ?? null;
        
        if ($paymentId && $invoiceData['amount_paid'] > 0) {
            // Determine if this is a payment_intent or charge
            $isPaymentIntent = isset($invoiceData['payment_intent']);
            
            if ($isPaymentIntent) {
                // Fetch payment intent details from Stripe to get the actual charge
                try {
                    $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentId);
                    
                    // Get the charge from payment intent
                    if (isset($paymentIntent->charges->data[0])) {
                        $charge = $paymentIntent->charges->data[0];
                        
                        $this->recordPaymentTransaction->execute(
                            RecordPaymentTransactionDTO::fromStripeCharge(
                                [
                                    'id' => $charge->id,
                                    'amount' => $charge->amount,
                                    'currency' => $charge->currency,
                                    'status' => $charge->status,
                                    'created' => $charge->created,
                                    'refunded' => $charge->refunded,
                                    'payment_method' => $charge->payment_method ?? null,
                                    'failure_code' => $charge->failure_code ?? null,
                                    'failure_message' => $charge->failure_message ?? null,
                                ],
                                $subscription->billing_account_id,
                                $invoice->id,
                                $subscription->id
                            )
                        );
                    }
                } catch (\Exception $e) {
                    Log::channel('billing')->warning('invoice.payment_succeeded.payment_intent_fetch_failed', [
                        'payment_intent_id' => $paymentId,
                        'invoice_id' => $invoiceData['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Direct charge reference
                $this->recordPaymentTransaction->execute(
                    RecordPaymentTransactionDTO::fromStripeCharge(
                        [
                            'id' => $paymentId,
                            'amount' => $invoiceData['amount_paid'],
                            'currency' => $invoiceData['currency'],
                            'status' => 'succeeded',
                            'created' => $invoiceData['status_transitions']['paid_at'] ?? time(),
                            'refunded' => false,
                        ],
                        $subscription->billing_account_id,
                        $invoice->id,
                        $subscription->id
                    )
                );
            }
        } elseif ($invoiceData['amount_paid'] === 0) {
            Log::channel('billing')->info('invoice.payment_succeeded.zero_amount', [
                'invoice_id' => $invoiceData['id'],
                'subscription_id' => $subscription->id,
                'reason' => 'Trial or fully discounted invoice - no payment transaction needed',
            ]);
        } else {
            Log::channel('billing')->warning('invoice.payment_succeeded.no_payment_reference', [
                'invoice_id' => $invoiceData['id'],
                'subscription_id' => $subscription->id,
                'amount_paid' => $invoiceData['amount_paid'],
            ]);
        }

        // If subscription was in troubled state, reactivate it
        $troubledStates = [
            SubscriptionStatusEnum::PAST_DUE,
            SubscriptionStatusEnum::GRACE_PERIOD,
        ];

        if (in_array($subscription->status, $troubledStates, true)) {
            $this->reactivateSubscription->execute(
                ReactivateSubscriptionDTO::fromWebhook(
                    subscriptionId: $subscription->id,
                    reason: 'Payment succeeded after ' . $subscription->status->value,
                )
            );

            Log::channel('billing')->info('invoice.payment_succeeded.subscription_reactivated', [
                'subscription_id' => $subscription->id,
                'previous_status' => $subscription->status->value,
                'invoice_id' => $invoiceData['id'],
            ]);
        }
    }

    private function extractBillingAccountIdFromLines(array $lines): ?int
    {
        foreach ($lines as $line) {
            if (isset($line['metadata']['billing_account_id'])) {
                return (int) $line['metadata']['billing_account_id'];
            }
        }
        return null;
    }
}
