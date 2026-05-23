<?php

declare(strict_types=1);

namespace App\Actions\PaymentMethod;

use App\DTOs\PaymentMethod\SetDefaultPaymentMethodDTO;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;

class SetDefaultPaymentMethodAction
{
    public function __construct(
        private PaymentMethodService $paymentMethodService,
    ) {}

    public function execute(SetDefaultPaymentMethodDTO $dto): void
    {
        // Wave 2 Remediation: Authorization removed from Action
        // Authorization now explicitly owned by PaymentMethodPolicy::update() in controller
        // This action is now orchestration-focused only
        
        $paymentMethod = PaymentMethod::findOrFail($dto->paymentMethodId);

        $this->paymentMethodService->setAsDefault($paymentMethod);
    }
}
