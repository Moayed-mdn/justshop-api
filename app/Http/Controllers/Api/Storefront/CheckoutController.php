<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Actions\Payment\CreateCheckoutSessionAction;
use App\Actions\Payment\GetCheckoutStatusAction;
use App\DTOs\Payment\CreateCheckoutDTO;
use App\DTOs\Payment\GetCheckoutStatusDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CreateCheckoutRequest;
use App\Http\Requests\Checkout\GetCheckoutStatusRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private CreateCheckoutSessionAction $createCheckoutSessionAction,
        private GetCheckoutStatusAction $getCheckoutStatusAction,
    ) {}

    public function initiate(CreateCheckoutRequest $request, int $store): JsonResponse
    {
        $result = $this->createCheckoutSessionAction->execute(
            CreateCheckoutDTO::fromRequest($request, $store)
        );

        return $this->success($result);
    }

    public function confirm(Request $request, int $store): JsonResponse
    {
        // Confirm payment logic here
        return $this->success(null, 'Payment confirmed');
    }

    public function status(GetCheckoutStatusRequest $request, string $sessionId): JsonResponse
    {
        $data = $this->getCheckoutStatusAction->execute(
            GetCheckoutStatusDTO::fromRequest($request, $sessionId)
        );

        return $this->success($data);
    }
}
