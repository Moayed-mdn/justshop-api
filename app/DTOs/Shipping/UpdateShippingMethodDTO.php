<?php

namespace App\DTOs\Shipping;

/**
 * DTO for updating an existing shipping method.
 */
class UpdateShippingMethodDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly ?string $name,
        public readonly ?string $code,
        public readonly ?string $description,
        public readonly ?float $price,
        public readonly ?string $currency,
        public readonly ?float $minOrderAmount,
        public readonly ?float $maxOrderAmount,
        public readonly ?int $estimatedDeliveryDays,
        public readonly ?int $minDeliveryDays,
        public readonly ?int $maxDeliveryDays,
        public readonly ?bool $isActive,
        public readonly ?int $sortOrder,
    ) {}

    /**
     * Create from array (typically from a validated request).
     */
    public static function fromArray(array $data, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            name: $data['name'] ?? null,
            code: $data['code'] ?? null,
            description: $data['description'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            currency: $data['currency'] ?? null,
            minOrderAmount: isset($data['min_order_amount']) ? (float) $data['min_order_amount'] : null,
            maxOrderAmount: isset($data['max_order_amount']) ? (float) $data['max_order_amount'] : null,
            estimatedDeliveryDays: $data['estimated_delivery_days'] ?? null,
            minDeliveryDays: $data['min_delivery_days'] ?? null,
            maxDeliveryDays: $data['max_delivery_days'] ?? null,
            isActive: $data['is_active'] ?? null,
            sortOrder: $data['sort_order'] ?? null,
        );
    }

    /**
     * Convert to array for model update (only non-null values).
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'min_order_amount' => $this->minOrderAmount,
            'max_order_amount' => $this->maxOrderAmount,
            'estimated_delivery_days' => $this->estimatedDeliveryDays,
            'min_delivery_days' => $this->minDeliveryDays,
            'max_delivery_days' => $this->maxDeliveryDays,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ], fn($value) => $value !== null);
    }
}
