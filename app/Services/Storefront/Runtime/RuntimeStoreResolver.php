<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use App\Models\Store;
use Illuminate\Http\Request;

class RuntimeStoreResolver
{
    public function __construct(
        private readonly RuntimeLogger $runtimeLogger,
    ) {}

    public function resolveFromRequest(Request $request): Store
    {
        return $this->resolveByHost((string) $request->getHost());
    }

    public function resolveByHost(string $host): Store
    {
        $resolverStart = microtime(true);
        \Log::info('[PERF-TRACE] RuntimeStoreResolver: Entry', [
            'host' => $host,
            'ms' => 0,
        ]);

        $normalizedHost = strtolower(trim($host));
        
        // Debug endpoint disabled for performance
        // Original code caused 2-second delay due to 1-second timeout × 2 calls
        // #region debug-point A:resolver-input (DISABLED)
        // ... debug code removed for performance ...
        // #endregion

        \Log::info('[PERF-TRACE] RuntimeStoreResolver: After debug report (DISABLED)', [
            'step_ms' => round((microtime(true) - $resolverStart) * 1000, 2),
            'total_ms' => round((microtime(true) - $resolverStart) * 1000, 2),
        ]);

        $checkpoint = microtime(true);
        $store = Store::query()
            ->whereRaw('LOWER(domain) = ?', [$normalizedHost])
            ->first();
        \Log::info('[PERF-TRACE] RuntimeStoreResolver: After DB query', [
            'step_ms' => round((microtime(true) - $checkpoint) * 1000, 2),
            'total_ms' => round((microtime(true) - $resolverStart) * 1000, 2),
            'found' => $store instanceof Store,
        ]);
        // #region debug-point A:resolver-result (DISABLED)
        // Second debug call also removed for performance
        // #endregion
        
        \Log::info('[PERF-TRACE] RuntimeStoreResolver: After 2nd debug report (DISABLED)', [
            'step_ms' => 0,
            'total_ms' => round((microtime(true) - $resolverStart) * 1000, 2),
        ]);

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
}
