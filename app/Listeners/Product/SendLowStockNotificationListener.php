<?php

declare(strict_types=1);

namespace App\Listeners\Product;

use App\Enums\Notification\NotificationCategoryEnum;
use App\Events\Product\ProductVariantLowStock;
use App\Models\ProductVariant;
use App\Notifications\Product\ProductLowStockNotification;
use App\Services\Notification\StoreNotificationRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendLowStockNotificationListener implements ShouldQueue
{
    public function __construct(
        private readonly StoreNotificationRecipientResolver $storeRecipients,
    ) {
    }

    public function handle(ProductVariantLowStock $event): void
    {
        $variant = ProductVariant::with(['product.store', 'product.translations'])->find($event->productVariantId);

        if (!$variant || !$variant->product || !$variant->product->store) {
            return;
        }

        $store = $variant->product->store;
        $recipients = $this->storeRecipients->resolve($store, NotificationCategoryEnum::INVENTORY);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new ProductLowStockNotification($variant, $variant->product->translated('name') ?? '', $store->name),
        );
    }
}
