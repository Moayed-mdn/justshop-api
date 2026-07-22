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
        $normalizedHost = strtolower(trim($host));

        $store = Store::query()
            ->whereRaw('LOWER(domain) = ?', [$normalizedHost])
            ->first();

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
