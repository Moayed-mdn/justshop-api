<?php

namespace App\Actions\Billing;

use App\DTOs\Billing\CreateBillingAccountDTO;
use App\Models\BillingAccount;
use App\Models\User;
use App\Repositories\Billing\BillingAccountRepository;

class CreateBillingAccountAction
{
    public function __construct(
        private BillingAccountRepository $billingAccountRepository
    ) {}

    /**
     * Create or retrieve billing account for a user.
     */
    public function execute(CreateBillingAccountDTO $dto): BillingAccount
    {
        $user = User::findOrFail($dto->ownerUserId);

        return $this->billingAccountRepository->getOrCreate(
            $user,
            [
                'billing_email' => $dto->billingEmail ?: $user->email,
                'legal_name' => $dto->legalName,
                'country_code' => $dto->countryCode,
                'default_currency' => $dto->defaultCurrency,
            ]
        );
    }
}
