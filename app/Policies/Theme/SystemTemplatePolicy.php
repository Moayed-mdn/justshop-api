<?php

declare(strict_types=1);

namespace App\Policies\Theme;

use App\Models\Store;
use App\Models\Theme\ThemeTemplate;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class SystemTemplatePolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision(
            $user,
            'viewAny',
            $this->isMerchant($user) && $this->isMember($user, $store),
            $store
        );
    }

    public function view(User $user, ThemeTemplate $template): bool
    {
        $store = $template->theme?->store;
        return $store ? $this->decision($user, 'view', $this->isMerchant($user) && $this->isMember($user, $store), $store) : false;
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision(
            $user,
            'create',
            $this->isMerchant($user) && $this->isMember($user, $store),
            $store
        );
    }

    public function update(User $user, ThemeTemplate $template): bool
    {
        $store = $template->theme?->store;
        return $store ? $this->decision($user, 'update', $this->isMerchant($user) && $this->isAdmin($user, $store), $store) : false;
    }

    public function delete(User $user, ThemeTemplate $template): bool
    {
        $store = $template->theme?->store;
        return $store ? $this->decision($user, 'delete', $this->isMerchant($user) && $this->isAdmin($user, $store), $store) : false;
    }
}
