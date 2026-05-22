<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapUserDTO;
use App\Models\User;

class BootstrapIdentityResolver
{
    public function resolve(User $user): BootstrapUserDTO
    {
        return BootstrapUserDTO::fromModel($user);
    }
}
