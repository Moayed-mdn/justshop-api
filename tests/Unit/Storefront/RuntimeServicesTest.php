<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Models\User;
use App\Services\Storefront\Runtime\RuntimeContractException;
use App\Services\Storefront\Runtime\RuntimePreviewTokenService;
use App\Services\Storefront\Runtime\RuntimeStoreResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuntimeServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite (used for tests) lacks the GREATEST() function that
        // StoreObserver relies on when adjusting BillingAccount store counts.
        Store::unsetEventDispatcher();
    }

    public function test_runtime_store_resolver_rejects_missing_and_inactive_hosts(): void
    {
        $resolver = app(RuntimeStoreResolver::class);

        try {
            $resolver->resolveByHost('missing.justshop.test');
            $this->fail('Expected missing tenant exception was not thrown.');
        } catch (RuntimeContractException $exception) {
            $this->assertSame('runtime.tenant_not_found', $exception->runtimeCode());
            $this->assertSame(404, $exception->httpStatus());
        }

        $store = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'inactive.justshop.test',
            'slug' => 'inactive-store',
            'status' => StoreStatusEnum::SUSPENDED,
            'is_active' => false,
        ]);

        try {
            $resolver->resolveByHost($store->domain);
            $this->fail('Expected inactive tenant exception was not thrown.');
        } catch (RuntimeContractException $exception) {
            $this->assertSame('runtime.tenant_inactive', $exception->runtimeCode());
            $this->assertSame(403, $exception->httpStatus());
        }
    }

    public function test_preview_token_service_validates_scope_and_expiry(): void
    {
        $store = Store::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'domain' => 'demo.justshop.test',
            'slug' => 'justshop-demo',
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $service = app(RuntimePreviewTokenService::class);
        $token = $service->issueToken(
            store: $store,
            pageId: 'mkt_42',
            path: '/about-us',
            locale: 'en',
            issuedBy: 'cms_admin_7',
            expiresAt: CarbonImmutable::now()->addMinutes(10),
        );

        $validated = $service->validate($store, $token, 'mkt_42', '/about-us', 'en');

        $this->assertSame('mkt_42', $validated['pageId']);
        $this->assertTrue($validated['cacheBypass']);

        $expiredToken = $service->issueToken(
            store: $store,
            pageId: 'mkt_42',
            path: '/about-us',
            locale: 'en',
            issuedBy: 'cms_admin_7',
            expiresAt: CarbonImmutable::now()->subMinute(),
        );

        try {
            $service->validate($store, $expiredToken, 'mkt_42', '/about-us', 'en');
            $this->fail('Expected expired preview exception was not thrown.');
        } catch (RuntimeContractException $exception) {
            $this->assertSame('runtime.preview_expired', $exception->runtimeCode());
        }
    }
}
