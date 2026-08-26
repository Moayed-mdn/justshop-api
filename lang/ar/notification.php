<?php

return [
    // API responses
    'device_token_registered' => 'تم تسجيل الجهاز لتلقي الإشعارات.',
    'device_token_removed' => 'تم إلغاء تسجيل الجهاز.',
    'device_token_not_found' => 'رمز الجهاز غير موجود.',
    'notification_marked_read' => 'تم تحديد الإشعار كمقروء.',
    'notification_not_found' => 'الإشعار غير موجود.',
    'all_notifications_marked_read' => 'تم تحديد جميع الإشعارات كمقروءة.',

    // Customer-facing
    'order_placed_title' => 'تم تأكيد الطلب',
    'order_placed_body' => 'تم تأكيد طلبك :order_number. الإجمالي: :total.',
    'order_status_changed_title' => 'تحديث الطلب',
    'order_status_changed_body' => 'حالة طلبك :order_number الآن: :status.',
    'order_cancelled_title' => 'تم إلغاء الطلب',
    'order_cancelled_body' => 'تم إلغاء طلبك :order_number.',

    // Merchant-facing
    'order_received_merchant_title' => 'طلب جديد',
    'order_received_merchant_body' => 'تم إنشاء الطلب :order_number في متجر :store. الإجمالي: :total.',
    'order_cancelled_by_customer_title' => 'ألغى العميل الطلب',
    'order_cancelled_by_customer_body' => 'قام العميل بإلغاء الطلب :order_number في متجر :store.',
    'product_low_stock_title' => 'تنبيه انخفاض المخزون',
    'product_low_stock_body' => 'مخزون :product في متجر :store منخفض (المتبقي: :quantity).',
    'stripe_connect_enabled_title' => 'تم تفعيل الدفع',
    'stripe_connect_enabled_body' => 'أصبح بإمكان متجر :store استقبال المدفوعات عبر Stripe.',
    'stripe_connect_restricted_title' => 'تم تقييد الدفع',
    'stripe_connect_restricted_body' => 'تم تقييد قدرة متجر :store على استقبال المدفوعات. يرجى مراجعة حساب Stripe الخاص بك.',
    'subscription_trial_started_title' => 'بدأت الفترة التجريبية',
    'subscription_trial_started_body' => 'بدأت فترتك التجريبية المجانية.',
    'subscription_activated_title' => 'تم تفعيل الاشتراك',
    'subscription_activated_body' => 'اشتراكك أصبح فعالاً الآن.',
    'subscription_status_changed_title' => 'تحديث الاشتراك',
    'subscription_status_changed_body' => 'تغيرت حالة اشتراكك إلى :status.',

    // Admin-facing
    'merchant_registered_title' => 'تاجر جديد',
    'merchant_registered_body' => 'قام :name بالتسجيل كتاجر.',
    'store_created_title' => 'متجر جديد',
    'store_created_body' => 'تم إنشاء متجر جديد باسم :store.',
    'order_high_value_title' => 'طلب بقيمة عالية',
    'order_high_value_body' => 'الطلب :order_number في متجر :store بإجمالي :total.',
];
