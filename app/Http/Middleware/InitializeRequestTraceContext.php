<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionBoundaryMetadataResolver;
use App\Support\Observability\RequestTraceContextManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeRequestTraceContext
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
        private readonly IdentityContextResolver $identityContextResolver,
        private readonly SessionBoundaryMetadataResolver $sessionBoundaryMetadataResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->traceContext->initialize(
            $request->headers->get((string) config('observability.correlation_header')),
        );

        $this->traceContext->enrichSessionBoundary(
            $this->sessionBoundaryMetadataResolver->resolve($request),
        );

        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $identityContext = $this->identityContextResolver->resolve($user);
            $this->traceContext->enrichIdentityContext($identityContext);
            $this->traceContext->enrichSessionBoundary(
                $this->sessionBoundaryMetadataResolver->resolve($request, $identityContext),
            );
        }

        $response = $next($request);

        $response->headers->set(
            (string) config('observability.correlation_header'),
            $this->traceContext->correlationId(),
        );

        return $response;
    }
}
