<?php

declare(strict_types=1);

namespace App\Enums\Notification;

/**
 * Stable string identifier stored in every notification's payload as
 * `type`, and used as the FCM `data.type` field. Mobile/web clients switch
 * on this to decide what screen to open — see
 * docs/notifications/CLIENT_PAYLOADS.md.
 *
 * Values are permanent once shipped to a client build: append new cases,
 * never rename or remove existing ones.
 */
enum NotificationTypeEnum: string
{
    case ORDER_PLACED = 'order.placed';
    case ORDER_STATUS_CHANGED = 'order.status_changed';
    case ORDER_CANCELLED = 'order.cancelled';
    case ORDER_RECEIVED_MERCHANT = 'order.received_merchant';
    case ORDER_CANCELLED_BY_CUSTOMER = 'order.cancelled_by_customer';
    case ORDER_HIGH_VALUE = 'order.high_value';
    case PRODUCT_LOW_STOCK = 'product.low_stock';
    case STORE_STRIPE_CONNECT_STATUS_CHANGED = 'store.stripe_connect_status_changed';
    case SUBSCRIPTION_TRIAL_STARTED = 'subscription.trial_started';
    case SUBSCRIPTION_ACTIVATED = 'subscription.activated';
    case SUBSCRIPTION_STATUS_CHANGED = 'subscription.status_changed';
    case LEAD_SUBMITTED = 'lead.submitted';
    case MERCHANT_REGISTERED = 'merchant.registered';
    case STORE_CREATED = 'store.created';
}
