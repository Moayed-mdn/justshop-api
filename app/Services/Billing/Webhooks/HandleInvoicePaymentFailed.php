<?php

namespace App\Services\Billing\Webhooks;

use App\Actions\Billing\RecordInvoiceAction;
use App\Actions\Billing\RecordPaymentTransactionAction;
use App\Actions\Subscription\MarkPastDueAction;
use App\Actions\Subscription\EnterGracePeriodAction;
use App\DTOs\Billing\RecordInvoiceDTO;
use App\DTOs\Billing\RecordPaymentTransactionDTO;
use App\DTOs\Subscription\MarkPastDueDTO;
use App\DTOs\Subscription\EnterGracePeriodDTO;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class HandleInvoicePaymentFailed
{
    public function __construct(
        private readonly MarkPastDueAction $markPastDue,
        private readonly EnterGracePeriodAction $enterGracePeriod,
        private readonly RecordInvoiceAction $recordInvoice,
        private readonly RecordPaymentTransactionAction $recordPaymentTransaction,
    ) {}

    /**
     * Handle invoice.payment_failed webhook.
     * 
     * 1. Records/updates invoice
     * 2. Records failed payment transaction
     * 3. Marks subscription as past_due or enters grace period
     */
    public function handle(array $event): void
    {
        $invoiceData = $event['data']['object'];
        $stripeSubscriptionId = $invoiceData['subscription'] ?? null;

        if (! $stripeSubscriptionId) {
            Log::channel('billing')->warning('invoice.payment_failed.no_subscription', [
                'invoice_id' => $invoiceData['id'],
            ]);
            return;
        }

        $subscription = Subscription::where('provider_subscription_id', $stripeSubscriptionId)->first();

        if (! $subscription) {
            Log::channel('billing')->warning('invoice.payment_failed.subscription_not_found', [
                'provider_subscription_id' => $stripeSubscriptionId,
                'invoice_id' => $invoiceData['id'],
            ]);
            return;
        }

        $attemptCount = $invoiceData['attempt_count'] ?? 1;
        $nextAttempt = $invoiceData['next_payment_attempt'] ?? null;

        Log::channel('billing')->warning('invoice.payment_failed.processing', [
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoiceData['id'],
            'attempt_count' => $attemptCount,
            'next_payment_attempt' => $nextAttempt,
            'failure_code' => $invoiceData['charge']['failure_code'] ?? null,
            'failure_message' => $invoiceData['charge']['failure_message'] ?? null,
        ]);

        // Record invoice
        $invoice = $this->recordInvoice->execute(
            RecordInvoiceDTO::fromStripeInvoice(
                $invoiceData,
                $subscription->billing_account_id,
                $subscription->id
            )
        );

        // Record failed payment transaction if charge exists
        if (isset($invoiceData['charge']) && $invoiceData['charge']) {
            $chargeId = $invoiceData['charge'];
            
            $this->recordPaymentTransaction->execute(
                RecordPaymentTransactionDTO::fromStripeCharge(
                    [
                        'id' => $chargeId,
                        'amount' => $invoiceData['amount_due'],
                        'currency' => $invoiceData['currency'],
                        'status' => 'failed',
                        'created' => time(),
                        'refunded' => false,
                        'failure_code' => $invoiceData['charge']['failure_code'] ?? null,
                        'failure_message' => $invoiceData['charge']['failure_message'] ?? null,
                    ],
                    $subscription->billing_account_id,
                    $invoice->id,
                    $subscription->id
                )
            );
        }

        // Stripe Smart Retries: typically 4 attempts over 3-4 days
        $retriesExhausted = $attemptCount >= 4 || $nextAttempt === null;

        if ($retriesExhausted) {
            // Retries exhausted → Enter 72-hour grace period
            $this->enterGracePeriod->execute(
                EnterGracePeriodDTO::fromSubscription(
                    subscriptionId: $subscription->id,
                    gracePeriodDays: 3,
                    reason: "Payment retries exhausted ({$attemptCount} attempts failed)",
                )
            );

            Log::channel('billing')->error('invoice.payment_failed.grace_period_entered', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoiceData['id'],
                'attempt_count' => $attemptCount,
            ]);
        } else {
            // Still retrying → Mark as past_due
            $this->markPastDue->execute(
                MarkPastDueDTO::fromWebhook(
                    subscriptionId: $subscription->id,
                    reason: "Payment failed (attempt {$attemptCount}), retrying",
                    providerStatus: 'past_due',
                )
            );

            Log::channel('billing')->warning('invoice.payment_failed.marked_past_due', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoiceData['id'],
                'attempt_count' => $attemptCount,
                'next_attempt' => $nextAttempt,
            ]);
        }
    }
}
