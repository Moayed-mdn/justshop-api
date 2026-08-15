<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Actions\Platform\Orders\CancelPlatformOrderAction;
use App\Actions\Platform\Orders\GetPlatformOrderAction;
use App\Actions\Platform\Orders\GetPlatformOrdersAction;
use App\Actions\Platform\Orders\RefundPlatformOrderAction;
use App\Actions\Platform\Orders\UpdatePlatformOrderStatusAction;
use App\DTOs\Platform\Orders\CancelPlatformOrderDTO;
use App\DTOs\Platform\Orders\GetPlatformOrderDTO;
use App\DTOs\Platform\Orders\GetPlatformOrdersDTO;
use App\DTOs\Platform\Orders\RefundPlatformOrderDTO;
use App\DTOs\Platform\Orders\UpdatePlatformOrderStatusDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Orders\GetPlatformOrdersRequest;
use App\Http\Requests\Platform\Orders\RefundPlatformOrderRequest;
use App\Http\Requests\Platform\Orders\UpdatePlatformOrderStatusRequest;
use App\Http\Resources\Platform\PlatformOrderResource;
use App\Policies\PlatformOrderPolicy;
use Illuminate\Http\JsonResponse;

/**
 * Platform Order Controller
 * 
 * Wave 6: Platform authority domain controller for orders.
 * Platform authority is INDEPENDENT from merchant authority.
 * 
 * Platform actors can intentionally access orders across all stores.
 * This is a platform-level capability, not a merchant capability.
 */
class PlatformOrderController extends Controller
{
    public function __construct(
        private readonly GetPlatformOrdersAction $getPlatformOrdersAction,
        private readonly GetPlatformOrderAction $getPlatformOrderAction,
        private readonly UpdatePlatformOrderStatusAction $updatePlatformOrderStatusAction,
        private readonly CancelPlatformOrderAction $cancelPlatformOrderAction,
        private readonly RefundPlatformOrderAction $refundPlatformOrderAction,
    ) {}

    /**
     * List platform orders across all stores.
     * 
     * This intentionally operates at platform scope.
     * Platform actors with platform.order.view can see orders from any store.
     */
    public function index(GetPlatformOrdersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PlatformOrderPolicy::class);

        $orders = $this->getPlatformOrdersAction->execute(
            GetPlatformOrdersDTO::fromRequest($request)
        );

        return $this->paginated($orders, PlatformOrderResource::collection($orders));
    }

    /**
     * Show a specific platform order.
     * 
     * Platform actors can view an order from any store.
     * This is intentionally cross-store.
     */
    public function show(int $order): JsonResponse
    {
        $orderModel = $this->getPlatformOrderAction->execute(
            new GetPlatformOrderDTO(orderId: $order)
        );

        $this->authorize('view', [PlatformOrderPolicy::class, $orderModel]);

        return $this->success(new PlatformOrderResource($orderModel));
    }

    /**
     * Update order status at platform level.
     * 
     * Platform mutation - requires explicit platform.order.update_status permission.
     */
    public function updateStatus(UpdatePlatformOrderStatusRequest $request, int $order): JsonResponse
    {
        $orderModel = $this->getPlatformOrderAction->execute(
            new GetPlatformOrderDTO(orderId: $order)
        );

        $this->authorize('updateStatus', [PlatformOrderPolicy::class, $orderModel]);

        $updatedOrder = $this->updatePlatformOrderStatusAction->execute(
            UpdatePlatformOrderStatusDTO::fromRequest($request, $order)
        );

        return $this->success(
            new PlatformOrderResource($updatedOrder),
            __('platform.order_status_updated')
        );
    }

    /**
     * Cancel order at platform level.
     * 
     * Platform mutation - requires explicit platform.order.cancel permission.
     */
    public function cancel(int $order): JsonResponse
    {
        $orderModel = $this->getPlatformOrderAction->execute(
            new GetPlatformOrderDTO(orderId: $order)
        );

        $this->authorize('cancel', [PlatformOrderPolicy::class, $orderModel]);

        $canceledOrder = $this->cancelPlatformOrderAction->execute(
            new CancelPlatformOrderDTO(orderId: $order)
        );

        return $this->success(
            new PlatformOrderResource($canceledOrder),
            __('platform.order_canceled')
        );
    }

    /**
     * Refund order at platform level.
     * 
     * Platform mutation - requires explicit platform.order.refund permission.
     */
    public function refund(RefundPlatformOrderRequest $request, int $order): JsonResponse
    {
        $orderModel = $this->getPlatformOrderAction->execute(
            new GetPlatformOrderDTO(orderId: $order)
        );

        $this->authorize('refund', [PlatformOrderPolicy::class, $orderModel]);

        $refundedOrder = $this->refundPlatformOrderAction->execute(
            RefundPlatformOrderDTO::fromRequest($request, $order)
        );

        return $this->success(
            new PlatformOrderResource($refundedOrder),
            __('platform.order_refunded')
        );
    }
}
