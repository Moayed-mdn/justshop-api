<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HandleDeprecatedRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $newRoute = null): Response
    {
        $response = $next($request);

        // Add deprecation headers
        $response->headers->set('X-API-Deprecated', 'true');
        
        $suggestedRoute = $newRoute;
        if (!$suggestedRoute) {
            $path = $request->path();
            
            // Normalize path by removing 'api/' prefix if present (Laravel adds it in tests)
            $normalizedPath = preg_replace('/^api\//', '', $path);
            
            if (str_starts_with($normalizedPath, 'v1/admin/stores/')) {
                $suggestedRoute = str_replace('v1/admin/stores/', '/v1/merchant/stores/', $normalizedPath);
            } elseif (str_starts_with($normalizedPath, 'v1/admin/')) {
                $suggestedRoute = str_replace('v1/admin/', '/v1/platform/', $normalizedPath);
            } elseif ($normalizedPath === 'v1/store-slug/check') {
                $suggestedRoute = '/v1/merchant/stores/slug-check';
            } elseif ($normalizedPath === 'v1/stores') {
                $suggestedRoute = '/v1/merchant/stores';
            } elseif (preg_match('#^v1/stores/\d+(?:/provisioning-status)?$#', $normalizedPath) === 1) {
                $suggestedRoute = str_replace('v1/stores/', '/v1/merchant/stores/', $normalizedPath);
            } elseif ($normalizedPath === 'v1/users/bootstrap' || $normalizedPath === 'v1/users/auth/bootstrap') {
                $suggestedRoute = '/v1/merchant/me';
            } elseif ($normalizedPath === 'v1/users/sessions') {
                $suggestedRoute = '/v1/merchant/sessions';
            } elseif (preg_match('#^v1/users/sessions/[^/]+$#', $normalizedPath) === 1) {
                $suggestedRoute = str_replace('v1/users/sessions/', '/v1/merchant/sessions/', $normalizedPath);
            } elseif (str_starts_with($normalizedPath, 'v1/users/auth/')) {
                $suggestedRoute = str_replace('v1/users/auth/', '/v1/merchant/auth/', $normalizedPath);
            } elseif ($normalizedPath === 'v1/storefront/account/bootstrap') {
                $suggestedRoute = '/v1/customer/bootstrap';
            } elseif ($normalizedPath === 'v1/storefront/account/logout') {
                $suggestedRoute = '/v1/customer/auth/logout';
            } elseif (str_starts_with($normalizedPath, 'v1/stores/')) {
                $suggestedRoute = str_replace('v1/stores/', '/v1/storefront/stores/', $normalizedPath);
            } elseif (str_starts_with($normalizedPath, 'v1/storefront/account/')) {
                $suggestedRoute = str_replace('v1/storefront/account/', '/v1/customer/', $normalizedPath);
            } elseif (str_starts_with($normalizedPath, 'v1/users/')) {
                $suggestedRoute = str_replace('v1/users/', '/v1/merchant/', $normalizedPath);
            } elseif ($normalizedPath === 'v1/me') {
                $suggestedRoute = '/v1/merchant/me';
            }
        }

        if ($suggestedRoute) {
            $response->headers->set('X-API-Suggested-New-Route', $suggestedRoute);
        }

        // Log legacy usage for telemetry
        Log::warning('Legacy API route accessed', [
            'path' => $request->path(),
            'method' => $request->method(),
            'suggested_new_route' => $suggestedRoute,
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ]);

        return $response;
    }
}
