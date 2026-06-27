<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PageTemplate;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class PageTemplatePolicy
{
    use HasStoreMembership;

    /**
     * Determine whether the user can view any templates.
     */
    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision(
            $user,
            'viewAny',
            $this->isMerchant($user) && $this->isMember($user, $store),
            $store
        );
    }

    /**
     * Determine whether the user can view the template.
     */
    public function view(User $user, PageTemplate $template): bool
    {
        $store = $template->store;
        return $this->decision(
            $user,
            'view',
            $this->isMerchant($user) && $this->isMember($user, $store),
            $store
        );
    }

    /**
     * Determine whether the user can create templates.
     */
    public function create(User $user, Store $store): bool
    {
        return $this->decision(
            $user,
            'create',
            $this->isMerchant($user) && $this->isMember($user, $store),
            $store
        );
    }

    /**
     * Determine whether the user can update the template.
     */
    public function update(User $user, PageTemplate $template): bool
    {
        $store = $template->store;
        return $this->decision(
            $user,
            'update',
            $this->isMerchant($user) && $this->isAdmin($user, $store),
            $store
        );
    }

    /**
     * Determine whether the user can delete the template.
     */
    public function delete(User $user, PageTemplate $template): bool
    {
        $store = $template->store;
        return $this->decision(
            $user,
            'delete',
            $this->isMerchant($user) && $this->isAdmin($user, $store),
            $store
        );
    }
}
