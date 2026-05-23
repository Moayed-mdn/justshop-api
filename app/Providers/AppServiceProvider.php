<?php

namespace App\Providers;

use App\Events\Lead\LeadSubmitted;
use App\Exceptions\Auth\TooManyRequestsException;
use App\Listeners\Lead\SendLeadSubmittedNotificationListener;
use App\Services\Auth\Membership\MembershipResolver;
use App\Services\Auth\Membership\PivotMembershipResolver;
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
        $this->app->bind(MembershipResolver::class, PivotMembershipResolver::class);

        // Wave 6: Policy Ownership Registry — singleton so registrations persist per request
        $this->app->singleton(
            \App\Services\Authorization\PolicyOwnershipRegistry::class,
            function () {
                $registry = new \App\Services\Authorization\PolicyOwnershipRegistry();

                // Register all known policies with their ownership metadata
                $registry->register(
                    \App\Policies\StorePolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::MERCHANT,
                    [\App\Enums\Auth\AuthDomainEnum::MERCHANT, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: ['merchant_to_super_admin'],
                    supportOverrideRules: ['view', 'update'],
                );

                $registry->register(
                    \App\Policies\OrderPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::MERCHANT,
                    [\App\Enums\Auth\AuthDomainEnum::MERCHANT, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: ['merchant_to_super_admin'],
                    supportOverrideRules: ['view'],
                );

                $registry->register(
                    \App\Policies\AddressPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::CUSTOMER,
                    [\App\Enums\Auth\AuthDomainEnum::CUSTOMER, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: [],
                    supportOverrideRules: ['view'],
                );

                $registry->register(
                    \App\Policies\BrandPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::MERCHANT,
                    [\App\Enums\Auth\AuthDomainEnum::MERCHANT, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: ['merchant_to_super_admin'],
                    supportOverrideRules: [],
                );

                $registry->register(
                    \App\Policies\CategoryPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::MERCHANT,
                    [\App\Enums\Auth\AuthDomainEnum::MERCHANT, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: ['merchant_to_super_admin'],
                    supportOverrideRules: [],
                );

                $registry->register(
                    \App\Policies\TagPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::MERCHANT,
                    [\App\Enums\Auth\AuthDomainEnum::MERCHANT, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: ['merchant_to_super_admin'],
                    supportOverrideRules: [],
                );

                $registry->register(
                    \App\Policies\LeadPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::PLATFORM,
                    [\App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: [],
                    supportOverrideRules: ['view'],
                );

                $registry->register(
                    \App\Policies\BlogPostPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::PLATFORM,
                    [\App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: [],
                    supportOverrideRules: ['view'],
                );

                $registry->register(
                    \App\Policies\PaymentMethodPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::CUSTOMER,
                    [\App\Enums\Auth\AuthDomainEnum::CUSTOMER, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: [],
                    supportOverrideRules: ['view'],
                );

                $registry->register(
                    \App\Policies\DashboardPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::MERCHANT,
                    [\App\Enums\Auth\AuthDomainEnum::MERCHANT, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: ['merchant_to_super_admin'],
                    supportOverrideRules: [],
                );

                return $registry;
            }
        );
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
