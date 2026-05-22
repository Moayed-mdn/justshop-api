<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\DTOs\Auth\Identity\IdentityContext;
use App\DTOs\Auth\Identity\RouteDomainContext;
use App\DTOs\Auth\Identity\SessionBoundaryMetadata;
use App\DTOs\Auth\Session\GuardShadowSummary;
use App\DTOs\Auth\Session\SessionOwnershipContext;
use App\Support\System\ApiDomainResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestTraceContextManager
{
    private const REQUEST_ATTRIBUTE = 'request_trace_context';

    public function __construct(
        private readonly Request $request,
        private readonly CorrelationIdGenerator $correlationIdGenerator,
        private readonly ApiDomainResolver $apiDomainResolver,
    ) {}

    public function initialize(?string $incomingCorrelationId = null): RequestTraceContext
    {
        $context = RequestTraceContext::initialize(
            correlationId: $this->correlationIdGenerator->resolve($incomingCorrelationId),
            apiDomain: $this->apiDomainResolver->resolve($this->request)->value,
            releaseVersion: (string) config('observability.release_version'),
        );

        return $this->replace($context);
    }

    public function current(): RequestTraceContext
    {
        $context = $this->request->attributes->get(self::REQUEST_ATTRIBUTE);

        if ($context instanceof RequestTraceContext) {
            return $context;
        }

        return $this->initialize(
            $this->request->headers->get((string) config('observability.correlation_header')),
        );
    }

    public function replace(RequestTraceContext $context): RequestTraceContext
    {
        $this->request->attributes->set(self::REQUEST_ATTRIBUTE, $context);
        Log::withContext($context->toLogContext());

        return $context;
    }

    public function enrichActor(?int $actorId, ?string $actorType): RequestTraceContext
    {
        return $this->replace(
            $this->current()->withActor($actorId, $actorType)
        );
    }

    public function enrichIdentityContext(IdentityContext $identityContext): RequestTraceContext
    {
        return $this->replace(
            $this->current()->withIdentityContext($identityContext)
        );
    }

    public function enrichStore(?int $storeId, ?int $membershipId = null): RequestTraceContext
    {
        return $this->replace(
            $this->current()->withStore($storeId, $membershipId)
        );
    }

    public function enrichRouteDomain(RouteDomainContext $routeDomainContext): RequestTraceContext
    {
        return $this->replace(
            $this->current()->withRouteDomain($routeDomainContext)
        );
    }

    public function enrichSessionBoundary(SessionBoundaryMetadata $sessionBoundary): RequestTraceContext
    {
        return $this->replace(
            $this->current()->withSessionBoundary($sessionBoundary)
        );
    }

    public function enrichSessionOwnership(SessionOwnershipContext $sessionOwnership): RequestTraceContext
    {
        return $this->replace(
            $this->current()->withSessionOwnership($sessionOwnership)
        );
    }

    public function enrichGuardShadow(GuardShadowSummary $guardShadow): RequestTraceContext
    {
        return $this->replace(
            $this->current()->withGuardShadow($guardShadow)
        );
    }

    public function correlationId(): string
    {
        return $this->current()->correlationId;
    }
}
