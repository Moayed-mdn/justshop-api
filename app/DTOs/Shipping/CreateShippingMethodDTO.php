<?php

namespace App\DTOs\Shipping;

/**
 * DTO for creating a new shipping method.
 */
class CreateShippingMethodDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly float $price,
        public readonly string $currency,
        public readonly ?float $minOrderAmount,
        public readonly ?float $maxOrderAmount,
        public readonly ?int $estimatedDeliveryDays,
        public readonly ?int $minDeliveryDays,
        public readonly ?int $maxDeliveryDays,
        public readonly bool $isActive,
        public readonly int $sortOrder,
    ) {}

    /**
     * Create from array (typically from a validated request).
     */
    public static function fromArray(array $data, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            name: $data['name'],
            code: $data['code'] ?? self::generateCode($data['name']),
            description: $data['description'] ?? null,
            price: (float) $data['price'],
            currency: $data['currency'] ?? 'USD',
            minOrderAmount: isset($data['min_order_amount']) ? (float) $data['min_order_amount'] : null,
            maxOrderAmount: isset($data['max_order_amount']) ? (float) $data['max_order_amount'] : null,
            estimatedDeliveryDays: $data['estimated_delivery_days'] ?? null,
            minDeliveryDays: $data['min_delivery_days'] ?? null,
            maxDeliveryDays: $data['max_delivery_days'] ?? null,
            isActive: $data['is_active'] ?? true,
            sortOrder: $data['sort_order'] ?? 0,
        );
    }

    /**
     * Generate a code from the name.
     */
    private static function generateCode(string $name): string
    {
        return strtolower(str_replace([' ', '-'], '_', $name));
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'store_id' => $this->storeId,
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
        ];
    }
}
