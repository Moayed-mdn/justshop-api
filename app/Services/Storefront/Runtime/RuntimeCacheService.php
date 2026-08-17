<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use App\Models\Store;
use Closure;
use Illuminate\Support\Facades\Cache;

class RuntimeCacheService
{
    /**
     * @var string[]
     */
    private const INVALIDATION_ARTIFACTS = ['route', 'page', 'navigation', 'theme', 'seo'];

    public function __construct(
        private readonly RuntimeLogger $runtimeLogger,
    ) {}

    /**
     * @template T
     *
     * @param Closure(): T $callback
     * @return T
     */
    public function remember(
        Store $store,
        string $locale,
        string $artifact,
        string $path,
        int $ttlSeconds,
        bool $bypass,
        Closure $callback,
    ): mixed {
        if ($bypass || $ttlSeconds <= 0) {
            $this->runtimeLogger->info('runtime.cache.bypass', [
                'artifact' => $artifact,
                'status' => 'bypassed',
                'path' => $path,
            ]);

            return $callback();
        }

        $key = $this->key($store, $locale, $artifact, $path);

        if (Cache::has($key)) {
            $this->registerKey($store, $key, $artifact);

            $this->runtimeLogger->info('runtime.cache.hit', [
                'artifact' => $artifact,
                'status' => 'success',
                'path' => $path,
            ]);

            return Cache::get($key);
        }

        $this->runtimeLogger->info('runtime.cache.miss', [
            'artifact' => $artifact,
            'status' => 'success',
            'path' => $path,
        ]);

        $value = $callback();
        Cache::put($key, $value, now()->addSeconds($ttlSeconds));
        $this->registerKey($store, $key, $artifact);

        return $value;
    }

    /**
     * @return array{key: string, artifact: string, ttlSeconds: int, tags: string[], bypassed: bool}
     */
    public function descriptor(
        Store $store,
        string $locale,
        string $artifact,
        string $path,
        int $ttlSeconds,
        bool $bypassed,
        ?string $pageId = null,
    ): array {
        return [
            'key' => $this->key($store, $locale, $artifact, $path),
            'artifact' => $artifact,
            'ttlSeconds' => $bypassed ? 0 : $ttlSeconds,
            'tags' => $this->tags($store, $locale, $artifact, $path, $pageId),
            'bypassed' => $bypassed,
        ];
    }

    /**
     * Artifacts whose underlying data actually varies by request path.
     *
     * ⚠️ PERF FIX: 'navigation' and 'theme' were previously keyed by $path
     * even though resolveNavigationDataFromDatabase()/resolveThemeDataFromDatabase()
     * never use $path — they only depend on tenant + locale. Keying by path
     * meant every distinct URL was a fresh cache miss for these two artifacts,
     * so the same navigation/theme data was recomputed from the database on
     * almost every request instead of being served from cache. 'route' and
     * 'page' genuinely differ per path and stay scoped to it.
     *
     * @var string[]
     */
    private const PATH_SCOPED_ARTIFACTS = ['route', 'page', 'seo'];

    public function key(Store $store, string $locale, string $artifact, string $path): string
    {
        $pathSegment = in_array($artifact, self::PATH_SCOPED_ARTIFACTS, true) ? $path : 'all';

        return sprintf(
            'storefront_runtime:%s:tenant:%s:locale:%s:artifact:%s:path:%s',
            (string) config('storefront_runtime.contract_version'),
            $store->slug,
            $locale,
            $artifact,
            $pathSegment,
        );
    }

    /**
     * @return string[]
     */
    public function tags(
        Store $store,
        string $locale,
        string $artifact,
        string $path,
        ?string $pageId = null,
    ): array {
        $tags = [
            'tenant:' . $store->slug,
            'locale:' . $locale,
            'artifact:' . $artifact,
        ];

        if ($pageId !== null) {
            $tags[] = 'page:' . $pageId;
        }

        $tags[] = 'path:' . $path;

        return $tags;
    }

    public function invalidateTenantArtifacts(Store $store, array $artifacts = self::INVALIDATION_ARTIFACTS): int
    {
        $registryKey = $this->registryKey($store);
        $entries = $this->registryEntries($store);
        $allowedArtifacts = array_values(array_unique(array_filter(
            $artifacts,
            static fn (mixed $artifact): bool => is_string($artifact) && $artifact !== '',
        )));

        if ($entries === [] || $allowedArtifacts === []) {
            return 0;
        }

        $invalidated = 0;
        $remaining = [];

        foreach ($entries as $cacheKey => $entry) {
            $artifact = (string) ($entry['artifact'] ?? '');

            if (!in_array($artifact, $allowedArtifacts, true)) {
                $remaining[$cacheKey] = $entry;
                continue;
            }

            Cache::forget($cacheKey);
            $invalidated++;
        }

        if ($remaining === []) {
            Cache::forget($registryKey);
        } else {
            Cache::forever($registryKey, $remaining);
        }

        if ($invalidated > 0) {
            $this->runtimeLogger->info('runtime.cache.invalidated', [
                'artifact' => 'cache',
                'status' => 'success',
                'path' => '*',
                'invalidated_count' => $invalidated,
                'invalidated_artifacts' => $allowedArtifacts,
            ]);
        }

        return $invalidated;
    }

    private function registerKey(Store $store, string $key, string $artifact): void
    {
        $entries = $this->registryEntries($store);
        $entries[$key] = [
            'key' => $key,
            'artifact' => $artifact,
        ];

        Cache::forever($this->registryKey($store), $entries);
    }

    /**
     * @return array<string, array{key: string, artifact: string}>
     */
    private function registryEntries(Store $store): array
    {
        $entries = Cache::get($this->registryKey($store), []);

        return is_array($entries) ? $entries : [];
    }

    private function registryKey(Store $store): string
    {
        return 'storefront_runtime_registry:tenant:' . $store->slug;
    }
}
