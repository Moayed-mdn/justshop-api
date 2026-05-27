<?php

return [
    // Success Messages
    'success' => 'تم بنجاح',
    'created' => 'تم الإنشاء بنجاح',
    'updated' => 'تم التحديث بنجاح',
    'deleted' => 'تم الحذف بنجاح',
    
    // Order/Business Logic - Car Workshop Context
    'order_cannot_be_cancelled' => 'لا يمكن إلغاء هذا الطلب.',
    'item_out_of_stock' => 'القطعة غير متوفرة في المخزون.',
    'reorder_items_not_added' => 'لم تتمكن من إضافة أي من القطع إلى سلة التسوق.',
    'reorder_failed' => 'فشل إعادة طلب القطع.',
    
    // Payment
    'payment_failed' => 'فشلت عملية الدفع.',
    'stripe_service_error' => 'حدث خطأ في خدمة Stripe.',
    'stripe_webhook_error' => 'خطأ في webhook الخاص بـ Stripe.',
    'stripe_checkout_failed' => 'فشل إنشاء جلسة الدفع.',
    'stripe_session_retrieve_failed' => 'فشل استرجاع جلسة الدفع.',
    'cart_empty' => 'سلة التسوق الخاصة بك فارغة.',
    'variant_no_longer_available' => 'قطعة الغيار رقم #(:id) لم تعد متوفرة.',
    'not_enough_stock_for_product' => 'الكمية المتوفرة لا تكفي لـ ":product". المتوفر: :available.',
    
    // General Service Messages
    'operation_failed' => 'فشلت العملية.',
    'resource_not_found' => 'المورد غير موجود.',
    'duplicate_entry' => 'تم اكتشاف إدخال مكرر.',
    'insufficient_permissions' => 'صلاحيات غير كافية.',
    'service_unavailable' => 'الخدمة غير متاحة مؤقتاً.',
    
    // Cart - Car Workshop Context
    'cart_cleared' => 'تم إفراغ السلة',
    'variant_not_available' => 'قطعة الغيار غير متوفرة بالكمية المطلوبة.',
    'not_enough_stock' => 'الكمية المتوفرة لا تكفي للطلب المطلوب.',
];