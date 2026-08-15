<?php

return [
    'internal_server_error' => 'An internal server error occurred.',
    'not_found' => 'Not found.',
    'validation_failed' => 'Validation failed.',
    'unauthorized_access' => 'Unauthorized access.',
    'forbidden' => 'Forbidden.',
    'bad_request' => 'Bad request.',
    'conflict' => 'Conflict occurred.',
    'unprocessable_entity' => 'Unprocessable entity.',
    'too_many_requests' => 'Too many requests. Please try again later.',
    
    // Validation Messages
    'email_required' => 'The email field is required.',
    'email_invalid' => 'Please enter a valid email address.',
    'password_required' => 'The password field is required.',
    'cart_empty' => 'Your cart is empty.',
    'order_number_required' => 'Please enter your order number.',
    'checkout_email_required' => 'Please enter the email used during checkout.',
    
    // Store Messages
    'store_not_found' => 'Store not found',
    'unauthorized_store' => 'You are not authorized to access this store.',
    'invalid_store_lifecycle_transition' => 'This store status transition is not allowed.',

    // Billing Messages
    'billing_account_not_found' => 'Billing account not found.',
    'invoice_not_found' => 'Invoice not found.',
    'invoice_pdf_not_available' => 'Invoice PDF is not available.',
    'feature_flag_value_required' => 'Feature flag value is required.',

    // User Messages
    'user_not_found' => 'User not found.',

    // Audit Messages
    'audit_log_not_found' => 'Audit log not found.',
    'end_date_must_be_after_start_date' => 'End date must be after or equal to start date.',
    'per_page_max_100' => 'Per page value must not exceed 100.',

    // Product Messages
    'product_not_found' => 'Product not found.',
    'product_restore_failed' => 'Product restore failed.',

    'tag_not_found' => 'Tag not found.',

    // Permission Denial Messages
    'permission' => [
        'generic' => 'You don\'t have permission to :action :resource. Contact your store administrator.',

        'product' => [
            'view' => 'You don\'t have permission to view products. Contact your store administrator.',
            'create' => 'You don\'t have permission to create products. Contact your store administrator.',
            'update' => 'You don\'t have permission to update products. This action requires Store Admin role.',
            'delete' => 'You don\'t have permission to delete products. View-only access is granted.',
            'restore' => 'You don\'t have permission to restore products. Contact your store administrator.',
        ],

        'category' => [
            'view' => 'You don\'t have permission to view categories. Contact your store administrator.',
            'create' => 'You don\'t have permission to create categories. Contact your store administrator.',
            'update' => 'You don\'t have permission to update categories. This action requires Store Admin role.',
            'delete' => 'You don\'t have permission to delete categories. View-only access is granted.',
            'restore' => 'You don\'t have permission to restore categories. Contact your store administrator.',
        ],

        'brand' => [
            'view' => 'You don\'t have permission to view brands. Contact your store administrator.',
            'create' => 'You don\'t have permission to create brands. Contact your store administrator.',
            'update' => 'You don\'t have permission to update brands. This action requires Store Admin role.',
            'delete' => 'You don\'t have permission to delete brands. View-only access is granted.',
            'restore' => 'You don\'t have permission to restore brands. Contact your store administrator.',
        ],

        'tag' => [
            'view' => 'You don\'t have permission to view tags. Contact your store administrator.',
            'create' => 'You don\'t have permission to create tags. This action requires Store Admin role.',
            'update' => 'You don\'t have permission to update tags. Contact your store administrator.',
            'delete' => 'You don\'t have permission to delete tags. View-only access is granted.',
        ],

        'order' => [
            'view' => 'You don\'t have permission to view orders. Contact your store administrator.',
            'update_status' => 'You don\'t have permission to update order status. This action requires Store Admin role.',
            'cancel' => 'You don\'t have permission to cancel orders. Contact your store administrator.',
            'refund' => 'You don\'t have permission to refund orders. This action requires Store Admin role.',
        ],

        'navigation' => [
            'view' => 'You don\'t have permission to view navigation menus. Contact your store administrator.',
            'create' => 'You don\'t have permission to create navigation menus. Contact your store administrator.',
            'update' => 'You don\'t have permission to update navigation menus. Contact your store administrator.',
            'delete' => 'You don\'t have permission to delete navigation menus. Contact your store administrator.',
        ],

        'page' => [
            'view' => 'You don\'t have permission to view marketing pages. Contact your store administrator.',
            'create' => 'You don\'t have permission to create marketing pages. Contact your store administrator.',
            'update' => 'You don\'t have permission to update marketing pages. This action requires Store Admin role.',
            'delete' => 'You don\'t have permission to delete marketing pages. View-only access is granted.',
            'publish' => 'You don\'t have permission to publish marketing pages. This action requires Store Admin role.',
        ],

        'store' => [
            'update' => 'You don\'t have permission to update store settings. This action requires Store Admin role.',
            'delete' => 'You don\'t have permission to delete this store. Only the store owner can perform this action.',
            'restore' => 'You don\'t have permission to restore this store. Contact your store administrator.',
            'forceDelete' => 'You don\'t have permission to permanently delete this store. Contact your store administrator.',
        ],

        'system_template' => [
            'view' => 'You don\'t have permission to view system templates. Contact your store administrator.',
            'create' => 'You don\'t have permission to create system templates. Contact your store administrator.',
            'update' => 'You don\'t have permission to update system templates. Contact your store administrator.',
            'delete' => 'You don\'t have permission to delete system templates. Contact your store administrator.',
        ],

        'shipping' => [
            'view' => 'You don\'t have permission to view shipping settings. Contact your store administrator.',
            'create' => 'You don\'t have permission to create shipping zones or methods. Contact your store administrator.',
            'update' => 'You don\'t have permission to update shipping settings. Contact your store administrator.',
            'delete' => 'You don\'t have permission to delete shipping zones or methods. Contact your store administrator.',
        ],

        'template' => [
            'view' => 'You don\'t have permission to view page templates. Contact your store administrator.',
            'create' => 'You don\'t have permission to create page templates. Contact your store administrator.',
            'update' => 'You don\'t have permission to update page templates. Contact your store administrator.',
            'delete' => 'You don\'t have permission to delete page templates. Contact your store administrator.',
        ],

        'theme' => [
            'view' => 'You don\'t have permission to view themes. Contact your store administrator.',
            'create' => 'You don\'t have permission to create themes. Contact your store administrator.',
            'update' => 'You don\'t have permission to update themes. Contact your store administrator.',
            'delete' => 'You don\'t have permission to delete themes. Contact your store administrator.',
            'publish' => 'You don\'t have permission to publish themes. Contact your store administrator.',
        ],

        'user' => [
            'create' => 'You don\'t have permission to create users. Contact your store administrator.',
            'block' => 'You don\'t have permission to block users. This action requires Store Admin role.',
            'delete' => 'You don\'t have permission to delete users. Contact your store administrator.',
            'restore' => 'You don\'t have permission to restore users. Contact your store administrator.',
        ],

        'subscription' => [
            'view' => 'You don\'t have permission to view subscription details. Contact your store administrator.',
            'upgrade' => 'You don\'t have permission to upgrade the subscription plan. This action requires the Store Admin role.',
            'downgrade' => 'You don\'t have permission to downgrade the subscription plan. This action requires the Store Admin role.',
            'cancel' => 'You don\'t have permission to cancel the subscription. This action requires the Store Admin role.',
            'resume' => 'You don\'t have permission to resume the subscription. This action requires the Store Admin role.',
        ],

        'invoice' => [
            'view' => 'You don\'t have permission to view invoices. Contact your store administrator.',
            'download' => 'You don\'t have permission to download invoices. Contact your store administrator.',
        ],

        'billing' => [
            'portal' => 'You don\'t have permission to access the billing portal. Contact your store administrator.',
        ],

        'payment_method' => [
            'update' => 'You don\'t have permission to update payment methods. Contact your store administrator.',
            'delete' => 'You don\'t have permission to delete payment methods. Contact your store administrator.',
        ],
    ],
];
