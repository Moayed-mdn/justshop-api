<?php

declare(strict_types=1);

namespace App\Actions\PaymentMethod;

use App\DTOs\PaymentMethod\DeletePaymentMethodDTO;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;

class DeletePaymentMethodAction
{
    public function __construct(
        private PaymentMethodService $paymentMethodService,
    ) {}

    public function execute(DeletePaymentMethodDTO $dto): void
    {
        // Wave 2 Remediation: Authorization removed from Action
        // Authorization now explicitly owned by PaymentMethodPolicy::delete() in controller
        // This action is now orchestration-focused only
        
        $paymentMethod = PaymentMethod::findOrFail($dto->paymentMethodId);

        $this->paymentMethodService->deletePaymentMethod($paymentMethod);
    }
}
