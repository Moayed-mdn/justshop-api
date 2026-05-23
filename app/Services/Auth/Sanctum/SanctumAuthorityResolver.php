<?php

declare(strict_types=1);

namespace App\Services\Auth\Sanctum;

use App\Services\Auth\SessionOwnershipManager;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SanctumAuthorityResolver
{
    public function __construct(
        private readonly SessionOwnershipManager $sessionOwnershipManager,
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function resolve(Request $request): array
    {
        $authDomain = $this->sessionOwnershipManager->getAuthDomain();
        $actorType = $this->sessionOwnershipManager->getActorType();
        $actorId = $this->sessionOwnershipManager->getActorId();

        $authorityContext = [
            'auth_domain' => $authDomain,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'is_authenticated' => $authDomain !== null,
        ];

        $this->logAuthorityResolution($request, $authorityContext);

        return $authorityContext;
    }

    private function logAuthorityResolution(Request $request, array $context): void
    {
        Log::info('sanctum.authority.resolved', [
            'request_path' => $request->path(),
            'correlation_id' => $this->traceContext->current()->correlationId,
            ...$context,
        ]);
    }
}
