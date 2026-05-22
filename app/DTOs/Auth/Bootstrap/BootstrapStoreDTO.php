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
    ) {}

    public static function fromModel(Store $store, string $role): self
    {
        return new self(
            id: (int) $store->id,
            name: $store->name,
            slug: $store->slug,
            domain: $store->domain,
            currency: $store->currency ?? 'USD',
            role: $role,
        );
    }
}
