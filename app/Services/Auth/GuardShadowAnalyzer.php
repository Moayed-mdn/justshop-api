<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Session\GuardShadowSummary;
use App\DTOs\Auth\Session\SessionOwnershipContext;

class GuardShadowAnalyzer
{
    public function __construct(
        private readonly MerchantGuardShadowResolver $merchantResolver,
        private readonly CustomerGuardShadowResolver $customerResolver,
    ) {}

    public function analyze(SessionOwnershipContext $context): GuardShadowSummary
    {
        $merchant = $this->merchantResolver->resolve($context);
        $customer = $this->customerResolver->resolve($context);

        $ambiguousOwnershipPath = $merchant->wouldResolve && $customer->wouldResolve;
        $futureGuardHint = $ambiguousOwnershipPath
            ? 'ambiguous_guard'
            : ($merchant->wouldResolve ? 'merchant_guard' : ($customer->wouldResolve ? 'customer_guard' : 'shared_guard'));

        $guardMismatchAnomaly = ($context->authDomain === 'customer' && $merchant->wouldResolve)
            || (in_array($context->authDomain, ['merchant', 'platform'], true) && $customer->wouldResolve);

        return new GuardShadowSummary(
            merchant: $merchant,
            customer: $customer,
            futureGuardHint: $futureGuardHint,
            ambiguousOwnershipPath: $ambiguousOwnershipPath,
            guardMismatchAnomaly: $guardMismatchAnomaly,
        );
    }
}
