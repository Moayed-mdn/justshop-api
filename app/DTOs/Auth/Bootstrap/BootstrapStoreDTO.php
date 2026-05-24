<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

use App\Models\Store;

class BootstrapStoreDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $domain,
        public string $currency,
        public string $role,
        public string $status,
        public bool $isActive,
        public ?string $statusChangedAt,
        public ?string $createdAt,
        public array $permissions,
    ) {}

    public static function fromModel(Store $store, string $role, array $permissions = []): self
    {
        return new self(
            id: (int) $store->id,
            name: $store->name,
            slug: $store->slug,
            domain: $store->domain,
            currency: $store->currency ?? 'USD',
            role: $role,
            status: $store->status->value,
            isActive: $store->isOperational(),
            statusChangedAt: $store->status_changed_at?->toIso8601String(),
            createdAt: $store->created_at?->toIso8601String(),
            permissions: $permissions,
        );
    }
}
