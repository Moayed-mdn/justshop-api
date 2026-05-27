<?php

return [
    // Success Messages
    'success' => 'Success',
    'created' => 'Created successfully',
    'updated' => 'Updated successfully',
    'deleted' => 'Deleted successfully',
    
    // Order/Business Logic
    'order_cannot_be_cancelled' => 'This order cannot be cancelled.',
    'item_out_of_stock' => 'Item is out of stock.',
    'reorder_items_not_added' => 'None of the items could be added to your cart.',
    'reorder_failed' => 'Failed to reorder the items.',
    
    // Payment
    'payment_failed' => 'Payment failed.',
    'stripe_service_error' => 'A Stripe service error occurred.',
    'stripe_webhook_error' => 'Stripe webhook error.',
    'stripe_checkout_failed' => 'Failed to create payment session.',
    'stripe_session_retrieve_failed' => 'Failed to retrieve payment session.',
    'operation_failed' => 'Operation failed.',
    'resource_not_found' => 'Resource not found.',
    'duplicate_entry' => 'Duplicate entry detected.',
    'insufficient_permissions' => 'Insufficient permissions.',
    'service_unavailable' => 'Service temporarily unavailable.',
    
    // Cart
    'cart_cleared' => 'Cart cleared',
    'cart_empty' => 'Your cart is empty.',
    'variant_not_available' => 'Product variant is not available in the requested quantity.',
    'variant_no_longer_available' => 'Product variant #(:id) is no longer available.',
    'not_enough_stock' => 'Not enough stock for the requested quantity.',
    'not_enough_stock_for_product' => 'Not enough stock for ":product". Available: :available.',
];