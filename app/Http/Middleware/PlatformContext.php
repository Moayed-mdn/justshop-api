<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform Context Middleware
 * 
 * Sets the Spatie Permission team_id to 0 (global/platform level) for platform routes.
 * This ensures that permission checks like `$user->can(PermissionEnum::MARKETING_PLATFORM_VIEW)`
 * correctly check against platform-level permissions assigned with team_id = 0.
 * 
 * Without this middleware, permission checks may fail or check against the wrong team context.
 */
class PlatformContext
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Set Spatie Permission context to global/platform level (team_id = 0)
        $this->permissionRegistrar->setPermissionsTeamId(0);
        
        // Clear cached permissions for this request to ensure fresh permission checks
        $this->permissionRegistrar->forgetCachedPermissions();

        return $next($request);
    }
}
