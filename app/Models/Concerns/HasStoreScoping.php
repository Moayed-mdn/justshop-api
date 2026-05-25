<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasStoreScoping
 * 
 * Provides structural isolation for models that belong to a Store.
 */
trait HasStoreScoping
{
    /**
     * Get the name of the column used for store scoping.
     */
    public function getStoreIdColumn(): string
    {
        return 'store_id';
    }

    /**
     * Scope a query to only include resources for a specific store.
     */
    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where($this->getStoreIdColumn(), $storeId);
    }
}
