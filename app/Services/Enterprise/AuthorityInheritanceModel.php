<?php

declare(strict_types=1);

namespace App\Services\Enterprise;

use App\Enums\Enterprise\OwnershipSemanticEnum;
use App\Models\Store;
use App\Models\User;

/**
 * Authority Inheritance Model
 * 
 * Wave 6: Prepare governance for authority inheritance.
 * NOT activated yet. Preparation only.
 * 
 * Prepare governance for:
 * - Org-level authority
 * - Delegated store access
 * - Scoped authority
 * - Support escalation
 * - Enterprise hierarchy
 * 
 * WITHOUT activating complex inheritance yet.
 */
class AuthorityInheritanceModel
{
    public function resolveOwnershipSemantic(User $user, Store $store): OwnershipSemanticEnum
    {
        // Wave 6: Simple ownership resolution
        // Future: Complex inheritance resolution

        $membership = $user->stores()->where('store_id', $store->id)->first();

        if ($membership === null) {
            throw new \RuntimeException('User is not a member of this store.');
        }

        // Simple role-based resolution (current)
        return match ($membership->pivot->role) {
            'store_admin' => OwnershipSemanticEnum::ADMIN,
            'store_member' => OwnershipSemanticEnum::MEMBER,
            default => OwnershipSemanticEnum::MEMBER,
        };
    }

    public function canInheritAuthority(User $user, Store $store): bool
    {
        // Wave 6: No inheritance yet
        // Future: Check org-level authority inheritance
        return false;
    }

    public function getInheritedAuthority(User $user, Store $store): ?OwnershipSemanticEnum
    {
        // Wave 6: No inheritance yet
        // Future: Resolve inherited authority from organization
        return null;
    }

    public function isDelegatedAccess(User $user, Store $store): bool
    {
        // Wave 6: No delegation yet
        // Future: Check if access is delegated from another actor
        return false;
    }

    public function isSupportEscalation(User $user, Store $store): bool
    {
        // Wave 6: Check if user is support actor
        return $user->getActorContext()->value === 'support_agent';
    }

    public function getAuthorityScope(User $user, Store $store): string
    {
        // Wave 6: Simple store-scoped authority
        // Future: org-scoped, delegated, inherited, etc.
        return 'store_scoped';
    }
}
