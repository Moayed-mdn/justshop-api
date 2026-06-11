<?php

namespace App\Repositories\Billing;

use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Collection;

class PaymentTransactionRepository
{
    /**
     * Find payment transaction by provider transaction ID.
     */
    public function findByProviderTransactionId(string $provider, string $providerTransactionId): ?PaymentTransaction
    {
        return PaymentTransaction::where('provider', $provider)
            ->where('provider_transaction_id', $providerTransactionId)
            ->first();
    }

    /**
     * Get all transactions for a billing account.
     */
    public function getAllForAccount(int $billingAccountId): Collection
    {
        return PaymentTransaction::where('billing_account_id', $billingAccountId)
            ->with(['invoice'])
            ->orderBy('processed_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all transactions for an invoice.
     */
    public function getAllForInvoice(int $invoiceId): Collection
    {
        return PaymentTransaction::where('invoice_id', $invoiceId)
            ->orderBy('processed_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
