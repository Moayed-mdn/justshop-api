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

        // Record payment transaction if charge exists
        if (isset($invoiceData['charge']) && $invoiceData['charge']) {
            // Get charge data from Stripe
            $chargeId = $invoiceData['charge'];
            
            $this->recordPaymentTransaction->execute(
                RecordPaymentTransactionDTO::fromStripeCharge(
                    [
                        'id' => $chargeId,
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

        // If subscription was in troubled state, reactivate it
        $troubledStates = [
            SubscriptionStatusEnum::PAST_DUE->value,
            SubscriptionStatusEnum::GRACE_PERIOD->value,
        ];

        if (in_array($subscription->status, $troubledStates, true)) {
            $this->reactivateSubscription->execute(
                ReactivateSubscriptionDTO::fromWebhook(
                    subscriptionId: $subscription->id,
                    reason: 'Payment succeeded after ' . $subscription->status,
                )
            );

            Log::channel('billing')->info('invoice.payment_succeeded.subscription_reactivated', [
                'subscription_id' => $subscription->id,
                'previous_status' => $subscription->status,
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
