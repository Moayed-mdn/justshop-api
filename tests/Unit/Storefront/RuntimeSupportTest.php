<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Models\User;
use App\Services\Storefront\Runtime\RuntimeCacheService;
use App\Services\Storefront\Runtime\RuntimeResponseFactory;
use App\Services\Storefront\Runtime\RuntimeRolloutGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RuntimeSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_descriptor_uses_phase_two_runtime_cache_standard(): void
    {
        $store = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'demo.justshop.test',
            'slug' => 'justshop-demo',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $descriptor = app(RuntimeCacheService::class)->descriptor(
            store: $store,
            locale: 'ar',
            artifact: 'page',
            path: '/ar/about-us-ar',
            ttlSeconds: 3600,
            bypassed: false,
            pageId: 'mkt_42',
        );

        $this->assertSame(
            'storefront_runtime:2026-05-28:tenant:justshop-demo:locale:ar:artifact:page:path:/ar/about-us-ar',
            $descriptor['key']
        );
        $this->assertSame('page', $descriptor['artifact']);
        $this->assertSame(3600, $descriptor['ttlSeconds']);
        $this->assertFalse($descriptor['bypassed']);
        $this->assertSame([
            'tenant:justshop-demo',
            'locale:ar',
            'artifact:page',
            'page:mkt_42',
            'path:/ar/about-us-ar',
        ], $descriptor['tags']);
    }

    public function test_request_context_normalizes_runtime_metadata_for_frontend_contracts(): void
    {
        $store = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'demo.justshop.test',
            'slug' => 'justshop-demo',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $context = app(RuntimeResponseFactory::class)->requestContext(
            store: $store,
            locale: 'en',
            path: 'about-us/',
            preview: false,
        );

        $this->assertSame('store_' . $store->id, $context['tenantId']);
        $this->assertSame('justshop-demo', $context['tenantKey']);
        $this->assertSame('en', $context['locale']);
        $this->assertSame('/about-us', $context['path']);
        $this->assertSame('2026-05-28', $context['runtimeVersion']);
        $this->assertFalse($context['preview']);
        $this->assertNotSame('', $context['requestId']);
    }

    public function test_runtime_rollout_gate_honors_mode_and_kill_switch(): void
    {
        $store = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'demo.justshop.test',
            'slug' => 'justshop-demo',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $gate = app(RuntimeRolloutGate::class);

        config()->set('storefront_runtime.rollout.mode', 'full');
        config()->set('storefront_runtime.rollout.kill_switch', false);
        $this->assertTrue($gate->isEnabled($store));

        config()->set('storefront_runtime.rollout.kill_switch', true);
        $this->assertFalse($gate->isEnabled($store));

        config()->set('storefront_runtime.rollout.kill_switch', false);
        config()->set('storefront_runtime.rollout.mode', 'internal');
        config()->set('storefront_runtime.rollout.internal_tenant_keys', ['justshop-demo']);
        $this->assertTrue($gate->isEnabled($store));

        config()->set('storefront_runtime.rollout.internal_tenant_keys', ['other-tenant']);
        $this->assertFalse($gate->isEnabled($store));
    }

    public function test_runtime_cache_invalidation_is_tenant_scoped(): void
    {
        $owner = User::factory()->create();
        $primaryStore = Store::factory()->create([
            'owner_id' => $owner->id,
            'domain' => 'demo.justshop.test',
            'slug' => 'justshop-demo',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);
        $secondaryStore = Store::factory()->create([
            'owner_id' => $owner->id,
            'domain' => 'second.justshop.test',
            'slug' => 'second-tenant',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $cache = app(RuntimeCacheService::class);

        $cache->remember($primaryStore, 'en', 'route', '/about-us', 300, false, fn (): array => ['path' => '/about-us']);
        $cache->remember($primaryStore, 'en', 'page', '/about-us', 300, false, fn (): array => ['id' => 'mkt_42']);
        $cache->remember($primaryStore, 'en', 'theme', '/about-us', 300, false, fn (): array => ['themeKey' => 'default-light']);
        $cache->remember($secondaryStore, 'en', 'page', '/about-us', 300, false, fn (): array => ['id' => 'mkt_99']);

        $primaryRouteKey = $cache->key($primaryStore, 'en', 'route', '/about-us');
        $primaryPageKey = $cache->key($primaryStore, 'en', 'page', '/about-us');
        $primaryThemeKey = $cache->key($primaryStore, 'en', 'theme', '/about-us');
        $secondaryPageKey = $cache->key($secondaryStore, 'en', 'page', '/about-us');

        $this->assertTrue(Cache::has($primaryRouteKey));
        $this->assertTrue(Cache::has($primaryPageKey));
        $this->assertTrue(Cache::has($primaryThemeKey));
        $this->assertTrue(Cache::has($secondaryPageKey));

        $invalidated = $cache->invalidateTenantArtifacts($primaryStore);

        $this->assertSame(3, $invalidated);
        $this->assertFalse(Cache::has($primaryRouteKey));
        $this->assertFalse(Cache::has($primaryPageKey));
        $this->assertFalse(Cache::has($primaryThemeKey));
        $this->assertTrue(Cache::has($secondaryPageKey));
    }
}
