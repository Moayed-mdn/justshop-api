<?php

namespace App\Providers;

use App\Events\Lead\LeadSubmitted;
use App\Domain\Shared\Events\MerchantRegistered;
use App\Domain\Shared\Events\StoreCreated;
use App\Events\Order\OrderCancelled;
use App\Events\Order\OrderPlaced;
use App\Events\Order\OrderStatusChanged;
use App\Events\Product\ProductVariantLowStock;
use App\Events\Store\StripeConnectStatusChanged;
use App\Events\Subscription\SubscriptionActivated;
use App\Events\Subscription\SubscriptionStatusChanged;
use App\Events\Subscription\TrialStarted;
use App\Exceptions\Auth\TooManyRequestsException;
use App\Listeners\Auth\SendWelcomeEmailListener;
use App\Listeners\Lead\SendLeadSubmittedNotificationListener;
use App\Listeners\Order\SendOrderCancelledNotificationsListener;
use App\Listeners\Order\SendOrderPlacedNotificationsListener;
use App\Listeners\Order\SendOrderStatusChangedNotificationListener;
use App\Listeners\Platform\SendMerchantRegisteredNotificationListener;
use App\Listeners\Platform\SendStoreCreatedNotificationListener;
use App\Listeners\Product\SendLowStockNotificationListener;
use App\Listeners\Store\AutoAddStoreToHostsFile;
use App\Listeners\Store\BootstrapStoreListener;
use App\Listeners\Store\SendStripeConnectStatusNotificationListener;
use App\Listeners\Subscription\SendSubscriptionActivatedNotificationListener;
use App\Listeners\Subscription\SendSubscriptionStatusChangedNotificationListener;
use App\Listeners\Subscription\SendTrialStartedNotificationListener;
use App\Notifications\Channels\FcmChannel;
use App\Services\Auth\Membership\MembershipResolver;
use App\Services\Auth\Membership\PivotMembershipResolver;
use App\Support\Audit\AuditLoggerInterface;
use App\Support\Audit\DatabaseAuditLogger;
use App\Support\Observability\RequestTraceContextManager;
use App\Support\Security\LogSecurityEventLogger;
use App\Support\Security\SecurityEventLoggerInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Telescope only in local environment when package is installed
        if (
            $this->app->environment('local') &&
            class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)
        ) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->scoped(RequestTraceContextManager::class, RequestTraceContextManager::class);
        $this->app->scoped(AuditLoggerInterface::class, DatabaseAuditLogger::class);
        $this->app->scoped(SecurityEventLoggerInterface::class, LogSecurityEventLogger::class);
        $this->app->bind(MembershipResolver::class, PivotMembershipResolver::class);

        // Phase 3: Stripe Provider Binding
        $this->app->singleton(
            \App\Contracts\Billing\BillingProviderInterface::class,
            function () {
                $provider = config('app.billing_provider', env('BILLING_PROVIDER', 'stripe'));
                
                return match ($provider) {
                    'test' => new \App\Services\Billing\TestBillingProvider(),
                    'stripe' => new \App\Services\Billing\StripeProvider(),
                    default => throw new \InvalidArgumentException("Unsupported billing provider: {$provider}"),
                };
            }
        );

        // Stripe Client for enhanced checkout
        $this->app->singleton(\Stripe\StripeClient::class, function () {
            return new \Stripe\StripeClient(config('services.stripe.secret'));
        });

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

                $registry->register(
                    \App\Policies\ProductPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::MERCHANT,
                    [\App\Enums\Auth\AuthDomainEnum::MERCHANT, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: ['merchant_to_super_admin'],
                    supportOverrideRules: ['view', 'update'],
                );

                $registry->register(
                    \App\Policies\MembershipPolicy::class,
                    \App\Enums\Auth\AuthDomainEnum::MERCHANT,
                    [\App\Enums\Auth\AuthDomainEnum::MERCHANT, \App\Enums\Auth\AuthDomainEnum::PLATFORM],
                    escalationRules: ['merchant_to_super_admin'],
                    supportOverrideRules: ['view'],
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
        // Model::unguard() intentionally removed — all models use explicit $fillable arrays.
        // Mass assignment protection is active platform-wide.

        // Register Theme observer for cache invalidation
        \App\Models\Theme\Theme::observe(\App\Observers\ThemeObserver::class);

        // Register Product observer for atomic entitlement count updates
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);

        // Register Store observer for atomic entitlement count updates
        \App\Models\Store::observe(\App\Observers\StoreObserver::class);

          // Step 4 Hardening: Queue Isolation with Safety Assertions
          // Automatically clear tenant context after every job execution to prevent state leakage.
          Queue::after(function (\Illuminate\Queue\Events\JobProcessed $event) {
              $storeId = app()->bound('storeId') ? app('storeId') : 'none';
              
              app()->forgetInstance('storeId');
              app()->forgetInstance('currentStore');

              // Re-initialize RequestTraceContextManager for the next job
              app(RequestTraceContextManager::class)->reset();

              Log::info('queue.job.context_cleared', [
                  'job' => $event->job->resolveName(),
                  'cleared_store_id' => $storeId,
              ]);
          });

        Event::listen(
            LeadSubmitted::class,
            SendLeadSubmittedNotificationListener::class,
        );

        Event::listen(
            MerchantRegistered::class,
            SendWelcomeEmailListener::class,
        );

        Event::listen(
            MerchantRegistered::class,
            SendMerchantRegisteredNotificationListener::class,
        );

        Event::listen(
            StoreCreated::class,
            BootstrapStoreListener::class,
        );

        Event::listen(
            StoreCreated::class,
            AutoAddStoreToHostsFile::class,
        );

        Event::listen(
            StoreCreated::class,
            SendStoreCreatedNotificationListener::class,
        );

        // Push notification system: order, product, and store lifecycle events.
        Event::listen(
            OrderPlaced::class,
            SendOrderPlacedNotificationsListener::class,
        );

        Event::listen(
            OrderStatusChanged::class,
            SendOrderStatusChangedNotificationListener::class,
        );

        Event::listen(
            OrderCancelled::class,
            SendOrderCancelledNotificationsListener::class,
        );

        Event::listen(
            ProductVariantLowStock::class,
            SendLowStockNotificationListener::class,
        );

        Event::listen(
            StripeConnectStatusChanged::class,
            SendStripeConnectStatusNotificationListener::class,
        );

        // Push notification system: platform subscription/billing lifecycle.
        // These events were already being dispatched by real billing code
        // but had no registered listener until now — see the note on
        // SendTrialStartedNotificationListener.
        Event::listen(
            TrialStarted::class,
            SendTrialStartedNotificationListener::class,
        );

        Event::listen(
            SubscriptionActivated::class,
            SendSubscriptionActivatedNotificationListener::class,
        );

        Event::listen(
            SubscriptionStatusChanged::class,
            SendSubscriptionStatusChangedNotificationListener::class,
        );

        // Push notification system: custom FCM delivery channel.
        Notification::extend('fcm', function ($app) {
            return $app->make(FcmChannel::class);
        });

        // Register custom rate limiter for email verification resends
        RateLimiter::for('verification-resend', function ($request) {
            return Limit::perHour(3)->by($request->email . '|' . $request->ip())->response(function () {
                throw new TooManyRequestsException(
                    'You have sent too many verification email requests. Please try again in an hour.'
                );
            });
        });

        // Login brute-force protection: 5 attempts per minute per email+IP
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by(
                strtolower((string) $request->input('email')) . '|' . $request->ip()
            )->response(function () {
                throw new TooManyRequestsException(
                    __('auth.too_many_login_attempts')
                );
            });
        });

        // Storefront customer login: same protection, separate key namespace
        RateLimiter::for('customer-login', function ($request) {
            return Limit::perMinute(5)->by(
                'customer|' . strtolower((string) $request->input('email')) . '|' . $request->ip()
            )->response(function () {
                throw new TooManyRequestsException(
                    __('auth.too_many_login_attempts')
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
