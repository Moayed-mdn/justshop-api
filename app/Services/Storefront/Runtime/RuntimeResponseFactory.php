<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use App\Models\Store;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Http\Request;

class RuntimeResponseFactory
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
        private readonly RuntimeCacheService $cacheService,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function success(
        ?Store $store,
        string $locale,
        string $path,
        bool $preview,
        array $data,
        ?array $cache = null,
    ): array {
        $payload = [
            'requestContext' => $this->requestContext($store, $locale, $path, $preview),
            'data' => $data,
        ];

        if ($cache !== null) {
            $payload['cache'] = $cache;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function errorPayload(RuntimeContractException $exception): array
    {
        /** @var Store|null $store */
        $store = app()->bound('currentStore') ? app('currentStore') : null;
        /** @var Request $request */
        $request = request();
        $locale = (string) ($request->attributes->get('storefront_runtime_locale') ?? app()->getLocale() ?? config('app.locale', 'en'));
        $path = $this->resolveRequestPath($request);
        $preview = $request->boolean('preview') || $request->header('X-Preview-Token') !== null || $request->is('api/v1/storefront/runtime/preview/validate');

        return [
            'requestContext' => $this->requestContext($store, $locale, $path, $preview),
            'error' => [
                'code' => $exception->runtimeCode(),
                'message' => $exception->getMessage(),
                'httpStatus' => $exception->httpStatus(),
                'retryable' => $exception->retryable(),
                'details' => $exception->details(),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    public function validationErrorPayload(array $details = []): array
    {
        return $this->errorPayload(new RuntimeContractException(
            runtimeCode: 'runtime.validation_failed',
            httpStatus: 400,
            message: 'The storefront runtime request is invalid.',
            details: $details,
        ));
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    public function unexpectedErrorPayload(array $details = []): array
    {
        return $this->errorPayload(new RuntimeContractException(
            runtimeCode: 'runtime.internal_error',
            httpStatus: 500,
            message: 'The storefront runtime failed unexpectedly.',
            details: $details,
            retryable: true,
        ));
    }

    /**
     * @return array{requestId: string, tenantId: string|null, tenantKey: string|null, locale: string, path: string, runtimeVersion: string, preview: bool}
     */
    public function requestContext(?Store $store, string $locale, string $path, bool $preview): array
    {
        return [
            'requestId' => $this->requestId(),
            'tenantId' => $store !== null ? 'store_' . $store->id : null,
            'tenantKey' => $store?->slug,
            'locale' => $locale,
            'path' => $this->normalizePath($path),
            'runtimeVersion' => (string) config('storefront_runtime.contract_version'),
            'preview' => $preview,
        ];
    }

    /**
     * @return array{key: string, artifact: string, ttlSeconds: int, tags: string[], bypassed: bool}
     */
    public function cache(
        Store $store,
        string $locale,
        string $artifact,
        string $path,
        int $ttlSeconds,
        bool $bypassed,
        ?string $pageId = null,
    ): array {
        return $this->cacheService->descriptor(
            store: $store,
            locale: $locale,
            artifact: $artifact,
            path: $this->normalizePath($path),
            ttlSeconds: $ttlSeconds,
            bypassed: $bypassed,
            pageId: $pageId,
        );
    }

    public function normalizePath(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        $normalized = '/' . ltrim($trimmed, '/');
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        return $normalized !== '/' ? rtrim($normalized, '/') : '/';
    }

    public function requestId(): string
    {
        return (string) (request()->header('X-Request-Id') ?: $this->traceContext->correlationId());
    }

    public function resolveRequestPath(Request $request): string
    {
        $path = $request->attributes->get('storefront_runtime_path')
            ?? $request->query('path')
            ?? $request->input('path')
            ?? '/';

        return $this->normalizePath((string) $path);
    }
}
