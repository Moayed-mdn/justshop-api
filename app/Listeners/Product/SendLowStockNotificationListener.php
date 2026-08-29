<?php

declare(strict_types=1);

namespace App\Listeners\Product;

use App\Enums\Notification\NotificationCategoryEnum;
use App\Events\Product\ProductVariantLowStock;
use App\Listeners\Concerns\EnsuresSingleNotificationDispatch;
use App\Models\ProductVariant;
use App\Notifications\Product\ProductLowStockNotification;
use App\Services\Notification\StoreNotificationRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendLowStockNotificationListener implements ShouldQueue
{
    use EnsuresSingleNotificationDispatch;

    public function __construct(
        private readonly StoreNotificationRecipientResolver $storeRecipients,
    ) {
    }

    public function handle(ProductVariantLowStock $event): void
    {
        // Keyed with quantity: the dispatch site already only fires on a
        // threshold-crossing transition, so two different real crossings
        // naturally have different quantities and both notify; only an
        // exact repeat of the same crossing is suppressed.
        $key = "low-stock:{$event->productVariantId}:{$event->currentQuantity}";
        if (!$this->claimOnce($key)) {
            return;
        }

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