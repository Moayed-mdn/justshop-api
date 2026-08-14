<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Policies\PlatformOrderPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    /**
     * List platform orders across all stores.
     * 
     * This intentionally operates at platform scope.
     * Platform actors with platform.order.view can see orders from any store.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', [PlatformOrderPolicy::class]);

        $query = Order::query()->with(['store', 'user']);

        // Apply filters
        if ($request->has('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhere('order_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->paginated($orders, $orders);
    }

    /**
     * Show a specific platform order.
     * 
     * Platform actors can view an order from any store.
     * This is intentionally cross-store.
     */
    public function show(int $order): JsonResponse
    {
        // Find order globally (not scoped to a store)
        $orderModel = Order::with(['store', 'user', 'items'])->findOrFail($order);

        // Platform authorization (not merchant authorization)
        $this->authorize('view', [PlatformOrderPolicy::class, $orderModel]);

        return $this->success($orderModel);
    }

    /**
     * Update order status at platform level.
     * 
     * Platform mutation - requires explicit platform.order.update_status permission.
     */
    public function updateStatus(Request $request, int $order): JsonResponse
    {
        $orderModel = Order::findOrFail($order);

        $this->authorize('updateStatus', [PlatformOrderPolicy::class, $orderModel]);

        $request->validate([
            'status' => 'required|string',
        ]);

        $orderModel->status = $request->input('status');
        $orderModel->save();

        return $this->success($orderModel, 'Platform order status updated successfully');
    }

    /**
     * Cancel order at platform level.
     * 
     * Platform mutation - requires explicit platform.order.cancel permission.
     */
    public function cancel(int $order): JsonResponse
    {
        $orderModel = Order::findOrFail($order);

        $this->authorize('cancel', [PlatformOrderPolicy::class, $orderModel]);

        $orderModel->status = 'cancelled';
        $orderModel->save();

        return $this->success($orderModel, 'Platform order cancelled successfully');
    }

    /**
     * Refund order at platform level.
     * 
     * Platform mutation - requires explicit platform.order.refund permission.
     */
    public function refund(Request $request, int $order): JsonResponse
    {
        $orderModel = Order::findOrFail($order);

        $this->authorize('refund', [PlatformOrderPolicy::class, $orderModel]);

        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        // TODO: Implement actual refund logic with payment gateway
        $orderModel->status = 'refunded';
        $orderModel->save();

        return $this->success($orderModel, 'Platform order refunded successfully');
    }
}
