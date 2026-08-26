<?php

declare(strict_types=1);

namespace App\Enums\Notification;

use App\Enums\PermissionEnum;

/**
 * Drives StoreNotificationRecipientResolver: which store-team members
 * receive a given store-scoped notification.
 *
 * Store Admin always receives every category. Store Staff only receive a
 * category if they hold the associated permission for that store (see
 * docs/notifications/ARCHITECTURE.md §2 for the reasoning and its
 * current-system caveat).
 */
enum NotificationCategoryEnum: string
{
    case ORDER = 'order';
    case INVENTORY = 'inventory';
    case FINANCE = 'finance';

    /**
     * Store Admin only — never fanned out to staff regardless of
     * permissions (Stripe Connect status, platform subscription/billing).
     */
    case ADMIN_ONLY = 'admin_only';

    /**
     * The permission a Store Staff member must hold for this store to
     * receive this category of notification. Null for ADMIN_ONLY, which
     * bypasses staff entirely.
     */
    public function staffGatePermission(): ?string
    {
        return match ($this) {
            self::ORDER => PermissionEnum::ORDER_VIEW,
            self::INVENTORY => PermissionEnum::PRODUCT_VIEW,
            self::FINANCE => PermissionEnum::INVOICE_VIEW,
            self::ADMIN_ONLY => null,
        };
    }
}
