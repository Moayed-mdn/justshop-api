<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\Notification\DevicePlatformEnum;
use App\Http\Requests\Notification\RegisterDeviceTokenRequest;
use App\Http\Resources\Notification\DeviceTokenResource;
use App\Http\Resources\Notification\NotificationResource;
use App\Services\Notification\DeviceTokenService;
use App\Services\Notification\NotificationCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Device-token registration and in-app notification-center endpoints.
 *
 * Identical behavior is needed under /v1/merchant, /v1/customer, and
 * /v1/platform — every actor type is the same underlying User row, so
 * there's nothing actor-specific about "register my device" or "list my
 * notifications". Rather than three near-duplicate controller classes,
 * each actor-context controller is a few lines that `use` this trait;
 * routing/namespacing still follows the existing per-context controller
 * convention (App\Http\Controllers\Api\{Merchant,Storefront\Account,Platform}).
 *
 * Every method scopes strictly to $request->user() — there is no route
 * parameter anywhere here that could address another user's data.
 */
trait HandlesNotificationEndpoints
{
    public function registerDeviceToken(
        RegisterDeviceTokenRequest $request,
        DeviceTokenService $service,
    ): JsonResponse {
        $deviceToken = $service->registerForUser(
            $request->user(),
            $request->string('token')->toString(),
            DevicePlatformEnum::from($request->string('platform')->toString()),
            $request->string('device_id')->toString() ?: null,
            $request->string('device_name')->toString() ?: null,
        );

        return $this->success(new DeviceTokenResource($deviceToken), __('notification.device_token_registered'));
    }

    public function removeDeviceToken(
        Request $request,
        DeviceTokenService $service,
        string $token,
    ): JsonResponse {
        $removed = $service->removeForUser($request->user(), $token);

        if (!$removed) {
            return $this->error(__('notification.device_token_not_found'), 404);
        }

        return $this->success(null, __('notification.device_token_removed'));
    }

    public function listNotifications(
        Request $request,
        NotificationCenterService $service,
    ): JsonResponse {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $notifications = $service->paginate($request->user(), $perPage);

        return $this->paginated($notifications, NotificationResource::collection($notifications));
    }

    public function unreadCount(
        Request $request,
        NotificationCenterService $service,
    ): JsonResponse {
        return $this->success(['unread_count' => $service->unreadCount($request->user())]);
    }

    public function markAsRead(
        Request $request,
        NotificationCenterService $service,
        string $notification,
    ): JsonResponse {
        $marked = $service->markAsRead($request->user(), $notification);

        if (!$marked) {
            return $this->error(__('notification.notification_not_found'), 404);
        }

        return $this->success(null, __('notification.notification_marked_read'));
    }

    public function markAllAsRead(
        Request $request,
        NotificationCenterService $service,
    ): JsonResponse {
        $service->markAllAsRead($request->user());

        return $this->success(null, __('notification.all_notifications_marked_read'));
    }
}
