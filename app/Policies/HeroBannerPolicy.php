<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HeroBanner;
use App\Models\Store;
use App\Models\User;

class HeroBannerPolicy
{
    /**
     * Determine if the user can view any hero banners for the store.
     */
    public function viewAny(User $user, Store $store): bool
    {
        // Check if user belongs to the store
        return $user->stores()->where('stores.id', $store->id)->exists();
    }

    /**
     * Determine if the user can view a specific hero banner.
     */
    public function view(User $user, HeroBanner $heroBanner, Store $store): bool
    {
        // Ensure the banner belongs to the store and user has access to the store
        return $heroBanner->store_id === $store->id
            && $user->stores()->where('stores.id', $store->id)->exists();
    }

    /**
     * Determine if the user can create hero banners for the store.
     */
    public function create(User $user, Store $store): bool
    {
        // Check if user belongs to the store
        return $user->stores()->where('stores.id', $store->id)->exists();
    }

    /**
     * Determine if the user can update a hero banner.
     */
    public function update(User $user, HeroBanner $heroBanner, Store $store): bool
    {
        // Ensure the banner belongs to the store and user has access to the store
        return $heroBanner->store_id === $store->id
            && $user->stores()->where('stores.id', $store->id)->exists();
    }

    /**
     * Determine if the user can delete a hero banner.
     */
    public function delete(User $user, HeroBanner $heroBanner, Store $store): bool
    {
        // Ensure the banner belongs to the store and user has access to the store
        return $heroBanner->store_id === $store->id
            && $user->stores()->where('stores.id', $store->id)->exists();
    }

    /**
     * Determine if the user can restore a hero banner.
     */
    public function restore(User $user, HeroBanner $heroBanner, Store $store): bool
    {
        // Ensure the banner belongs to the store and user has access to the store
        return $heroBanner->store_id === $store->id
            && $user->stores()->where('stores.id', $store->id)->exists();
    }
}
