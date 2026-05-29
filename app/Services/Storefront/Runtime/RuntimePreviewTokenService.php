<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class RuntimePreviewTokenService
{
    public function issueToken(
        Store $store,
        string $pageId,
        string $path,
        string $locale,
        ?string $issuedBy = null,
        ?CarbonImmutable $expiresAt = null,
    ): string {
        $payload = [
            'token_id' => 'pvt_' . str_replace('-', '', (string) str()->uuid()),
            'tenant_id' => $store->id,
            'tenant_key' => $store->slug,
            'page_id' => $pageId,
            'path' => $this->normalizePath($path),
            'locale' => $locale,
            'issued_by' => $issuedBy,
            'expires_at' => ($expiresAt ?? CarbonImmutable::now()->addMinutes((int) config('storefront_runtime.preview.ttl_minutes', 30)))->toIso8601String(),
        ];

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public function authorize(Store $store, string $token, string $path, string $locale): array
    {
        $payload = $this->decryptPayload($token);
        $pageId = (string) ($payload['page_id'] ?? '');

        if ($pageId === '') {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.preview_invalid',
                httpStatus: 403,
                message: 'The preview token is invalid for the requested tenant and page.',
                details: [
                    'reason' => 'token_missing_page_scope',
                ],
            );
        }

        $expiresAt = $this->resolveExpiry($payload, $pageId);
        $normalizedPath = $this->normalizePath($path);
        $tokenPath = $this->normalizePath((string) ($payload['path'] ?? ''));
        $scopeMatches = (int) ($payload['tenant_id'] ?? 0) === $store->id
            && $tokenPath === $normalizedPath
            && (string) ($payload['locale'] ?? '') === $locale;

        if (!$scopeMatches) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.preview_invalid',
                httpStatus: 403,
                message: 'The preview token is invalid for the requested tenant and page.',
                details: [
                    'pageId' => $pageId,
                    'reason' => 'tenant_page_scope_mismatch',
                ],
            );
        }

        return [
            'pageId' => $pageId,
            'expiresAt' => $expiresAt->toIso8601String(),
            'cacheBypass' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(Store $store, string $token, string $pageId, string $path, string $locale): array
    {
        $authorized = $this->authorize($store, $token, $path, $locale);

        if ($authorized['pageId'] !== $pageId) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.preview_invalid',
                httpStatus: 403,
                message: 'The preview token is invalid for the requested tenant and page.',
                details: [
                    'pageId' => $pageId,
                    'reason' => 'tenant_page_scope_mismatch',
                ],
            );
        }

        return $authorized;
    }

    /**
     * @return array<string, mixed>
     */
    private function decryptPayload(string $token): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.preview_invalid',
                httpStatus: 403,
                message: 'The preview token is invalid for the requested tenant and page.',
                details: [
                    'reason' => 'token_malformed',
                ],
            );
        }

        return is_array($payload) ? $payload : [];
    }

    private function resolveExpiry(array $payload, string $pageId): CarbonImmutable
    {
        try {
            $expiresAt = CarbonImmutable::parse((string) ($payload['expires_at'] ?? ''));
        } catch (Throwable) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.preview_invalid',
                httpStatus: 403,
                message: 'The preview token is invalid for the requested tenant and page.',
                details: [
                    'pageId' => $pageId,
                    'reason' => 'token_invalid_expiry',
                ],
            );
        }

        if ($expiresAt->isPast()) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.preview_expired',
                httpStatus: 403,
                message: 'The preview token has expired for the requested tenant and page.',
                details: [
                    'pageId' => $pageId,
                    'reason' => 'expired',
                ],
            );
        }

        return $expiresAt;
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        $normalized = '/' . ltrim($trimmed, '/');
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        return $normalized !== '/' ? rtrim($normalized, '/') : '/';
    }
}
