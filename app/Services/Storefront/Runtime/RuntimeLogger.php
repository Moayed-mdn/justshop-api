<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Support\Facades\Log;

class RuntimeLogger
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $event, array $context = []): void
    {
        Log::info($event, $this->buildContext($event, $context));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $event, array $context = []): void
    {
        Log::error($event, $this->buildContext($event, $context));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildContext(string $event, array $context): array
    {
        $store = app()->bound('currentStore') ? app('currentStore') : null;
        $request = request();
        $startedAt = $request->attributes->get('storefront_runtime_started_at');

        if (!array_key_exists('duration_ms', $context) && is_numeric($startedAt)) {
            $context['duration_ms'] = max(
                0,
                (int) round((microtime(true) - (float) $startedAt) * 1000),
            );
        }

        return array_merge([
            'tenant_id' => $store !== null ? 'store_' . $store->id : null,
            'tenant_key' => $store?->slug,
            'locale' => app()->getLocale(),
            'path' => $context['path'] ?? ('/' . ltrim($request->path(), '/')),
            'request_id' => $request->header('X-Request-Id') ?: $this->traceContext->correlationId(),
            'runtime_version' => (string) config('storefront_runtime.contract_version'),
            'event' => $event,
        ], $context);
    }
}
