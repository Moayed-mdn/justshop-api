<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Observability\RequestTraceContextManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeRequestTraceContext
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->traceContext->initialize(
            $request->headers->get((string) config('observability.correlation_header')),
        );

        $user = $request->user();

        if ($user) {
            $this->traceContext->enrichActor(
                actorId: $user->id,
                actorType: $user->getActorContext()->value,
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
