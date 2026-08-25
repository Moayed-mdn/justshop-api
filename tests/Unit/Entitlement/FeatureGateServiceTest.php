<?php

declare(strict_types=1);

namespace Tests\Unit\Entitlement;

use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\Entitlement\FeatureKeyEnum;
use App\Exceptions\Entitlement\QuotaExceededException;
use App\Exceptions\Subscription\SubscriptionRequiredException;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Services\Entitlement\FeatureGateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for FeatureGateService — the real class behind the
 * `subscription.active` middleware (see EnsureActiveSubscription).
 *
 * These call the service directly rather than going through HTTP, since
 * the goal here is to pin down the entitlement-status decision matrix
 * itself; StoreAssetControllerTest exercises the same logic end-to-end
 * through a real gated route.
 */
class FeatureGateServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeatureGateService $service;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(FeatureGateService::class);
        $this->store = Store::factory()->create();
    }

    private function snapshot(EntitlementStatusEnum $status, array $features = []): StoreEntitlementSnapshot
    {
        return StoreEntitlementSnapshot::query()->create([
            'store_id' => $this->store->id,
            'entitlement_status' => $status,
            'features' => $features,
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);
    }

    // ── Write access ─────────────────────────────────────────────

    public function test_ensure_write_access_throws_when_store_has_no_snapshot(): void
    {
        $this->expectException(SubscriptionRequiredException::class);

        $this->service->ensureWriteAccess($this->store->id);
    }

    /**
     * @dataProvider writeGrantingStatuses
     */
    public function test_ensure_write_access_passes_for_statuses_that_grant_write(
        EntitlementStatusEnum $status
    ): void {
        $this->snapshot($status);

        $this->service->ensureWriteAccess($this->store->id);
        $this->addToAssertionCount(1); // no exception thrown = pass
    }

    public static function writeGrantingStatuses(): array
    {
        return [
            'entitled' => [EntitlementStatusEnum::ENTITLED],
            'trial' => [EntitlementStatusEnum::TRIAL],
            'grandfathered' => [EntitlementStatusEnum::GRANDFATHERED],
        ];
    }

    /**
     * @dataProvider writeBlockingStatuses
     */
    public function test_ensure_write_access_throws_for_statuses_that_block_write(
        EntitlementStatusEnum $status
    ): void {
        $this->snapshot($status);

        $this->expectException(SubscriptionRequiredException::class);
        $this->service->ensureWriteAccess($this->store->id);
    }

    public static function writeBlockingStatuses(): array
    {
        return [
            'read_only' => [EntitlementStatusEnum::READ_ONLY],
            'restricted' => [EntitlementStatusEnum::RESTRICTED],
            'none' => [EntitlementStatusEnum::NONE],
        ];
    }

    // ── Read access ──────────────────────────────────────────────

    public function test_ensure_read_access_allows_read_only_status(): void
    {
        $this->snapshot(EntitlementStatusEnum::READ_ONLY);

        $this->service->ensureReadAccess($this->store->id);
        $this->addToAssertionCount(1);
    }

    public function test_ensure_read_access_throws_for_restricted_status(): void
    {
        $this->snapshot(EntitlementStatusEnum::RESTRICTED);

        $this->expectException(SubscriptionRequiredException::class);
        $this->service->ensureReadAccess($this->store->id);
    }

    // ── Feature flags / limits ───────────────────────────────────

    public function test_has_feature_returns_true_for_enabled_boolean_feature(): void
    {
        $this->snapshot(EntitlementStatusEnum::ENTITLED, [
            FeatureKeyEnum::CUSTOM_DOMAIN->value => true,
        ]);

        $this->assertTrue($this->service->hasFeature($this->store->id, FeatureKeyEnum::CUSTOM_DOMAIN));
    }

    public function test_has_feature_returns_false_when_store_has_no_snapshot(): void
    {
        $this->assertFalse($this->service->hasFeature($this->store->id, FeatureKeyEnum::CUSTOM_DOMAIN));
    }

    public function test_get_feature_limit_returns_configured_numeric_limit(): void
    {
        $this->snapshot(EntitlementStatusEnum::ENTITLED, [
            FeatureKeyEnum::PRODUCTS_MAX->value => 50,
        ]);

        $this->assertSame(50, $this->service->getFeatureLimit($this->store->id, FeatureKeyEnum::PRODUCTS_MAX));
    }

    public function test_get_feature_limit_returns_null_when_unlimited(): void
    {
        $this->snapshot(EntitlementStatusEnum::ENTITLED, [
            FeatureKeyEnum::PRODUCTS_MAX->value => null,
        ]);

        $this->assertNull($this->service->getFeatureLimit($this->store->id, FeatureKeyEnum::PRODUCTS_MAX));
    }

    // ── Quota enforcement ────────────────────────────────────────

    public function test_ensure_quota_passes_when_under_limit(): void
    {
        StoreEntitlementSnapshot::query()->create([
            'store_id' => $this->store->id,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED,
            'features' => [FeatureKeyEnum::PRODUCTS_MAX->value => 10],
            'products_count' => 5,
            'refreshed_at' => now(),
        ]);

        $this->service->ensureQuota($this->store->id, FeatureKeyEnum::PRODUCTS_MAX);
        $this->addToAssertionCount(1);
    }

    public function test_ensure_quota_throws_when_at_limit(): void
    {
        StoreEntitlementSnapshot::query()->create([
            'store_id' => $this->store->id,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED,
            'features' => [FeatureKeyEnum::PRODUCTS_MAX->value => 10],
            'products_count' => 10,
            'refreshed_at' => now(),
        ]);

        $this->expectException(QuotaExceededException::class);
        $this->service->ensureQuota($this->store->id, FeatureKeyEnum::PRODUCTS_MAX);
    }

    public function test_ensure_quota_passes_when_limit_is_unlimited(): void
    {
        StoreEntitlementSnapshot::query()->create([
            'store_id' => $this->store->id,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED,
            'features' => [FeatureKeyEnum::PRODUCTS_MAX->value => null],
            'products_count' => 999999,
            'refreshed_at' => now(),
        ]);

        $this->service->ensureQuota($this->store->id, FeatureKeyEnum::PRODUCTS_MAX);
        $this->addToAssertionCount(1);
    }
}
