<?php

declare(strict_types=1);

namespace App\Services\Auth\Membership;

use App\DTOs\Auth\Membership\MembershipContext;
use App\Models\Store;
use App\Models\User;

interface MembershipResolver
{
    public function resolveForStore(User $user, Store|int|null $store): ?MembershipContext;

    /**
     * @param iterable<Store> $stores
     * @return array<int, MembershipContext>
     */
    public function resolveForStores(User $user, iterable $stores): array;
}
