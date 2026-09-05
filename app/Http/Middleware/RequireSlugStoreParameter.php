<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Store\StoreNotFoundException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject a purely numeric {store} route parameter, requiring the store
 * slug instead.
 *
 * Store::resolveRouteBinding() intentionally accepts either a numeric id
 * or a slug, since that dual lookup is relied on across most of the
 * merchant API. A small number of specific endpoints (currently: shipping
 * address settings) are designed to accept the slug only. Rather than
 * restrict the shared model binding (which would silently break every
 * other endpoint using {store}), this middleware enforces the stricter
 * rule only on the routes it's explicitly attached to, checked before
 * implicit route-model-binding substitutes the parameter.
 */
class RequireSlugStoreParameter
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->route()?->originalParameter('store');

        if (is_string($raw) && ctype_digit($raw)) {
            throw new StoreNotFoundException();
        }

        return $next($request);
    }
}
