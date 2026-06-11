<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Actions\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageAction;
use App\DTOs\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Category;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Navigation\NavigationMenu;
use App\Models\Navigation\NavigationMenuItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\Storefront\Runtime\RuntimePreviewTokenService;
use App\Services\Storefront\Runtime\StorefrontRuntimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StorefrontRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        config()->set('storefront_runtime.rollout.mode', 'full');
        config()->set('storefront_runtime.rollout.kill_switch', false);
        config()->set('storefront_runtime.rollout.internal_tenant_keys', ['justshop-demo', 'demo.justshop.test']);
        config()->set('storefront_runtime.rollout.pilot_tenant_keys', []);

        parent::tearDown();
    }

    public function test_resolve_endpoint_returns_contract_shape_for_home_marketing_category_product_redirect_and_not_found(): void
    {
        [$store, $page, $category, $product] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/'])
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'home')
            ->assertJsonPath('data.pageId', 'home')
            ->assertJsonPath('cache.key', 'storefront_runtime:2026-05-28:tenant:' . $store->slug . ':locale:en:artifact:route:path:/');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'marketing_page')
            ->assertJsonPath('data.pageId', 'mkt_' . $page->id);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/shop'])
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'shop_page')
            ->assertJsonPath('data.pageId', 'shop');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/products'])
            ->assertOk()
            ->assertJsonPath('data.status', 'redirect')
            ->assertJsonPath('data.redirectTo', '/shop');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/shop/category/shoes'])
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'category_page')
            ->assertJsonPath('data.pageId', 'cat_' . $category->id);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/shop/product/red-shoe'])
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'product_page')
            ->assertJsonPath('data.pageId', 'prd_' . $product->id);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/old-about'])
            ->assertOk()
            ->assertJsonPath('data.status', 'redirect')
            ->assertJsonPath('data.routeType', 'redirect')
            ->assertJsonPath('data.redirectTo', '/about-us')
            ->assertJsonPath('data.redirectStatus', 301);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/missing-page'])
            ->assertOk()
            ->assertJsonPath('data.status', 'not_found')
            ->assertJsonPath('data.routeType', 'marketing_page')
            ->assertJsonPath('cache.ttlSeconds', 60);
    }

    public function test_resolve_endpoint_marks_legacy_passthrough_routes_explicitly(): void
    {
        [$store] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/checkout'])
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.pageId', null)
            ->assertJsonPath('data.resourceType', 'none')
            ->assertJsonPath('data.legacyPassthrough', true)
            ->assertJsonPath('cache.artifact', 'route');
    }

    public function test_page_endpoint_returns_contract_shape_for_marketing_page_payload(): void
    {
        [$store, $page] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/page/mkt_' . $page->id, ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('requestContext.path', '/about-us')
            ->assertJsonPath('data.page.id', 'mkt_' . $page->id)
            ->assertJsonPath('data.page.pageType', 'marketing_page')
            ->assertJsonPath('data.page.layout', 'marketing')
            ->assertJsonPath('data.page.sections.0.type', 'hero_banner')
            ->assertJsonPath('data.page.sections.0.component', 'HeroSection')
            ->assertJsonPath('data.page.seo.canonicalUrl', 'https://' . $store->domain . '/about-us')
            ->assertJsonPath('cache.artifact', 'page')
            ->assertJsonPath('cache.tags.3', 'page:mkt_' . $page->id)
            ->assertJsonPath('cache.tags.4', 'path:/about-us');
    }

    public function test_category_page_payload_includes_product_grid_for_branch_categories(): void
    {
        [$store, $page, $category, $product] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/page/cat_' . $category->id, ['path' => '/products/category/shoes'])
            ->assertOk()
            ->assertJsonPath('data.page.pageType', 'category_page')
            ->assertJsonPath('data.page.sections.1.type', 'product_grid')
            ->assertJsonPath('data.page.sections.1.component', 'ProductGridSection')
            ->assertJsonPath('data.page.sections.1.props.products.0.slug', 'red-shoe')
            ->assertJsonPath('data.page.sections.1.props.products.0.name', 'Red Shoe');
    }

    public function test_resolve_endpoint_supports_locale_prefixed_arabic_paths(): void
    {
        [$store, $page, $category, $product] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/ar', 'locale' => 'ar'], 'ar')
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'home')
            ->assertJsonPath('data.path', '/ar')
            ->assertJsonPath('requestContext.locale', 'ar');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/ar/about-us-ar', 'locale' => 'ar'], 'ar')
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'marketing_page')
            ->assertJsonPath('data.pageId', 'mkt_' . $page->id);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/ar/shop/category/shoes-ar', 'locale' => 'ar'], 'ar')
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'category_page')
            ->assertJsonPath('data.pageId', 'cat_' . $category->id);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/ar/shop/product/red-shoe-ar', 'locale' => 'ar'], 'ar')
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.routeType', 'product_page')
            ->assertJsonPath('data.pageId', 'prd_' . $product->id);
    }

    public function test_navigation_and_theme_endpoints_are_tenant_scoped_and_locale_aware(): void
    {
        [$store] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/navigation', ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('data.header.0.id', 'nav_home')
            ->assertJsonPath('data.footer.0.id', 'nav_footer_about')
            ->assertJsonPath('cache.artifact', 'navigation');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/theme', ['path' => '/about-us'], 'ar')
            ->assertOk()
            ->assertJsonPath('requestContext.locale', 'ar')
            ->assertJsonPath('data.settings.direction', 'rtl')
            ->assertJsonPath('cache.artifact', 'theme');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/theme', ['path' => '/'])
            ->assertOk()
            ->assertJsonPath('data.branding.storeName', $store->name)
            ->assertJsonPath('data.branding.tagline', 'Electronics, fashion, and home essentials — curated for everyday shopping.');
    }

    public function test_navigation_endpoint_uses_database_main_and_footer_menus_when_present(): void
    {
        [$store] = $this->seedRuntimeCatalog();

        $mainMenu = NavigationMenu::query()->create([
            'store_id' => $store->id,
            'name' => 'Main Menu',
            'handle' => 'main-menu',
            'description' => 'Primary navigation',
            'is_active' => true,
        ]);

        $footerMenu = NavigationMenu::query()->create([
            'store_id' => $store->id,
            'name' => 'Footer Menu',
            'handle' => 'footer-menu',
            'description' => 'Footer navigation',
            'is_active' => true,
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $mainMenu->id,
            'label' => json_encode(['en' => 'Home DB', 'ar' => 'الرئيسية من قاعدة البيانات'], JSON_UNESCAPED_UNICODE),
            'type' => 'custom',
            'url' => '/db-home',
            'target' => '_self',
            'position' => 0,
            'is_active' => true,
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $footerMenu->id,
            'label' => json_encode(['en' => 'About DB', 'ar' => 'من نحن من قاعدة البيانات'], JSON_UNESCAPED_UNICODE),
            'type' => 'custom',
            'url' => '/db-about',
            'target' => '_self',
            'position' => 0,
            'is_active' => true,
        ]);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/navigation', ['path' => '/'])
            ->assertOk()
            ->assertJsonPath('data.header.0.label', 'Home DB')
            ->assertJsonPath('data.header.0.path', '/db-home')
            ->assertJsonPath('data.header.0.external', false)
            ->assertJsonPath('data.footer.0.label', 'About DB')
            ->assertJsonPath('data.footer.0.path', '/db-about')
            ->assertJsonPath('data.footer.0.external', false);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/navigation', ['path' => '/ar'], 'ar')
            ->assertOk()
            ->assertJsonPath('data.header.0.label', 'الرئيسية من قاعدة البيانات')
            ->assertJsonPath('data.footer.0.label', 'من نحن من قاعدة البيانات');
    }

    public function test_preview_validation_and_preview_page_fetch_work_with_cache_bypass(): void
    {
        [$store, $page] = $this->seedRuntimeCatalog(pageStatus: MarketingPageStatusEnum::DRAFT);
        $pageId = 'mkt_' . $page->id;
        $path = '/about-us';
        $token = app(RuntimePreviewTokenService::class)->issueToken($store, $pageId, $path, 'en', 'cms_admin_7');

        $this->runtimePost($store, '/api/v1/storefront/runtime/preview/validate', [
            'token' => $token,
            'pageId' => $pageId,
            'path' => $path,
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.previewState', 'authorized')
            ->assertJsonPath('data.pageId', $pageId)
            ->assertJsonPath('data.cacheBypass', true);

        $this->runtimeGet(
            $store,
            '/api/v1/storefront/runtime/page/' . $pageId,
            ['preview' => 1, 'path' => $path],
            'en',
            ['X-Preview-Token' => $token],
        )
            ->assertOk()
            ->assertJsonPath('requestContext.preview', true)
            ->assertJsonPath('data.page.status', 'draft')
            ->assertJsonPath('cache.bypassed', true)
            ->assertJsonPath('cache.ttlSeconds', 0);
    }

    public function test_preview_route_resolution_and_runtime_shell_artifacts_bypass_shared_cache(): void
    {
        [$store, $page] = $this->seedRuntimeCatalog(pageStatus: MarketingPageStatusEnum::DRAFT);
        $pageId = 'mkt_' . $page->id;
        $path = '/about-us';
        $token = app(RuntimePreviewTokenService::class)->issueToken($store, $pageId, $path, 'en', 'cms_admin_7');

        $this->runtimeGet(
            $store,
            '/api/v1/storefront/runtime/resolve',
            ['path' => $path],
            'en',
            ['X-Preview-Token' => $token],
        )
            ->assertOk()
            ->assertJsonPath('requestContext.preview', true)
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.pageId', $pageId)
            ->assertJsonPath('cache.bypassed', true)
            ->assertJsonPath('cache.ttlSeconds', 0);

        $this->runtimeGet(
            $store,
            '/api/v1/storefront/runtime/navigation',
            ['path' => $path],
            'en',
            ['X-Preview-Token' => $token],
        )
            ->assertOk()
            ->assertJsonPath('requestContext.preview', true)
            ->assertJsonPath('cache.artifact', 'navigation')
            ->assertJsonPath('cache.bypassed', true)
            ->assertJsonPath('cache.ttlSeconds', 0);

        $this->runtimeGet(
            $store,
            '/api/v1/storefront/runtime/theme',
            ['path' => $path],
            'en',
            ['X-Preview-Token' => $token],
        )
            ->assertOk()
            ->assertJsonPath('requestContext.preview', true)
            ->assertJsonPath('cache.artifact', 'theme')
            ->assertJsonPath('cache.bypassed', true)
            ->assertJsonPath('cache.ttlSeconds', 0);
    }

    public function test_preview_route_resolution_rejects_cross_tenant_replay(): void
    {
        [$store, $page] = $this->seedRuntimeCatalog(pageStatus: MarketingPageStatusEnum::DRAFT);
        $pageId = 'mkt_' . $page->id;
        $path = '/about-us';
        $token = app(RuntimePreviewTokenService::class)->issueToken($store, $pageId, $path, 'en', 'cms_admin_7');

        $otherStore = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'other.justshop.test',
            'slug' => 'other-tenant',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $this->runtimeGetRaw(
            $otherStore->domain,
            '/api/v1/storefront/runtime/resolve',
            ['path' => $path],
            'en',
            ['X-Preview-Token' => $token],
        )
            ->assertForbidden()
            ->assertJsonPath('error.code', 'runtime.preview_invalid');
    }

    public function test_store_marketing_page_updates_invalidate_cached_runtime_route_page_navigation_and_seo_data(): void
    {
        [$store, $page] = $this->seedRuntimeCatalog();
        $pageId = 'mkt_' . $page->id;

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('data.pageId', $pageId);
        $this->runtimeGet($store, '/api/v1/storefront/runtime/page/' . $pageId, ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('data.page.seo.canonicalUrl', 'https://' . $store->domain . '/about-us');
        $this->runtimeGet($store, '/api/v1/storefront/runtime/navigation', ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('data.footer.0.path', '/about-us');

        $page->loadMissing('sections');

        app(UpdateStoreMarketingPageAction::class)->execute(new UpdateStoreMarketingPageDTO(
            id: (int) $page->id,
            storeId: (int) $store->id,
            title: ['en' => 'Company', 'ar' => 'Company AR'],
            slug: ['en' => 'company', 'ar' => 'company-ar'],
            excerpt: is_array($page->excerpt) ? $page->excerpt : null,
            content: is_array($page->content) ? $page->content : null,
            status: MarketingPageStatusEnum::PUBLISHED,
            publishedAt: $page->published_at?->toDateTimeString(),
            seo: is_array($page->seo) ? $page->seo : null,
            template: $page->template,
            sortOrder: (int) $page->sort_order,
            sections: $page->sections->map(static fn ($section): array => [
                'section_type' => (string) $section->section_type,
                'identifier' => (string) $section->identifier,
                'sort_order' => (int) $section->sort_order,
                'title' => $section->title,
                'subtitle' => $section->subtitle,
                'content' => $section->content,
                'settings' => $section->settings,
                'is_active' => (bool) $section->is_active,
            ])->all(),
            updatedBy: (int) $store->owner_id,
        ));

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('data.status', 'not_found');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/company'])
            ->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.pageId', $pageId);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/page/' . $pageId, ['path' => '/company'])
            ->assertOk()
            ->assertJsonPath('data.page.title', 'Company')
            ->assertJsonPath('data.page.seo.canonicalUrl', 'https://' . $store->domain . '/company');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/navigation', ['path' => '/company'])
            ->assertOk()
            ->assertJsonPath('data.footer.0.path', '/company');
    }

    public function test_runtime_negative_paths_are_normalized(): void
    {
        [$store, $page] = $this->seedRuntimeCatalog();

        $this->runtimeGetRaw('missing.justshop.test', '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'runtime.tenant_not_found');

        $inactiveStore = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'inactive.justshop.test',
            'slug' => 'inactive-tenant',
            'status' => StoreStatusEnum::SUSPENDED,
            'is_active' => false,
        ]);

        $this->runtimeGetRaw($inactiveStore->domain, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'runtime.tenant_inactive');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'], 'fr')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'runtime.invalid_locale');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/page/mkt_999999', ['path' => '/about-us'])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'runtime.page_not_found');

        $this->runtimePost($store, '/api/v1/storefront/runtime/preview/validate', [
            'token' => 'invalid-token',
            'pageId' => 'mkt_' . $page->id,
            'path' => '/about-us',
            'locale' => 'en',
        ])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'runtime.preview_invalid');
    }

    public function test_runtime_requests_require_supported_contract_version_header(): void
    {
        [$store] = $this->seedRuntimeCatalog();

        $this->withHeaders([
            'Host' => $store->domain,
            'X-Storefront-Locale' => 'en',
            'X-Request-Id' => 'req_missing_runtime_version',
        ])->getJson($this->absoluteRuntimeUrl($store->domain, '/api/v1/storefront/runtime/resolve', [
            'path' => '/about-us',
        ]))
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'runtime.validation_failed')
            ->assertJsonPath(
                'error.details.headers.X-Storefront-Version.0',
                'The runtime contract version header is missing or unsupported.'
            );
    }

    public function test_unexpected_runtime_failures_are_normalized_to_runtime_internal_error(): void
    {
        [$store] = $this->seedRuntimeCatalog();

        $this->mock(StorefrontRuntimeService::class, function ($mock): void {
            $mock->shouldReceive('navigationPayload')
                ->once()
                ->andThrow(new \RuntimeException('boom'));
        });

        $this->runtimeGet($store, '/api/v1/storefront/runtime/navigation')
            ->assertStatus(500)
            ->assertJsonPath('error.code', 'runtime.internal_error')
            ->assertJsonPath('error.httpStatus', 500)
            ->assertJsonPath('error.retryable', true);
    }

    public function test_runtime_logs_include_required_observability_fields(): void
    {
        Log::spy();

        [$store] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk();

        Log::shouldHaveReceived('info')->withArgs(function (string $event, array $context) use ($store): bool {
            return $event === 'runtime.route.resolved'
                && ($context['tenant_id'] ?? null) === 'store_' . $store->id
                && ($context['tenant_key'] ?? null) === $store->slug
                && ($context['locale'] ?? null) === 'en'
                && ($context['path'] ?? null) === '/about-us'
                && ($context['request_id'] ?? null) === 'req_test_storefront_runtime'
                && ($context['runtime_version'] ?? null) === '2026-05-28'
                && ($context['artifact'] ?? null) === 'route'
                && ($context['status'] ?? null) === 'success'
                && ($context['event'] ?? null) === 'runtime.route.resolved'
                && array_key_exists('duration_ms', $context);
        })->once();
    }

    public function test_runtime_route_resolution_and_page_payload_stay_within_local_baseline(): void
    {
        [$store, $page] = $this->seedRuntimeCatalog();

        $resolveStartedAt = microtime(true);
        for ($i = 0; $i < 5; $i++) {
            $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
                ->assertOk();
        }
        $resolveDurationMs = (int) round((microtime(true) - $resolveStartedAt) * 1000);

        $pageStartedAt = microtime(true);
        for ($i = 0; $i < 5; $i++) {
            $this->runtimeGet($store, '/api/v1/storefront/runtime/page/mkt_' . $page->id, ['path' => '/about-us'])
                ->assertOk();
        }
        $pageDurationMs = (int) round((microtime(true) - $pageStartedAt) * 1000);

        $this->assertLessThan(
            5000,
            $resolveDurationMs,
            'Route resolution baseline exceeded 5000ms for 5 repeated requests.'
        );
        $this->assertLessThan(
            5000,
            $pageDurationMs,
            'Page payload baseline exceeded 5000ms for 5 repeated requests.'
        );
    }

    public function test_runtime_responses_echo_submitted_request_id(): void
    {
        [$store] = $this->seedRuntimeCatalog();
        $requestId = 'req_phase6_correlation_check';

        $this->runtimeGet(
            $store,
            '/api/v1/storefront/runtime/resolve',
            ['path' => '/about-us'],
            'en',
            ['X-Request-Id' => $requestId],
        )
            ->assertOk()
            ->assertJsonPath('requestContext.requestId', $requestId);
    }

    public function test_runtime_page_payload_rejects_cross_tenant_resource_access(): void
    {
        [$store, $page, $category, $product] = $this->seedRuntimeCatalog();

        $otherStore = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'other.justshop.test',
            'slug' => 'other-tenant',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $this->runtimeGetRaw(
            $otherStore->domain,
            '/api/v1/storefront/runtime/page/mkt_' . $page->id,
            ['path' => '/about-us'],
        )
            ->assertNotFound()
            ->assertJsonPath('error.code', 'runtime.page_not_found');

        $this->runtimeGetRaw(
            $otherStore->domain,
            '/api/v1/storefront/runtime/page/cat_' . $category->id,
            ['path' => '/products/category/shoes'],
        )
            ->assertNotFound()
            ->assertJsonPath('error.code', 'runtime.page_not_found');

        $this->runtimeGetRaw(
            $otherStore->domain,
            '/api/v1/storefront/runtime/page/prd_' . $product->id,
            ['path' => '/products/red-shoe'],
        )
            ->assertNotFound()
            ->assertJsonPath('error.code', 'runtime.page_not_found');

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('cache.key', 'storefront_runtime:2026-05-28:tenant:' . $store->slug . ':locale:en:artifact:route:path:/about-us');

        $this->runtimeGetRaw($otherStore->domain, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('cache.key', 'storefront_runtime:2026-05-28:tenant:' . $otherStore->slug . ':locale:en:artifact:route:path:/about-us');
    }

    public function test_runtime_rollout_gate_blocks_non_allowlisted_tenants_in_internal_mode(): void
    {
        config()->set('storefront_runtime.rollout.mode', 'internal');
        config()->set('storefront_runtime.rollout.kill_switch', false);
        config()->set('storefront_runtime.rollout.internal_tenant_keys', ['justshop-demo']);

        [$store] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk();

        $otherStore = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'pilot.justshop.test',
            'slug' => 'pilot-tenant',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $this->runtimeGetRaw($otherStore->domain, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'runtime.rollout_disabled');
    }

    public function test_runtime_kill_switch_disables_runtime_for_all_tenants(): void
    {
        config()->set('storefront_runtime.rollout.mode', 'full');
        config()->set('storefront_runtime.rollout.kill_switch', true);

        [$store] = $this->seedRuntimeCatalog();

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'runtime.rollout_disabled');
    }

    public function test_runtime_pilot_mode_allows_internal_and_pilot_tenant_keys(): void
    {
        config()->set('storefront_runtime.rollout.mode', 'pilot');
        config()->set('storefront_runtime.rollout.kill_switch', false);
        config()->set('storefront_runtime.rollout.internal_tenant_keys', ['justshop-demo']);
        config()->set('storefront_runtime.rollout.pilot_tenant_keys', ['pilot-tenant']);

        [$store] = $this->seedRuntimeCatalog();

        $pilotStore = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'pilot.justshop.test',
            'slug' => 'pilot-tenant',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $this->runtimeGet($store, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk();

        $this->runtimeGetRaw($pilotStore->domain, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertOk()
            ->assertJsonPath('data.status', 'not_found');

        $blockedStore = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'blocked.justshop.test',
            'slug' => 'blocked-tenant',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $this->runtimeGetRaw($blockedStore->domain, '/api/v1/storefront/runtime/resolve', ['path' => '/about-us'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'runtime.rollout_disabled');
    }

    public function test_runtime_seo_contract_is_complete_for_supported_page_types(): void
    {
        [$store, $page, $category, $product] = $this->seedRuntimeCatalog();

        $cases = [
            ['pageId' => 'home', 'path' => '/', 'canonical' => 'https://' . $store->domain . '/'],
            ['pageId' => 'mkt_' . $page->id, 'path' => '/about-us', 'canonical' => 'https://' . $store->domain . '/about-us'],
            ['pageId' => 'cat_' . $category->id, 'path' => '/shop/category/shoes', 'canonical' => 'https://' . $store->domain . '/shop/category/shoes'],
            ['pageId' => 'prd_' . $product->id, 'path' => '/shop/product/red-shoe', 'canonical' => 'https://' . $store->domain . '/shop/product/red-shoe'],
        ];

        foreach ($cases as $case) {
            $response = $this->runtimeGet(
                $store,
                '/api/v1/storefront/runtime/page/' . $case['pageId'],
                ['path' => $case['path']],
            )->assertOk();

            $seo = $response->json('data.page.seo');

            $this->assertIsArray($seo);
            $this->assertNotSame('', (string) ($seo['title'] ?? ''));
            $this->assertNotSame('', (string) ($seo['description'] ?? ''));
            $this->assertSame($case['canonical'], $seo['canonicalUrl'] ?? null);
            $this->assertContains($seo['robots'] ?? null, ['index,follow', 'noindex,nofollow', 'noindex,follow']);
            $this->assertIsArray($seo['hreflang'] ?? null);
            $this->assertNotEmpty($seo['hreflang']);
            $this->assertNotSame('', (string) data_get($seo, 'openGraph.title'));
            $this->assertNotSame('', (string) data_get($seo, 'openGraph.description'));
            $this->assertContains(data_get($seo, 'openGraph.type'), ['website', 'product']);
            $this->assertNotSame('', (string) data_get($seo, 'twitter.card'));
            $this->assertNotSame('', (string) data_get($seo, 'twitter.title'));
            $this->assertNotSame('', (string) data_get($seo, 'twitter.description'));
            $this->assertIsArray($seo['jsonLd'] ?? null);
            $this->assertNotEmpty($seo['jsonLd']);

            foreach ($seo['hreflang'] as $alternate) {
                $this->assertStringStartsWith('https://' . $store->domain, (string) ($alternate['url'] ?? ''));
            }
        }
    }

    /**
     * @return array{0: Store, 1: StoreMarketingPage, 2: Category, 3: Product}
     */
    private function seedRuntimeCatalog(MarketingPageStatusEnum $pageStatus = MarketingPageStatusEnum::PUBLISHED): array
    {
        $owner = User::factory()->create();
        $store = Store::factory()->create([
            'owner_id' => $owner->id,
            'domain' => 'demo.justshop.test',
            'slug' => 'justshop-demo',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'store_id' => $store->id,
            'slug' => 'shoes',
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $category->translations()->createMany([
            ['locale' => 'en', 'name' => 'Shoes', 'slug' => 'shoes'],
            ['locale' => 'ar', 'name' => 'Shoes AR', 'slug' => 'shoes-ar'],
        ]);

        $product = Product::query()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'brand_id' => null,
            'product_variant_id' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);
        $product->translations()->createMany([
            ['locale' => 'en', 'name' => 'Red Shoe', 'description' => 'A red running shoe.', 'slug' => 'red-shoe', 'seo_title' => 'Red Shoe', 'seo_description' => 'A red running shoe.'],
            ['locale' => 'ar', 'name' => 'Red Shoe AR', 'description' => 'A red running shoe AR.', 'slug' => 'red-shoe-ar', 'seo_title' => 'Red Shoe AR', 'seo_description' => 'A red running shoe AR.'],
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-RED-SHOE',
            'price' => 99.95,
            'quantity' => 8,
            'track_inventory' => true,
            'is_active' => true,
        ]);
        $product->update(['product_variant_id' => $variant->id]);

        $page = StoreMarketingPage::query()->create([
            'store_id' => $store->id,
            'title' => ['en' => 'About Us', 'ar' => 'About Us AR'],
            'slug' => ['en' => 'about-us', 'ar' => 'about-us-ar'],
            'excerpt' => ['en' => 'About JustShop', 'ar' => 'About JustShop AR'],
            'content' => [
                [
                    'id' => 'hero_about',
                    'type' => 'hero',
                    'props' => [
                        'headline' => 'Built for modern merchants',
                        'subheadline' => 'Launch and scale a tenant storefront from Laravel-managed content.',
                    ],
                ],
            ],
            'status' => $pageStatus,
            'published_at' => $pageStatus === MarketingPageStatusEnum::PUBLISHED ? now()->subDay() : null,
            'seo' => [
                'meta_title' => ['en' => 'About Us', 'ar' => 'About Us AR'],
                'meta_description' => ['en' => 'Learn how JustShop delivers tenant-aware storefront experiences.', 'ar' => 'Learn how JustShop delivers tenant-aware storefront experiences AR.'],
                'robots' => 'index,follow',
                'twitter_card' => 'summary_large_image',
                'structured_data' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                ],
            ],
            'template' => MarketingPageTemplateEnum::GENERIC,
            'sort_order' => 0,
        ]);
        $page->sections()->create([
            'store_id' => $store->id,
            'section_type' => 'hero',
            'identifier' => 'hero_about',
            'sort_order' => 0,
            'title' => ['en' => 'Built for modern merchants', 'ar' => 'Built for modern merchants AR'],
            'subtitle' => ['en' => 'Launch and scale a tenant storefront from Laravel-managed content.', 'ar' => 'Launch and scale a tenant storefront from Laravel-managed content AR.'],
            'content' => ['en' => ['headline' => 'Built for modern merchants'], 'ar' => ['headline' => 'Built for modern merchants AR']],
            'settings' => ['layout' => 'full'],
            'is_active' => true,
        ]);

        return [$store, $page, $category, $product];
    }

    private function runtimeGet(Store $store, string $uri, array $query = [], string $locale = 'en', array $extraHeaders = [])
    {
        return $this->withHeaders($this->runtimeHeaders($store->domain, $locale, $extraHeaders))
            ->getJson($this->absoluteRuntimeUrl($store->domain, $uri, $query));
    }

    private function runtimeGetRaw(string $host, string $uri, array $query = [], string $locale = 'en', array $extraHeaders = [])
    {
        return $this->withHeaders($this->runtimeHeaders($host, $locale, $extraHeaders))
            ->getJson($this->absoluteRuntimeUrl($host, $uri, $query));
    }

    private function runtimePost(Store $store, string $uri, array $payload, string $locale = 'en', array $extraHeaders = [])
    {
        return $this->withHeaders($this->runtimeHeaders($store->domain, $locale, $extraHeaders))
            ->postJson($this->absoluteRuntimeUrl($store->domain, $uri), $payload);
    }

    /**
     * @return array<string, string>
     */
    private function runtimeHeaders(string $host, string $locale = 'en', array $extraHeaders = []): array
    {
        return array_merge([
            'Host' => $host,
            'X-Storefront-Version' => '2026-05-28',
            'X-Storefront-Locale' => $locale,
            'X-Request-Id' => 'req_test_storefront_runtime',
        ], $extraHeaders);
    }

    private function absoluteRuntimeUrl(string $host, string $uri, array $query = []): string
    {
        return 'http://' . $host . $uri . ($query === [] ? '' : '?' . http_build_query($query));
    }
}
