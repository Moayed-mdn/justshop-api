<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Enums\Store\ProvisioningStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Services\Store\ProvisioningHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvisioningHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProvisioningHealthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite (used for tests) lacks the GREATEST() function that

        $this->service = app(ProvisioningHealthService::class);
    }

    public function test_refresh_marks_stale_running_provisioning_as_failed_and_retryable(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::PROVISIONING,
            'provisioning_status' => ProvisioningStatusEnum::RUNNING,
            'provisioning_started_at' => now()->subMinutes(30),
            // Heartbeat is 20 minutes stale, past the default 10-minute cutoff.
            'provisioning_last_heartbeat_at' => now()->subMinutes(20),
        ]);

        $result = $this->service->refresh($store);

        $this->assertSame(ProvisioningStatusEnum::FAILED, $result->provisioning_status);
        $this->assertTrue($result->provisioning_retryable);
        $this->assertFalse((bool) $result->is_active);
        $this->assertSame('bootstrap_timed_out', $result->provisioning_current_step);
        $this->assertNotNull($result->provisioning_failed_at);
    }

    public function test_refresh_preserves_active_status_when_marking_a_stale_reprovision_as_failed(): void
    {
        // A store that is already ACTIVE (e.g. re-provisioning some background
        // step) must not be knocked back to PROVISIONING when the health
        // check trips — only stores that were never active should move there.
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'provisioning_status' => ProvisioningStatusEnum::RUNNING,
            'provisioning_started_at' => now()->subMinutes(30),
            'provisioning_last_heartbeat_at' => now()->subMinutes(20),
        ]);

        $result = $this->service->refresh($store);

        $this->assertSame(StoreStatusEnum::ACTIVE, $result->status);
        $this->assertSame(ProvisioningStatusEnum::FAILED, $result->provisioning_status);
    }

    public function test_refresh_does_nothing_when_heartbeat_is_recent(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::PROVISIONING,
            'provisioning_status' => ProvisioningStatusEnum::RUNNING,
            'provisioning_started_at' => now()->subMinutes(5),
            'provisioning_last_heartbeat_at' => now()->subMinutes(1),
        ]);

        $result = $this->service->refresh($store);

        $this->assertSame(ProvisioningStatusEnum::RUNNING, $result->provisioning_status);
    }

    public function test_refresh_is_a_noop_for_already_completed_provisioning(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'provisioning_status' => ProvisioningStatusEnum::COMPLETED,
            'provisioning_started_at' => now()->subDays(30),
            'provisioning_last_heartbeat_at' => now()->subDays(30),
        ]);

        $result = $this->service->refresh($store);

        $this->assertSame(ProvisioningStatusEnum::COMPLETED, $result->provisioning_status);
        $this->assertSame(StoreStatusEnum::ACTIVE, $result->status);
    }

    public function test_refresh_is_a_noop_for_already_failed_provisioning(): void
    {
        // FAILED is not in the [PENDING, RUNNING] guard list, so a second
        // refresh() call must not re-process an already-failed store.
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::PENDING_SETUP,
            'provisioning_status' => ProvisioningStatusEnum::FAILED,
            'provisioning_started_at' => now()->subMinutes(30),
            'provisioning_last_heartbeat_at' => now()->subMinutes(20),
            'provisioning_failed_at' => now()->subMinutes(15),
        ]);

        $result = $this->service->refresh($store);

        $this->assertSame(ProvisioningStatusEnum::FAILED, $result->provisioning_status);
        $this->assertTrue($result->provisioning_failed_at->equalTo($store->provisioning_failed_at));
    }

    public function test_refresh_does_nothing_when_provisioning_never_started(): void
    {
        // Defensive guard: no provisioning_started_at/heartbeat recorded yet.
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::PENDING_SETUP,
            'provisioning_status' => ProvisioningStatusEnum::PENDING,
            'provisioning_started_at' => null,
            'provisioning_last_heartbeat_at' => null,
        ]);

        $result = $this->service->refresh($store);

        $this->assertSame(ProvisioningStatusEnum::PENDING, $result->provisioning_status);
    }

    public function test_prepare_retry_resets_a_failed_store_back_to_pending_setup(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::PROVISIONING,
            'is_active' => false,
            'provisioning_status' => ProvisioningStatusEnum::FAILED,
            'provisioning_progress' => 60,
            'provisioning_current_step' => 'bootstrap_timed_out',
            'provisioning_message' => 'Store provisioning timed out. Retry provisioning to continue setup.',
            'provisioning_retryable' => true,
            'provisioning_started_at' => now()->subHour(),
            'provisioning_last_heartbeat_at' => now()->subMinutes(50),
            'provisioning_failed_at' => now()->subMinutes(40),
            'provisioning_last_error' => 'Provisioning heartbeat exceeded timeout window.',
        ]);

        $result = $this->service->prepareRetry($store);

        $this->assertSame(StoreStatusEnum::PENDING_SETUP, $result->status);
        $this->assertSame(ProvisioningStatusEnum::PENDING, $result->provisioning_status);
        $this->assertSame(0, $result->provisioning_progress);
        $this->assertNull($result->provisioning_current_step);
        $this->assertNull($result->provisioning_message);
        $this->assertFalse($result->provisioning_retryable);
        $this->assertNull($result->provisioning_started_at);
        $this->assertNull($result->provisioning_last_heartbeat_at);
        $this->assertNull($result->provisioning_failed_at);
        $this->assertNull($result->provisioning_last_error);
    }
}
