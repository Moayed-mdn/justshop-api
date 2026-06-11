<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Billing\CreatePortalSessionDTO;
use App\Repositories\Billing\BillingAccountRepository;
use App\Models\BillingCustomer;
use Illuminate\Support\Facades\Log;

class CreateBillingPortalSessionAction
{
    public function __construct(
        private BillingProviderInterface $billingProvider,
        private BillingAccountRepository $billingAccountRepository,
        private EnsureBillingCustomerAction $ensureBillingCustomer,
    ) {}

    /**
     * Create a Stripe Billing Portal session for customer self-service.
     * 
     * Allows merchant to:
     * - Update payment method
     * - View invoices
     * - Download receipts
     * - Cancel subscription
     */
    public function execute(CreatePortalSessionDTO $dto): array
    {
        $billingAccount = $this->billingAccountRepository->findByIdOrFail($dto->billingAccountId);

        // Ensure billing customer exists
        $billingCustomer = BillingCustomer::where('billing_account_id', $billingAccount->id)
            ->where('provider', 'stripe')
            ->first();

        if (!$billingCustomer) {
            $billingCustomer = $this->ensureBillingCustomer->execute($billingAccount);
        }

        // Create portal session
        $session = $this->billingProvider->createPortalSession(
            $billingCustomer,
            $dto->returnUrl
        );

        Log::channel('billing')->info('billing_portal.session_created', [
            'billing_account_id' => $dto->billingAccountId,
            'session_id' => $session['session_id'],
        ]);

        return $session;
    }
}
