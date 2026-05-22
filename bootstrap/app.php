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

        $middleware->api(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\InitializeRequestTraceContext::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'store.context' => \App\Http\Middleware\StoreContext::class,
            'onboarding.completed' => \App\Http\Middleware\EnsureOnboardingIsCompleted::class,
            'identity.route' => \App\Http\Middleware\ApplyIdentityRouteContext::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        app(ExceptionRegistrar::class)->handle($exceptions);

    })->create();
