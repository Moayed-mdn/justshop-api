<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Session\GuardShadowSummary;
use App\DTOs\Auth\Session\SessionOwnershipContext;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionGuardTelemetry
{
    public const METRIC_CSRF_OWNERSHIP_MERCHANT = 'csrf.ownership.merchant';
    public const METRIC_CSRF_OWNERSHIP_CUSTOMER = 'csrf.ownership.customer';
    public const METRIC_CSRF_OWNERSHIP_REFERER_MISSING = 'csrf.ownership.referer_missing';
    public const METRIC_CSRF_OWNERSHIP_AMBIGUOUS = 'csrf.ownership.ambiguous';
    public const METRIC_CSRF_OWNERSHIP_GUARD_MISMATCH = 'csrf.ownership.guard_mismatch';

    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function logSessionOwnershipResolved(Request $request, SessionOwnershipContext $context): void
    {
        Log::info('session.ownership.resolved', $this->context($request, [
            'session_ownership' => $context->toArray(),
        ]));
    }

    public function logGuardShadowResolved(Request $request, SessionOwnershipContext $context, GuardShadowSummary $summary): void
    {
        Log::info('guard.shadow.resolved', $this->context($request, [
            'session_ownership' => $context->toArray(),
            'guard_shadow' => $summary->toArray(),
        ]));

        if ($summary->ambiguousOwnershipPath) {
            Log::warning('guard.shadow.ambiguity_detected', $this->context($request, [
                'session_ownership' => $context->toArray(),
                'guard_shadow' => $summary->toArray(),
            ]));
        }

        if ($summary->guardMismatchAnomaly) {
            Log::warning('guard.shadow.mismatch_detected', $this->context($request, [
                'session_ownership' => $context->toArray(),
                'guard_shadow' => $summary->toArray(),
            ]));
        }
    }

    public function logContaminationSignals(Request $request, SessionOwnershipContext $context, GuardShadowSummary $summary): void
    {
        $isCrossDomain = ($context->authDomain === 'customer' && in_array($context->routeDomain, ['merchant_users', 'merchant_admin'], true))
            || (in_array($context->authDomain, ['merchant', 'platform'], true) && $context->routeDomain === 'customer_account');

        $payload = [
            'session_ownership' => $context->toArray(),
            'guard_shadow' => $summary->toArray(),
        ];

        if ($isCrossDomain) {
            Log::warning('session.contamination.cross_domain_detected', $this->context($request, $payload));
            Log::warning('session.contamination.route_domain_detected', $this->context($request, $payload));
        }

        if (($context->actorType === 'customer' && in_array($context->routeDomain, ['merchant_users', 'merchant_admin'], true))
            || (in_array($context->actorType, ['merchant', 'super_admin'], true) && $context->routeDomain === 'customer_account')) {
            Log::warning('session.contamination.actor_context_detected', $this->context($request, $payload));
        }

        if ($context->onboardingApplicable && $context->routeDomain === 'customer_account') {
            Log::warning('session.contamination.onboarding_leakage_detected', $this->context($request, $payload));
        }

        if ($context->sessionOrigin === 'authenticated_shared_session' && $summary->ambiguousOwnershipPath) {
            Log::warning('session.contamination.session_origin_ambiguity_detected', $this->context($request, $payload));
        }

        if ($summary->futureGuardHint === 'ambiguous_guard') {
            Log::warning('session.contamination.future_guard_ambiguity_detected', $this->context($request, $payload));
        }

        $path = $request->path();

        if (str_ends_with($path, '/bootstrap') && $isCrossDomain) {
            Log::warning('session.contamination.bootstrap_misuse_detected', $this->context($request, $payload));
        }

        if (str_ends_with($path, '/logout') && $summary->ambiguousOwnershipPath) {
            Log::warning('session.contamination.logout_ambiguity_detected', $this->context($request, $payload));
        }

        Log::info('session.contamination.severity_assessed', $this->context($request, [
            ...$payload,
            'severity_score' => $this->severityScore($context, $summary, $isCrossDomain),
        ]));
    }

    public function logLogoutOwnership(Request $request, SessionOwnershipContext $context, GuardShadowSummary $summary): void
    {
        Log::info('session.logout.ownership_traced', $this->context($request, [
            'session_ownership' => $context->toArray(),
            'guard_shadow' => $summary->toArray(),
        ]));
    }

    public function logSessionContamination(Request $request, SessionOwnershipContext $context, string $reason): void
    {
        Log::error('session.contamination.detected', $this->context($request, [
            'session_ownership' => $context->toArray(),
            'contamination_reason' => $reason,
        ]));
    }

    public function logCsrfOwnership(Request $request, SessionOwnershipContext $context, GuardShadowSummary $summary): void
    {
        Log::info('session.csrf.ownership_traced', $this->context($request, [
            'session_ownership' => $context->toArray(),
            'guard_shadow' => $summary->toArray(),
        ]));

        $this->logCsrfOwnershipMetrics($request, $context, $summary);
    }

    public function logCsrfOwnershipMetrics(Request $request, SessionOwnershipContext $context, GuardShadowSummary $summary): void
    {
        $baseContext = $this->context($request, [
            'session_ownership' => $context->toArray(),
            'guard_shadow' => $summary->toArray(),
        ]);

        if ($context->authDomain === 'customer') {
            Log::info(self::METRIC_CSRF_OWNERSHIP_CUSTOMER, $baseContext);
        } elseif ($context->authDomain === 'merchant') {
            Log::info(self::METRIC_CSRF_OWNERSHIP_MERCHANT, $baseContext);
        }

        $referer = (string) ($request->headers->get('referer') ?? $request->headers->get('origin') ?? '');
        if ($referer === '') {
            Log::info(self::METRIC_CSRF_OWNERSHIP_REFERER_MISSING, $baseContext);
        }

        if ($summary->ambiguousOwnershipPath) {
            Log::info(self::METRIC_CSRF_OWNERSHIP_AMBIGUOUS, $baseContext);
        }

        if ($summary->guardMismatchAnomaly) {
            Log::info(self::METRIC_CSRF_OWNERSHIP_GUARD_MISMATCH, $baseContext);
        }
    }

    private function severityScore(SessionOwnershipContext $context, GuardShadowSummary $summary, bool $isCrossDomain): int
    {
        $score = 0;

        if ($isCrossDomain) {
            $score += 35;
        }

        if ($summary->ambiguousOwnershipPath) {
            $score += 25;
        }

        if ($summary->guardMismatchAnomaly) {
            $score += 20;
        }

        if ($context->onboardingApplicable && $context->routeDomain === 'customer_account') {
            $score += 10;
        }

        if ($context->sessionOrigin === 'authenticated_shared_session') {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function context(Request $request, array $context): array
    {
        return [
            ...$this->traceContext->current()->toLogContext(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            ...$context,
        ];
    }
}
