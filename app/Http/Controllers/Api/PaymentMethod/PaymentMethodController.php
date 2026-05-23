<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PaymentMethod;

use App\Actions\PaymentMethod\DeletePaymentMethodAction;
use App\Actions\PaymentMethod\ListPaymentMethodsAction;
use App\Actions\PaymentMethod\SetDefaultPaymentMethodAction;
use App\Actions\PaymentMethod\StorePaymentMethodAction;
use App\DTOs\PaymentMethod\DeletePaymentMethodDTO;
use App\DTOs\PaymentMethod\ListPaymentMethodsDTO;
use App\DTOs\PaymentMethod\SetDefaultPaymentMethodDTO;
use App\DTOs\PaymentMethod\StorePaymentMethodDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\DeletePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\ListPaymentMethodsRequest;
use App\Http\Requests\PaymentMethod\SetDefaultPaymentMethodRequest;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function __construct(
        private ListPaymentMethodsAction $listPaymentMethodsAction,
        private StorePaymentMethodAction $storePaymentMethodAction,
        private DeletePaymentMethodAction $deletePaymentMethodAction,
        private SetDefaultPaymentMethodAction $setDefaultPaymentMethodAction,
    ) {}

    public function index(ListPaymentMethodsRequest $request): JsonResponse
    {
        $paymentMethods = $this->listPaymentMethodsAction->execute(
            ListPaymentMethodsDTO::fromRequest($request)
        );

        return $this->success(
            PaymentMethodResource::collection($paymentMethods),
            'Payment methods retrieved successfully'
        );
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $paymentMethod = $this->storePaymentMethodAction->execute(
            StorePaymentMethodDTO::fromRequest($request)
        );

        return $this->success(
            new PaymentMethodResource($paymentMethod),
            'Payment method added successfully',
            201
        );
    }

    public function destroy(DeletePaymentMethodRequest $request): JsonResponse
    {
        // Wave 2 Remediation: Fetch payment method first to enable explicit policy authorization
        $paymentMethod = \App\Models\PaymentMethod::findOrFail($request->integer('payment_method_id'));

        // Wave 2 Remediation: Explicit policy authorization moved from Action to Controller
        // Authorization now owned by PaymentMethodPolicy::delete()
        $this->authorize('delete', $paymentMethod);

        $this->deletePaymentMethodAction->execute(
            DeletePaymentMethodDTO::fromRequest($request)
        );

        return $this->success(null, 'Payment method deleted successfully');
    }

    public function setDefault(SetDefaultPaymentMethodRequest $request): JsonResponse
    {
        // Wave 2 Remediation: Fetch payment method first to enable explicit policy authorization
        $paymentMethod = \App\Models\PaymentMethod::findOrFail($request->integer('payment_method_id'));

        // Wave 2 Remediation: Explicit policy authorization moved from Action to Controller
        // Authorization now owned by PaymentMethodPolicy::update()
        $this->authorize('update', $paymentMethod);

        $this->setDefaultPaymentMethodAction->execute(
            SetDefaultPaymentMethodDTO::fromRequest($request)
        );

        return $this->success(null, 'Payment method set as default successfully');
    }
}
