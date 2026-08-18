<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RuntimeStoreResolver
{
    /**
     * ⚠️ PERF FIX: this resolver runs on the middleware for EVERY storefront
     * runtime request (route resolve, page, navigation, theme, template,
     * section-groups — i.e. 4-6+ requests per single page view). It was
     * previously doing an uncached `whereRaw('LOWER(domain) = ?')` lookup —
     * a full table scan on every call, since `stores.domain` has no index
     * and the LOWER() wrapper would prevent a plain index from being used
     * even if one existed — plus 4 synchronous Log::info() calls per call.
     * Domain -> store mappings change essentially never, so this is an
     * ideal candidate for caching. Invalidated in StoreObserver::updated()
     * whenever a store's domain actually changes.
     */
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private readonly RuntimeLogger $runtimeLogger,
    ) {}

    public function resolveFromRequest(Request $request): Store
    {
        return $this->resolveByHost((string) $request->getHost());
    }

    public function resolveByHost(string $host): Store
    {
        $normalizedHost = strtolower(trim($host));

        $store = Cache::remember(
            $this->cacheKey($normalizedHost),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            static fn () => Store::query()
                ->whereRaw('LOWER(domain) = ?', [$normalizedHost])
                ->first(),
        );

        if (!$store instanceof Store) {
            $this->runtimeLogger->info('runtime.tenant.rejected', [
                'artifact' => 'route',
                'status' => 'failure',
                'path' => (string) request()->query('path', '/'),
                'details' => ['reason' => 'domain_not_mapped'],
            ]);

            throw new RuntimeContractException(
                runtimeCode: 'runtime.tenant_not_found',
                httpStatus: 404,
                message: 'The requested tenant could not be resolved from the storefront domain.',
                details: ['reason' => 'domain_not_mapped'],
            );
        }

        if (!$store->isOperational()) {
            app()->instance('currentStore', $store);
            $this->runtimeLogger->info('runtime.tenant.rejected', [
                'artifact' => 'route',
                'status' => 'failure',
                'details' => ['reason' => 'tenant_not_operational'],
            ]);

            throw new RuntimeContractException(
                runtimeCode: 'runtime.tenant_inactive',
                httpStatus: 403,
                message: 'The requested tenant is not active for storefront runtime delivery.',
                details: ['reason' => 'tenant_not_operational'],
            );
        }

        app()->instance('currentStore', $store);
        $this->runtimeLogger->info('runtime.tenant.resolved', [
            'artifact' => 'route',
            'status' => 'success',
        ]);

        return $store;
    }

    private function cacheKey(string $normalizedHost): string
    {
        return 'storefront_runtime:tenant_domain:' . $normalizedHost;
    }
}
