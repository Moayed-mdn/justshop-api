<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Exceptions\Domain\InvalidStoreLifecycleTransitionException;
use App\Models\Store;
use App\Services\Store\StoreLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private StoreLifecycleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite (used for tests) lacks the GREATEST() function that
        // StoreObserver relies on when adjusting BillingAccount store counts.
        Store::unsetEventDispatcher();

        $this->service = app(StoreLifecycleService::class);
    }

    public function test_transitions_from_pending_setup_to_provisioning(): void
    {
        $store = Store::factory()->create(['status' => StoreStatusEnum::PENDING_SETUP]);

        $this->service->transition($store, StoreStatusEnum::PROVISIONING, 1, ActorContextEnum::PLATFORM_SYSTEM);

        $store->refresh();
        $this->assertSame(StoreStatusEnum::PROVISIONING, $store->status);
    }

    public function test_transitions_from_provisioning_to_active_and_syncs_is_active_flag(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::PROVISIONING,
            'is_active' => false,
        ]);

        $this->service->transition($store, StoreStatusEnum::ACTIVE, 1, ActorContextEnum::PLATFORM_SYSTEM);

        $store->refresh();
        $this->assertSame(StoreStatusEnum::ACTIVE, $store->status);
        $this->assertTrue($store->is_active);
    }

    public function test_suspend_transitions_active_store_to_suspended_and_clears_is_active(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $this->service->suspend($store, 42, ActorContextEnum::SUPER_ADMIN, 'Chargeback threshold exceeded');

        $store->refresh();
        $this->assertSame(StoreStatusEnum::SUSPENDED, $store->status);
        $this->assertFalse($store->is_active);
        $this->assertSame(42, $store->status_changed_by_actor_id);
        $this->assertSame(ActorContextEnum::SUPER_ADMIN->value, $store->status_changed_by_actor_type);
    }

    public function test_reactivate_transitions_suspended_store_back_to_active(): void
    {
        $store = Store::factory()->create(['status' => StoreStatusEnum::SUSPENDED]);

        $this->service->reactivate($store, 7, ActorContextEnum::SUPPORT_AGENT, 'Billing dispute resolved');

        $store->refresh();
        $this->assertSame(StoreStatusEnum::ACTIVE, $store->status);
        $this->assertTrue($store->is_active);
    }

    public function test_archive_transitions_active_store_to_archived(): void
    {
        $store = Store::factory()->create(['status' => StoreStatusEnum::ACTIVE]);

        $this->service->archive($store, $store->owner_id, ActorContextEnum::MERCHANT, 'Owner closed the shop');

        $store->refresh();
        $this->assertSame(StoreStatusEnum::ARCHIVED, $store->status);
        $this->assertFalse($store->is_active);
    }

    public function test_mark_for_deletion_transitions_archived_store_to_deleted_pending(): void
    {
        $store = Store::factory()->create(['status' => StoreStatusEnum::ARCHIVED]);

        $this->service->markForDeletion($store, 1, ActorContextEnum::PLATFORM_SYSTEM, 'Grace period elapsed');

        $store->refresh();
        $this->assertSame(StoreStatusEnum::DELETED_PENDING, $store->status);
    }

    public function test_transition_to_current_status_is_an_idempotent_noop(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'status_changed_by_actor_id' => null,
        ]);

        // No exception, and no lifecycle side effects (actor fields untouched).
        $this->service->transition($store, StoreStatusEnum::ACTIVE, 999, ActorContextEnum::SUPER_ADMIN);

        $store->refresh();
        $this->assertSame(StoreStatusEnum::ACTIVE, $store->status);
        $this->assertNull($store->status_changed_by_actor_id);
    }

    public function test_rejects_transition_that_skips_the_fsm_path(): void
    {
        // pending_setup can only reach 'provisioning' next, never 'active' directly.
        $store = Store::factory()->create(['status' => StoreStatusEnum::PENDING_SETUP]);

        $this->expectException(InvalidStoreLifecycleTransitionException::class);

        $this->service->transition($store, StoreStatusEnum::ACTIVE, 1, ActorContextEnum::PLATFORM_SYSTEM);
    }

    public function test_rejects_transition_out_of_the_deleted_pending_terminal_state(): void
    {
        $store = Store::factory()->create(['status' => StoreStatusEnum::DELETED_PENDING]);

        $this->expectException(InvalidStoreLifecycleTransitionException::class);

        $this->service->reactivate($store, 1, ActorContextEnum::SUPER_ADMIN, 'Attempted resurrection');

        $store->refresh();
        $this->assertSame(StoreStatusEnum::DELETED_PENDING, $store->status);
    }

    public function test_rejects_archiving_a_suspended_store_directly_to_deleted_without_grace_transition(): void
    {
        // suspended -> deleted_pending is not an allowed direct edge (must go
        // through active/archived first per StoreStatusEnum::allowedTransitions()).
        $store = Store::factory()->create(['status' => StoreStatusEnum::SUSPENDED]);

        $this->expectException(InvalidStoreLifecycleTransitionException::class);

        $this->service->markForDeletion($store, 1, ActorContextEnum::SUPER_ADMIN, 'Force delete attempt');
    }

    public function test_disabled_store_can_only_transition_back_to_active(): void
    {
        $store = Store::factory()->create(['status' => StoreStatusEnum::DISABLED]);

        $this->expectException(InvalidStoreLifecycleTransitionException::class);

        $this->service->archive($store, 1, ActorContextEnum::SUPER_ADMIN, 'Invalid path');
    }
}
