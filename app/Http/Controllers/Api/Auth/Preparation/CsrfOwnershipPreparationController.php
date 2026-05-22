<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth\Preparation;

use App\Http\Controllers\Controller;
use App\Services\Auth\GuardShadowAnalyzer;
use App\Services\Auth\SessionGuardTelemetry;
use App\Services\Auth\SessionOwnershipResolver;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use Symfony\Component\HttpFoundation\Response;

class CsrfOwnershipPreparationController extends Controller
{
    public function __construct(
        private readonly CsrfCookieController $csrfCookieController,
        private readonly SessionOwnershipResolver $sessionOwnershipResolver,
        private readonly GuardShadowAnalyzer $guardShadowAnalyzer,
        private readonly SessionGuardTelemetry $sessionGuardTelemetry,
    ) {}

    public function show(Request $request): Response
    {
        $ownership = $this->sessionOwnershipResolver->resolveForCsrf($request);
        $shadow = $this->guardShadowAnalyzer->analyze($ownership);
        $this->sessionGuardTelemetry->logCsrfOwnership($request, $ownership, $shadow);

        $response = $this->csrfCookieController->show($request);
        $response->headers->set('X-Session-Auth-Domain', (string) $ownership->authDomain);
        $response->headers->set('X-Session-Route-Domain', (string) $ownership->routeDomain);
        $response->headers->set('X-Future-Guard-Hint', $shadow->futureGuardHint);

        return $response;
    }
}
