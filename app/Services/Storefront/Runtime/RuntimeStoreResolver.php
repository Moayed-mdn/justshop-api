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
        // #region debug-point A:resolver-input
        $_dbgReport = static function (string $hypothesisId, string $message, array $data = []): void {
            $envPath = '/home/leader/projects/laravel/tenant/.dbg/storefront-tenant-domain.env';
            $debugUrl = 'http://127.0.0.1:7777/event';
            $sessionId = 'storefront-tenant-domain';
            try {
                if (is_file($envPath)) {
                    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                        if (str_starts_with($line, 'DEBUG_SERVER_URL=')) {
                            $debugUrl = substr($line, strlen('DEBUG_SERVER_URL='));
                        } elseif (str_starts_with($line, 'DEBUG_SESSION_ID=')) {
                            $sessionId = substr($line, strlen('DEBUG_SESSION_ID='));
                        }
                    }
                }
                $payload = json_encode([
                    'sessionId' => $sessionId,
                    'runId' => 'pre-fix',
                    'hypothesisId' => $hypothesisId,
                    'location' => 'RuntimeStoreResolver::resolveByHost',
                    'msg' => '[DEBUG] ' . $message,
                    'data' => $data,
                    'ts' => (int) round(microtime(true) * 1000),
                ], JSON_THROW_ON_ERROR);
                @file_get_contents($debugUrl, false, stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\n",
                        'content' => $payload,
                        'timeout' => 1,
                    ],
                ]));
            } catch (\Throwable) {
            }
        };
        $_dbgReport('A', 'resolver received storefront host', [
            'host' => $host,
            'normalized_host' => $normalizedHost,
            'path' => (string) request()->query('path', '/'),
        ]);
        // #endregion

        $store = Store::query()
            ->whereRaw('LOWER(domain) = ?', [$normalizedHost])
            ->first();
        // #region debug-point A:resolver-result
        $_dbgReport('A', 'resolver queried store by domain', [
            'normalized_host' => $normalizedHost,
            'store_found' => $store instanceof Store,
            'store_id' => $store?->id,
            'store_slug' => $store?->slug,
            'store_domain' => $store?->domain,
            'store_is_active' => $store?->is_active,
            'store_status' => $store?->status?->value ?? $store?->status,
        ]);
        // #endregion

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
