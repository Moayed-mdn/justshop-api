<?php

namespace App\Http\Controllers\Api\Admin\Order;

use App\Actions\Admin\Order\CancelOrderAction;
use App\Actions\Admin\Order\GetOrderAction;
use App\Actions\Admin\Order\ListOrdersAction;
use App\Actions\Admin\Order\RefundOrderAction;
use App\Actions\Admin\Order\UpdateOrderStatusAction;
use App\DTOs\Admin\Order\CancelOrderDTO;
use App\DTOs\Admin\Order\GetOrderDTO;
use App\DTOs\Admin\Order\ListOrdersDTO;
use App\DTOs\Admin\Order\RefundOrderDTO;
use App\DTOs\Admin\Order\UpdateOrderStatusDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\CancelOrderRequest;
use App\Http\Requests\Admin\Order\GetOrderRequest;
use App\Http\Requests\Admin\Order\ListOrdersRequest;
use App\Http\Requests\Admin\Order\RefundOrderRequest;
use App\Http\Requests\Admin\Order\UpdateOrderStatusRequest;
use App\Http\Resources\Admin\Order\AdminOrderDetailResource;
use App\Http\Resources\Admin\Order\AdminOrderResource;
use Illuminate\Http\JsonResponse;

class AdminOrderController extends Controller
{
    public function index(ListOrdersRequest $request, ListOrdersAction $action, int $store): JsonResponse
    {
        $this->authorize('view', app('currentStore'));
        $orders = $action->execute(ListOrdersDTO::fromRequest($request, $store));
        return $this->paginated($orders, AdminOrderResource::collection($orders));
    }

    public function show(GetOrderRequest $request, GetOrderAction $action, int $store, int $order): JsonResponse
    {
        $this->authorize('view', app('currentStore'));
        $orderModel = $action->execute(GetOrderDTO::fromRequest($request, $store, $order));
        return $this->success(new AdminOrderDetailResource($orderModel));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, UpdateOrderStatusAction $action, int $store, int $order): JsonResponse
    {
        $this->authorize('update', app('currentStore'));
        $orderModel = $action->execute(UpdateOrderStatusDTO::fromRequest($request, $store, $order));
        return $this->success(new AdminOrderResource($orderModel), __('admin.order_status_updated'));
    }

    public function cancel(CancelOrderRequest $request, CancelOrderAction $action, int $store, int $order): JsonResponse
    {
        $this->authorize('update', app('currentStore'));
        $orderModel = $action->execute(CancelOrderDTO::fromRequest($request, $store, $order));
        return $this->success(new AdminOrderResource($orderModel), __('admin.order_cancelled'));
    }

    public function refund(RefundOrderRequest $request, RefundOrderAction $action, int $store, int $order): JsonResponse
    {
        $this->authorize('update', app('currentStore'));
        $orderModel = $action->execute(RefundOrderDTO::fromRequest($request, $store, $order));
        return $this->success(new AdminOrderResource($orderModel), __('admin.order_refunded'));
    }
}
