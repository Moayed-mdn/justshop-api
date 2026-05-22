<?php

namespace App\Providers;

use App\Events\Lead\LeadSubmitted;
use App\Exceptions\Auth\TooManyRequestsException;
use App\Listeners\Lead\SendLeadSubmittedNotificationListener;
use App\Support\Audit\AuditLoggerInterface;
use App\Support\Audit\DatabaseAuditLogger;
use App\Support\Observability\RequestTraceContextManager;
use App\Support\Security\LogSecurityEventLogger;
use App\Support\Security\SecurityEventLoggerInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(RequestTraceContextManager::class, RequestTraceContextManager::class);
        $this->app->scoped(AuditLoggerInterface::class, DatabaseAuditLogger::class);
        $this->app->scoped(SecurityEventLoggerInterface::class, LogSecurityEventLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        Event::listen(
            LeadSubmitted::class,
            SendLeadSubmittedNotificationListener::class,
        );

        // Register custom rate limiter for email verification resends
        RateLimiter::for('verification-resend', function ($request) {
            return Limit::perHour(3)->by($request->email . '|' . $request->ip())->response(function () {
                throw new TooManyRequestsException(
                    'You have sent too many verification email requests. Please try again in an hour.'
                );
            });
        });

        RateLimiter::for('lead-submissions', function ($request) {
            return Limit::perMinute(
                (int) config('lead.spam.throttle_max_attempts', 5),
                (int) config('lead.spam.throttle_decay_minutes', 1),
            )->by((string) $request->ip())->response(function () {
                throw new TooManyRequestsException(__('error.too_many_requests'));
            });
        });
    }
}
