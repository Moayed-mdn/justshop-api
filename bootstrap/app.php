<?php

use App\Exceptions\ExceptionRegistrar;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/v1/storefront/runtime/preview/validate',
            'api/v1/merchant/stores/*/media/upload',
            'api/v1/merchant/stores/*/media/delete',
            'api/v1/merchant/stores/*/hero-banners/upload-image',
            'api/v1/merchant/stores/*/hero-banners/delete-image',
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\InitializeRequestTraceContext::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'store.context' => \App\Http\Middleware\StoreContext::class,
            'storefront.runtime' => \App\Http\Middleware\ResolveStorefrontRuntimeContext::class,
            'onboarding.completed' => \App\Http\Middleware\EnsureOnboardingIsCompleted::class,
            'identity.route' => \App\Http\Middleware\ApplyIdentityRouteContext::class,
            'platform.authority' => \App\Http\Middleware\EnforcePlatformAuthority::class,
            'support.authority' => \App\Http\Middleware\EnforceSupportAuthority::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'api.deprecated' => \App\Http\Middleware\HandleDeprecatedRoute::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        app(ExceptionRegistrar::class)->handle($exceptions);

    })->create();
