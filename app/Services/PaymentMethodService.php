<?php

namespace App\Services;

use App\DTOs\PaymentMethod\StorePaymentMethodDTO;
use App\Models\PaymentMethod;
use App\Repositories\PaymentMethod\PaymentMethodRepository;
use Illuminate\Database\Eloquent\Collection;

class PaymentMethodService
{
    public function __construct(
        private PaymentMethodRepository $paymentMethodRepository
    ) {}

    public function getUserPaymentMethods(int $userId): Collection
    {
        return $this->paymentMethodRepository->getUserPaymentMethods($userId);
    }

    public function createPaymentMethod(StorePaymentMethodDTO $dto): PaymentMethod
    {
        if ($dto->isDefault) {
            $this->paymentMethodRepository->unsetDefault($dto->userId);
        }

        return $this->paymentMethodRepository->create([
            'user_id' => $dto->userId,
            'provider' => $dto->provider,
            'payment_method_id' => $dto->paymentMethodId,
            'brand' => $dto->brand,
            'last_four' => $dto->lastFour,
            'exp_month' => $dto->expMonth,
            'exp_year' => $dto->expYear,
            'is_default' => $dto->isDefault,
        ]);
    }

    public function deletePaymentMethod(PaymentMethod $paymentMethod): void
    {
        $wasDefault = $paymentMethod->is_default;
        $userId = $paymentMethod->user_id;

        $this->paymentMethodRepository->delete($paymentMethod);

        if ($wasDefault) {
            $stillHasDefault = $this->paymentMethodRepository->getDefault($userId);

            if (!$stillHasDefault) {
                $nextInLine = $this->paymentMethodRepository->getUserPaymentMethods($userId)->first();

                if ($nextInLine) {
                    $this->paymentMethodRepository->setAsDefault($userId, $nextInLine->id);
                }
            }
        }
    }

    public function setAsDefault(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethodRepository->setAsDefault(
            $paymentMethod->user_id,
            $paymentMethod->id
        );
    }
}
