<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Enums\Auth\OnboardingStepEnum;
use Laravel\Sanctum\Sanctum;
use App\Enums\Store\StoreStatusEnum;
use App\Enums\Store\ProvisioningStatusEnum;
use App\Enums\ErrorCode;

class ApiContractTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_me_endpoint_returns_canonical_bootstrap_data(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        $store = Store::factory()->create(['owner_id' => $user->id]);
        $user->stores()->attach($store, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $store->id]);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'avatar_url',
                        'is_email_verified',
                    ],
                    'stores' => [
                        [
                            'id',
                            'name',
                            'slug',
                            'domain',
                            'currency',
                            'role',
                            'status',
                            'permissions',
                        ]
                    ],
                    'active_store_id',
                    'onboarding' => [
                        'step',
                        'completed_steps',
                        'can_resume',
                        'store_id',
                        'is_completed',
                    ],
                    'permissions',
                    'session' => [
                        'id',
                        'ip_address',
                        'user_agent',
                        'last_active_at',
                        'is_current',
                    ],
                    'features' => $this->getExpectedFeaturesStructure(),
                    'localization' => [
                        'supported_locales',
                        'default_currency',
                        'timezone',
                    ],
                ],
            ])->assertJson(['data' => ['active_store_id' => $store->id]]);
    }

    public function test_me_endpoint_preserves_nullable_bootstrap_fields_before_first_store_creation(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.active_store_id', null)
            ->assertJsonPath('data.onboarding.step', OnboardingStepEnum::CREATE_STORE->value)
            ->assertJsonPath('data.onboarding.store_id', null)
            ->assertJsonPath('data.onboarding.can_resume', true)
            ->assertJsonPath('data.onboarding.is_completed', false)
            ->assertJsonCount(0, 'data.stores');
    }

    private function getExpectedFeaturesStructure(): array
    {
        $features = [];
        foreach (\App\Support\FeatureFlags\FeatureFlag::all() as $name => $_config) {
            $features[] = $name;
        }

        return $features;
    }

    public function test_get_provisioning_status_returns_correct_data(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        $store = Store::factory()->create([
            'owner_id' => $user->id,
            'status' => StoreStatusEnum::PROVISIONING,
            'provisioning_status' => \App\Enums\Store\ProvisioningStatusEnum::RUNNING,
            'provisioning_progress' => 50,
            'provisioning_current_step' => 'creating_database',
            'provisioning_message' => 'Setting up your store database',
            'provisioning_retryable' => false,
        ]);
        $user->stores()->attach($store, ['role' => 'owner']);

        $response = $this->getJson("/api/v1/stores/{$store->id}/provisioning-status");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'progress',
                    'current_step',
                    'message',
                    'retryable',
                ],
            ])
            ->assertJsonPath('data.status', \App\Enums\Store\ProvisioningStatusEnum::RUNNING->value)
            ->assertJsonPath('data.progress', 50)
            ->assertJsonPath('data.current_step', 'creating_database')
            ->assertJsonPath('data.message', 'Setting up your store database')
            ->assertJsonPath('data.retryable', false);
    }

    public function test_get_provisioning_status_requires_store_membership(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        /** @var User $intruder */
        $intruder = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        $this->actingAs($intruder);

        $store = Store::factory()->create([
            'owner_id' => $owner->id,
            'status' => StoreStatusEnum::PROVISIONING,
            'provisioning_status' => ProvisioningStatusEnum::RUNNING,
        ]);
        $owner->stores()->attach($store, ['role' => 'owner']);

        $response = $this->getJson("/api/v1/stores/{$store->id}/provisioning-status");

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => ErrorCode::STORE_ACCESS_DENIED->value,
                'redirect' => '/dashboard',
            ]);
    }

    public function test_get_active_sessions_returns_correct_data(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create some sessions for the user
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            [
                'id' => 'session1',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
                'payload' => 'some_payload',
                'last_activity' => now()->subMinutes(5)->timestamp,
            ],
            [
                'id' => 'session2',
                'user_id' => $user->id,
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
                'payload' => 'some_payload',
                'last_activity' => now()->subHours(1)->timestamp,
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/users/sessions');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'ip_address',
                        'user_agent',
                        'last_active_at',
                        'is_current',
                    ]
                ],
            ]);
    }

    public function test_revoke_single_session(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $currentSessionId = session()->getId();

        // Create two sessions for the user
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            [
                'id' => $currentSessionId,
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'agent1',
                'payload' => 'payload1',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'session_to_revoke',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.2',
                'user_agent' => 'agent2',
                'payload' => 'payload2',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $response = $this->deleteJson('/api/v1/users/sessions/session_to_revoke');

        $response->assertOk()
            ->assertJson([
                'message' => __('auth.session_revoked'),
            ]);

        $this->assertDatabaseMissing('sessions', ['id' => 'session_to_revoke']);
        $this->assertDatabaseHas('sessions', ['id' => $currentSessionId]);
    }

    public function test_revoke_single_session_does_not_delete_foreign_session(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $this->actingAs($user);

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'foreign_session',
            'user_id' => $otherUser->id,
            'ip_address' => '127.0.0.9',
            'user_agent' => 'foreign-agent',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->deleteJson('/api/v1/users/sessions/foreign_session');

        $response->assertOk()
            ->assertJson([
                'message' => __('auth.session_revoked'),
            ]);

        $this->assertDatabaseHas('sessions', ['id' => 'foreign_session', 'user_id' => $otherUser->id]);
    }

    public function test_revoke_all_other_sessions(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        session()->setId('current_session');
        session()->start();
        $this->actingAs($user);

        $currentSessionId = session()->getId();

        // Create three sessions for the user
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            [
                'id' => $currentSessionId,
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'agent_current',
                'payload' => 'payload_current',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'session_to_revoke_1',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.2',
                'user_agent' => 'agent_old_1',
                'payload' => 'payload_old_1',
                'last_activity' => now()->subHour()->timestamp,
            ],
            [
                'id' => 'session_to_revoke_2',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.3',
                'user_agent' => 'agent_old_2',
                'payload' => 'payload_old_2',
                'last_activity' => now()->subHours(2)->timestamp,
            ],
        ]);

        $response = $this->deleteJson('/api/v1/users/sessions', [
            'password' => 'password', // Assuming password confirmation is required
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('auth.all_other_sessions_revoked'),
            ]);

        $this->assertDatabaseMissing('sessions', ['id' => 'session_to_revoke_1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'session_to_revoke_2']);
    }

    public function test_logout_all_devices_action_preserves_current_session(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            [
                'id' => 'current_session',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'agent_current',
                'payload' => 'payload_current',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'session_to_revoke_1',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.2',
                'user_agent' => 'agent_old_1',
                'payload' => 'payload_old_1',
                'last_activity' => now()->subHour()->timestamp,
            ],
            [
                'id' => 'session_to_revoke_2',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.3',
                'user_agent' => 'agent_old_2',
                'payload' => 'payload_old_2',
                'last_activity' => now()->subHours(2)->timestamp,
            ],
        ]);

        $result = app(\App\Actions\Auth\LogoutAllDevicesAction::class)->execute(
            new \App\DTOs\Auth\LogoutAllDevicesDTO(
                userId: $user->id,
                currentSessionId: 'current_session',
            )
        );

        $this->assertSame(2, $result['sessions_revoked']);
        $this->assertDatabaseHas('sessions', ['id' => 'current_session', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'session_to_revoke_1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'session_to_revoke_2']);
    }

    public function test_email_verification_status_returns_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users/auth/email/status');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'email_verified' => true,
                    'email_verified_at' => $user->email_verified_at->toIso8601String(),
                ],
            ]);
    }

    public function test_email_verification_status_returns_unverified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users/auth/email/status');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'email_verified' => false,
                    'email_verified_at' => null,
                ],
            ]);
    }

    public function test_slug_availability_endpoint_returns_available(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/store-slug/check?slug=new-unique-slug');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'available' => true,
                    'reason' => null,
                ],
            ]);
    }

    public function test_slug_availability_endpoint_returns_taken(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        Store::factory()->create(['slug' => 'existing-slug', 'owner_id' => $user->id]);

        $response = $this->getJson('/api/v1/store-slug/check?slug=existing-slug');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'available' => false,
                    'reason' => 'taken',
                ],
            ]);
    }

    public function test_slug_availability_endpoint_returns_reserved(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/store-slug/check?slug=admin');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'available' => false,
                    'reason' => 'reserved',
                ],
            ]);
    }

    public function test_slug_availability_endpoint_returns_blocked(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/store-slug/check?slug=my-shit-store');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'available' => false,
                    'reason' => 'blocked',
                ],
            ]);
    }

    public function test_me_endpoint_returns_store_with_correct_status(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        $store = Store::factory()->create([
            'owner_id' => $user->id,
            'status' => StoreStatusEnum::PENDING_SETUP,
        ]);
        $user->stores()->attach($store, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $store->id]);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.stores.0.status', StoreStatusEnum::PENDING_SETUP->value)
            ->assertJsonPath('data.stores.0.is_active', false); // PENDING_SETUP is not active
    }

    public function test_accessing_disabled_store_returns_correct_error(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        $store = Store::factory()->create([
            'owner_id' => $user->id,
            'status' => \App\Enums\Store\StoreStatusEnum::DISABLED,
            'is_active' => false,
        ]);
        $user->stores()->attach($store, ['role' => 'owner']);

        // This route assumes a store is present in the URL path, e.g., /v1/stores/{store}/some-endpoint
        // The middleware 'store.context' will resolve the store and throw exceptions based on its status.
        $response = $this->getJson("/api/v1/stores/{$store->id}/products");

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => ErrorCode::STR_002->value,
                'message' => 'Store is disabled',
            ]);
    }

    public function test_accessing_non_existent_store_returns_correct_error(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
        Sanctum::actingAs($user);

        $nonExistentStoreId = 99999;

        $response = $this->getJson("/api/v1/stores/{$nonExistentStoreId}/products");

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'code' => ErrorCode::STR_001->value,
                'message' => 'Store not found',
            ]);
    }
}
