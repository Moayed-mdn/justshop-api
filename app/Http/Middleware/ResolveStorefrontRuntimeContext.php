<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\Storefront\Runtime\RuntimeContractException;
use App\Services\Storefront\Runtime\RuntimeResponseFactory;
use App\Services\Storefront\Runtime\RuntimeRolloutGate;
use App\Services\Storefront\Runtime\RuntimeStoreResolver;
use App\Support\Observability\RequestTraceContextManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ResolveStorefrontRuntimeContext
{
    public function __construct(
        private readonly RuntimeStoreResolver $storeResolver,
        private readonly RuntimeRolloutGate $rolloutGate,
        private readonly RequestTraceContextManager $traceContext,
        private readonly RuntimeResponseFactory $responseFactory,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $request->attributes->set('storefront_runtime_started_at', $start);
        
        \Log::info('[PERF-TRACE] Middleware: Entry point', [
            'ms' => 0,
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ]);

        $checkpoint = microtime(true);
        $this->guardVersion($request);
        \Log::info('[PERF-TRACE] Middleware: After guardVersion', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $checkpoint = microtime(true);
        $locale = $this->resolveLocale($request);
        \Log::info('[PERF-TRACE] Middleware: After resolveLocale', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
            'locale' => $locale,
        ]);

        $checkpoint = microtime(true);
        $path = $this->responseFactory->resolveRequestPath($request);
        \Log::info('[PERF-TRACE] Middleware: After resolveRequestPath', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
            'path' => $path,
        ]);

        $checkpoint = microtime(true);
        $store = $this->storeResolver->resolveFromRequest($request);
        \Log::info('[PERF-TRACE] Middleware: After resolveFromRequest (CRITICAL)', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
            'store_id' => $store->id,
            'store_slug' => $store->slug,
        ]);

        if (!$this->rolloutGate->isEnabled($store)) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.rollout_disabled',
                httpStatus: 403,
                message: 'The storefront runtime is not enabled for this tenant.',
                details: [
                    'tenantKey' => (string) $store->slug,
                    'rolloutMode' => (string) config('storefront_runtime.rollout.mode', 'full'),
                ],
            );
        }

        $checkpoint = microtime(true);
        \Log::info('[PERF-TRACE] Middleware: After rolloutGate check', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $checkpoint = microtime(true);
        $this->bindStoreContext($store);
        \Log::info('[PERF-TRACE] Middleware: After bindStoreContext', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $checkpoint = microtime(true);
        $this->traceContext->enrichStore(storeId: $store->id);
        \Log::info('[PERF-TRACE] Middleware: After traceContext enrichStore', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $request->attributes->set('storefront_runtime_locale', $locale);
        $request->attributes->set('storefront_runtime_path', $path);
        App::setLocale($locale);

        $checkpoint = microtime(true);
        \Log::info('[PERF-TRACE] Middleware: Before calling next()', [
            'step_ms' => 0,
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $response = $next($request);

        \Log::info('[PERF-TRACE] Middleware: After next() - Complete', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ]);

        return $response;
    }

    private function bindStoreContext(Store $store): void
    {
        app()->instance('storeId', $store->id);
        app()->instance('currentStore', $store);
    }

    private function guardVersion(Request $request): void
    {
        $version = (string) $request->header('X-Storefront-Version', '');
        $expected = (string) config('storefront_runtime.contract_version');

        if ($version === '' || $version !== $expected) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.validation_failed',
                httpStatus: 400,
                message: 'The storefront runtime request is invalid.',
                details: [
                    'headers' => [
                        'X-Storefront-Version' => ['The runtime contract version header is missing or unsupported.'],
                    ],
                ],
            );
        }
    }

    private function resolveLocale(Request $request): string
    {
        $locale = (string) ($request->query('locale')
            ?: $request->input('locale')
            ?: $request->header('X-Storefront-Locale')
            ?: app()->getLocale());

        if (!in_array($locale, (array) config('storefront_runtime.supported_locales', ['en', 'ar']), true)) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.invalid_locale',
                httpStatus: 422,
                message: 'The requested storefront locale is not supported.',
                details: ['locale' => $locale],
            );
        }

        return $locale;
    }
}
