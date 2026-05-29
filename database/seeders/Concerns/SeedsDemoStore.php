<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\Store;
use RuntimeException;

trait SeedsDemoStore
{
    protected function demoStore(): Store
    {
        static $store = null;

        if ($store instanceof Store) {
            return $store;
        }

        $store = Store::query()->where('slug', 'merchant-store')->first();

        if (!$store instanceof Store) {
            throw new RuntimeException(
                'Demo store (merchant-store / demo.justshop.test) must be seeded before catalog seeders run.',
            );
        }

        return $store;
    }

    protected function demoStoreId(): int
    {
        return $this->demoStore()->id;
    }
}
