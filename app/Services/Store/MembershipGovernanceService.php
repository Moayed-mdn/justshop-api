<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Enums\Store\MembershipStatusEnum;
use App\Enums\Store\MembershipTypeEnum;
use App\Models\User;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

class MembershipGovernanceService
{
    /**
     * Define the effective membership authority based on type and status.
     * This prepares for future enterprise hierarchy.
     */
    public function resolveEffectiveAuthority(User $user, Store $store): array
    {
        // Preparation: Currently we still use store_user pivot.
        // This service will eventually resolve inherited authority from organizations.
        
        $membership = $user->stores()->where('store_id', $store->id)->first();
        
        if (!$membership) {
            return [
                'has_access' => false,
                'type' => null,
                'status' => null,
                'inherited' => false,
            ];
        }

        return [
            'has_access' => true,
            'type' => $this->inferMembershipType($user, $store, $membership),
            'status' => MembershipStatusEnum::ACTIVE, // Default for now
            'inherited' => false,
        ];
    }

    private function inferMembershipType(User $user, Store $store, $membership): MembershipTypeEnum
    {
        if ($user->id === $store->owner_id) {
            return MembershipTypeEnum::STORE_OWNER;
        }

        // Logic to distinguish between admin and delegated operator will be added here
        return MembershipTypeEnum::ADMIN;
    }

    public function logAuthorityResolution(User $user, Store $store, array $authority): void
    {
        Log::info('membership.authority.resolved', [
            'user_id' => $user->id,
            'store_id' => $store->id,
            'authority' => $authority,
        ]);
    }
}
