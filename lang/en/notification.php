<?php

return [
    // API responses
    'device_token_registered' => 'Device registered for notifications.',
    'device_token_removed' => 'Device unregistered.',
    'device_token_not_found' => 'Device token not found.',
    'notification_marked_read' => 'Notification marked as read.',
    'notification_not_found' => 'Notification not found.',
    'all_notifications_marked_read' => 'All notifications marked as read.',

    // Customer-facing
    'order_placed_title' => 'Order confirmed',
    'order_placed_body' => 'Your order :order_number has been confirmed. Total: :total.',
    'order_status_changed_title' => 'Order update',
    'order_status_changed_body' => 'Your order :order_number is now :status.',
    'order_cancelled_title' => 'Order cancelled',
    'order_cancelled_body' => 'Your order :order_number has been cancelled.',

    // Merchant-facing
    'order_received_merchant_title' => 'New order received',
    'order_received_merchant_body' => 'Order :order_number was placed for :store. Total: :total.',
    'order_cancelled_by_customer_title' => 'Order cancelled by customer',
    'order_cancelled_by_customer_body' => 'Order :order_number for :store was cancelled by the customer.',
    'product_low_stock_title' => 'Low stock alert',
    'product_low_stock_body' => ':product is running low on :store (:quantity left).',
    'stripe_connect_enabled_title' => 'Payments enabled',
    'stripe_connect_enabled_body' => ':store can now accept payments via Stripe.',
    'stripe_connect_restricted_title' => 'Payments restricted',
    'stripe_connect_restricted_body' => ':store\'s ability to accept payments has been restricted. Please review your Stripe account.',
    'subscription_trial_started_title' => 'Trial started',
    'subscription_trial_started_body' => 'Your free trial has started.',
    'subscription_activated_title' => 'Subscription active',
    'subscription_activated_body' => 'Your subscription is now active.',
    'subscription_status_changed_title' => 'Subscription update',
    'subscription_status_changed_body' => 'Your subscription status changed to :status.',

    // Admin-facing
    'merchant_registered_title' => 'New merchant registered',
    'merchant_registered_body' => ':name signed up as a merchant.',
    'store_created_title' => 'New store created',
    'store_created_body' => 'A new store, :store, was created.',
    'order_high_value_title' => 'High-value order placed',
    'order_high_value_body' => 'Order :order_number on :store totals :total.',
];
