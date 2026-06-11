<?php

namespace App\Repositories\Billing;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceRepository
{
    /**
     * Find invoice by provider invoice ID.
     */
    public function findByProviderInvoiceId(string $provider, string $providerInvoiceId): ?Invoice
    {
        return Invoice::where('provider', $provider)
            ->where('provider_invoice_id', $providerInvoiceId)
            ->first();
    }

    /**
     * Get paginated invoices for a billing account.
     */
    public function getPaginatedForAccount(int $billingAccountId, int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::where('billing_account_id', $billingAccountId)
            ->with(['subscription', 'lineItems'])
            ->orderBy('issued_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get all invoices for a billing account.
     */
    public function getAllForAccount(int $billingAccountId): Collection
    {
        return Invoice::where('billing_account_id', $billingAccountId)
            ->with(['subscription', 'lineItems'])
            ->orderBy('issued_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find invoice by ID for a specific billing account.
     */
    public function findForAccount(int $id, int $billingAccountId): ?Invoice
    {
        return Invoice::where('id', $id)
            ->where('billing_account_id', $billingAccountId)
            ->with(['subscription', 'lineItems', 'paymentTransactions'])
            ->first();
    }

    /**
     * Find invoice or fail.
     */
    public function findOrFail(int $id): Invoice
    {
        return Invoice::with(['subscription', 'lineItems', 'paymentTransactions'])->findOrFail($id);
    }
}
