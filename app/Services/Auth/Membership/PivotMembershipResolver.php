<?php

declare(strict_types=1);

namespace App\Services\Auth\Membership;

use App\DTOs\Auth\Membership\MembershipContext;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PivotMembershipResolver implements MembershipResolver
{
    public function resolveForStore(User $user, Store|int|null $store): ?MembershipContext
    {
        if ($store === null) {
            return null;
        }

        $storeId = $store instanceof Store ? (int) $store->id : (int) $store;

        $memberships = $this->resolveForStores($user, [
            $store instanceof Store ? $store : Store::make(['id' => $storeId]),
        ]);

        return $memberships[$storeId] ?? null;
    }

    public function resolveForStores(User $user, iterable $stores): array
    {
        $memberships = [];
        $missingStoreIds = [];

        foreach ($stores as $store) {
            if (!$store instanceof Store) {
                continue;
            }

            $storeId = (int) $store->id;
            $pivot = $store->pivot;

            if ($pivot !== null && isset($pivot->id, $pivot->role)) {
                $memberships[$storeId] = new MembershipContext(
                    membershipId: (int) $pivot->id,
                    userId: (int) $user->id,
                    storeId: $storeId,
                    role: (string) $pivot->role,
                );

                continue;
            }

            $missingStoreIds[] = $storeId;
        }

        if ($missingStoreIds === []) {
            return $memberships;
        }

        $resolvedRows = DB::table('store_user')
            ->select(['id', 'store_id', 'role'])
            ->where('user_id', $user->id)
            ->whereIn('store_id', $missingStoreIds)
            ->get();

        foreach ($resolvedRows as $row) {
            $memberships[(int) $row->store_id] = new MembershipContext(
                membershipId: (int) $row->id,
                userId: (int) $user->id,
                storeId: (int) $row->store_id,
                role: (string) $row->role,
            );
        }

        return $memberships;
    }
}
