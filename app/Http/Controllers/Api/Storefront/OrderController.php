<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Actions\Order\CancelOrderAction;
use App\Actions\Order\CreateOrderAction;
use App\Actions\Order\GetOrderAction;
use App\Actions\Order\ListOrdersAction;
use App\DTOs\Order\CancelOrderDTO;
use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\GetOrderDTO;
use App\DTOs\Order\ListOrdersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\FilterOrdersRequest;
use App\Http\Requests\Order\GetOrderRequest;
use App\Http\Requests\Order\GuestOrderLookupRequest;
use App\Http\Requests\Order\ListOrdersRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private ListOrdersAction $listOrdersAction,
        private GetOrderAction $getOrderAction,
        private CreateOrderAction $createOrderAction,
        private CancelOrderAction $cancelOrderAction,
    ) {}

    public function filters(FilterOrdersRequest $request, int $store): JsonResponse
    {
        // Filters logic - returns filter options
        return $this->success([]);
    }

    public function index(ListOrdersRequest $request, int $store): JsonResponse
    {
        $orders = $this->listOrdersAction->execute(
            ListOrdersDTO::fromRequest($request, $store)
        );

        return $this->paginated(
            $orders,
            OrderResource::collection($orders)
        );
    }

    public function show(Request $request, int $store, string $orderNumber): JsonResponse
    {
        // Wave 2 Remediation: Fetch order first to enable explicit policy authorization
        $order = $this->getOrderAction->execute(
            GetOrderDTO::fromRequest($request, $store, $orderNumber)
        );

        // Wave 2 Remediation: Explicit policy authorization moved from Action to Controller
        // Authorization now owned by OrderPolicy::view()
        $this->authorize('view', $order);

        return $this->success(new OrderResource($order));
    }

    public function cancel(CancelOrderRequest $request, int $store, string $orderNumber): JsonResponse
    {
        // Wave 2 Remediation: Fetch order first to enable explicit policy authorization
        $order = $this->getOrderAction->execute(
            GetOrderDTO::fromRequest($request, $store, $orderNumber)
        );

        // Wave 2 Remediation: Explicit policy authorization moved from Action to Controller
        // Authorization now owned by OrderPolicy::cancel()
        $this->authorize('cancel', $order);

        $order = $this->cancelOrderAction->execute(
            CancelOrderDTO::fromRequest($request, $store, $orderNumber)
        );

        return $this->success(new OrderResource($order), __('order.cancelled'));
    }

    public function reorder(Request $request, int $store, string $orderNumber): JsonResponse
    {
        // Reorder logic placeholder
        return $this->success(null, __('order.reordered'));
    }

    public function guestLookup(GuestOrderLookupRequest $request): JsonResponse
    {
        // Guest lookup - no store context needed
        return $this->success(null, 'Guest order lookup');
    }
}
