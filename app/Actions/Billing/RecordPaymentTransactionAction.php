<?php

namespace App\Actions\Billing;

use App\DTOs\Billing\RecordPaymentTransactionDTO;
use App\Models\PaymentTransaction;
use App\Repositories\Billing\PaymentTransactionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordPaymentTransactionAction
{
    public function __construct(
        private PaymentTransactionRepository $paymentTransactionRepository,
    ) {}

    /**
     * Record a payment transaction from provider webhook.
     * 
     * Idempotent based on provider_transaction_id.
     */
    public function execute(RecordPaymentTransactionDTO $dto): PaymentTransaction
    {
        return DB::transaction(function () use ($dto) {
            // Check if transaction already exists
            $transaction = $this->paymentTransactionRepository->findByProviderTransactionId(
                $dto->provider,
                $dto->providerTransactionId
            );

            if ($transaction) {
                // Update existing transaction
                $transaction->update([
                    'status' => $dto->status,
                    'failure_code' => $dto->failureCode,
                    'failure_message' => $dto->failureMessage,
                    'processed_at' => $dto->processedAt ?? $transaction->processed_at,
                    'metadata' => $dto->metadata ?? $transaction->metadata,
                ]);
            } else {
                // Create new transaction
                $transaction = PaymentTransaction::create([
                    'billing_account_id' => $dto->billingAccountId,
                    'invoice_id' => $dto->invoiceId,
                    'subscription_id' => $dto->subscriptionId,
                    'provider' => $dto->provider,
                    'provider_transaction_id' => $dto->providerTransactionId,
                    'provider_payment_method_id' => $dto->providerPaymentMethodId,
                    'type' => $dto->type,
                    'status' => $dto->status,
                    'currency' => $dto->currency,
                    'amount_cents' => $dto->amountCents,
                    'failure_code' => $dto->failureCode,
                    'failure_message' => $dto->failureMessage,
                    'processed_at' => $dto->processedAt,
                    'metadata' => $dto->metadata,
                ]);
            }

            Log::channel('billing')->info('payment_transaction.recorded', [
                'transaction_id' => $transaction->id,
                'provider_transaction_id' => $dto->providerTransactionId,
                'type' => $dto->type,
                'status' => $dto->status,
                'amount_cents' => $dto->amountCents,
            ]);

            return $transaction;
        });
    }
}
