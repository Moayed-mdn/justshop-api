<?php

declare(strict_types=1);

namespace App\Notifications\Product;

use App\Enums\Notification\NotificationTypeEnum;
use App\Models\ProductVariant;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductLowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ProductVariant $variant,
        private readonly string $productName,
        private readonly string $storeName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationTypeEnum::PRODUCT_LOW_STOCK->value,
            'title' => __('notification.product_low_stock_title'),
            'body' => $this->body(),
            'entity_type' => 'product_variant',
            'entity_id' => $this->variant->id,
            'route' => 'merchant.products.show',
            'data' => [
                'product_variant_id' => $this->variant->id,
                'product_id' => $this->variant->product_id,
                'quantity' => $this->variant->quantity,
            ],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: __('notification.product_low_stock_title'),
            body: $this->body(),
            data: [
                'type' => NotificationTypeEnum::PRODUCT_LOW_STOCK->value,
                'entity_type' => 'product_variant',
                'entity_id' => (string) $this->variant->id,
                'route' => 'merchant.products.show',
                'product_id' => (string) $this->variant->product_id,
                'quantity' => (string) $this->variant->quantity,
            ],
        );
    }

    private function body(): string
    {
        return __('notification.product_low_stock_body', [
            'product' => $this->productName,
            'store' => $this->storeName,
            'quantity' => (string) $this->variant->quantity,
        ]);
    }
}
