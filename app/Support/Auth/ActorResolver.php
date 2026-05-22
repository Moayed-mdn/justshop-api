<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Enums\Auth\ActorContextEnum;
use App\Models\User;
use App\Services\Auth\IdentityContextResolver;

class ActorResolver
{
    public function __construct(
        private readonly IdentityContextResolver $identityContextResolver,
    ) {}

    /**
     * Resolve the actor context for a given user.
     */
    public function resolve(User $user): ActorContextEnum
    {
        return $this->identityContextResolver->resolve($user)->actorType;
    }
}
