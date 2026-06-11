<?php

namespace App\DTOs\Billing;

use Illuminate\Http\Request;

class CreateBillingAccountDTO
{
    public function __construct(
        public int $ownerUserId,
        public string $billingEmail,
        public ?string $legalName = null,
        public ?string $countryCode = null,
        public string $defaultCurrency = 'USD',
    ) {}

    public static function fromRequest(Request $request, int $ownerUserId): self
    {
        return new self(
            ownerUserId: $ownerUserId,
            billingEmail: $request->string('billing_email', '')->toString(),
            legalName: $request->string('legal_name')->toString() ?: null,
            countryCode: $request->string('country_code')->toString() ?: null,
            defaultCurrency: $request->string('default_currency', 'USD')->toString(),
        );
    }
}
