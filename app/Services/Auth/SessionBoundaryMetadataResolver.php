<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Identity\IdentityContext;
use App\DTOs\Auth\Identity\SessionBoundaryMetadata;
use Illuminate\Http\Request;

class SessionBoundaryMetadataResolver
{
    public function resolve(Request $request, ?IdentityContext $identityContext = null): SessionBoundaryMetadata
    {
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $ownershipKey = $identityContext !== null
            ? sprintf(
                '%s:%s:%d',
                $identityContext->authDomain->value,
                $identityContext->actorType->value,
                $identityContext->actorId,
            )
            : null;

        return new SessionBoundaryMetadata(
            sessionId: $sessionId,
            authDomain: $identityContext?->authDomain,
            actorType: $identityContext?->actorType,
            actorId: $identityContext?->actorId,
            authorityModel: 'shared_sanctum_session',
            isolationState: 'shared_until_guard_split',
            ownershipKey: $ownershipKey,
        );
    }
}
