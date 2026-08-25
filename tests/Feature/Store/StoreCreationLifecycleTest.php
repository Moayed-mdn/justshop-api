<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\Store\ProvisioningStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Jobs\Store\BootstrapStoreJob;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\OnboardingTransitionService;
use App\Services\Store\StoreInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StoreCreationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite (used for tests) does not support the GREATEST() SQL function
        // that StoreObserver uses when recalculating BillingAccount store counts.
        // Disabling the model event dispatcher avoids that unsupported-function
        // error without changing the assertions below, which do not depend on
        // the observer's side effects.
        Store::unsetEventDispatcher();
    }

    public function test_first_store_creation_moves_onboarding_to_store_created(): void
    {
        /** @var User $user */
        $user = User::factory()->createStoreStep()->create();
        $this->actingAs($user);
        Queue::fake();

        $payload = [
            'name' => 'My First Store',
            'slug' => 'my-first-store',
        ];

        $response = $this->postJson('/api/v1/stores', $payload);

        $response->assertStatus(201);

        $user->refresh();
        $this->assertEquals(OnboardingStepEnum::STORE_CREATED, $user->onboarding_step);
        $this->assertNotNull($user->last_active_store_id);
    }

    public function test_bootstrap_store_job_completes_onboarding(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'onboarding_step' => OnboardingStepEnum::STORE_CREATED,
        ]);

        $store = Store::factory()->create([
            'owner_id' => $user->id,
            'status' => StoreStatusEnum::PROVISIONING,
            'provisioning_status' => ProvisioningStatusEnum::RUNNING,
        ]);

        $job = new BootstrapStoreJob($store->id);

        // Use real services
        $initializationService = app(StoreInitializationService::class);
        $onboardingTransitionService = app(OnboardingTransitionService::class);

        $job->handle($initializationService, $onboardingTransitionService);

        $user->refresh();
        $store->refresh();

        $this->assertEquals(OnboardingStepEnum::COMPLETED, $user->onboarding_step);
        $this->assertEquals(ProvisioningStatusEnum::COMPLETED, $store->provisioning_status);
        $this->assertEquals(StoreStatusEnum::ACTIVE, $store->status);
    }

    public function test_bootstrap_store_job_failure_marks_provisioning_as_failed(): void
    {
        $store = Store::factory()->create([
            'status' => StoreStatusEnum::PROVISIONING,
            'is_active' => false,
            'provisioning_status' => ProvisioningStatusEnum::RUNNING,
            'provisioning_progress' => 40,
            'provisioning_current_step' => 'initializing_store',
            'provisioning_message' => 'Initializing store resources.',
            'provisioning_retryable' => false,
        ]);

        $job = new BootstrapStoreJob($store->id);
        $job->failed(new \RuntimeException('Provisioning failed'));

        $store->refresh();

        $this->assertSame(ProvisioningStatusEnum::FAILED, $store->provisioning_status);
        $this->assertSame('bootstrap_failed', $store->provisioning_current_step);
        $this->assertSame('Store provisioning failed. Retry provisioning to continue setup.', $store->provisioning_message);
        $this->assertTrue($store->provisioning_retryable);
    }
}
