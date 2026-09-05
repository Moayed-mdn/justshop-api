<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\ErrorCode;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FrontendContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_endpoint_returns_canonical_payload(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
        ]);

        $store = Store::factory()->create([
            'owner_id' => $user->id,
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $user->stores()->attach($store, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $store->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'avatar_url',
                        'is_email_verified',
                        'email_verified_at',
                    ],
                    'stores' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'domain',
                            'currency',
                            'role',
                            'status',
                            'is_active',
                            'status_changed_at',
                            'created_at',
                            'permissions',
                        ],
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
                    'session',
                    'features',
                    'localization',
                ],
            ]);
    }

    public function test_slug_availability_endpoint(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Store::factory()->create(['slug' => 'taken-slug']);

        $response = $this->actingAs($user)->getJson('/api/v1/store-slug/check?slug=taken-slug');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available' => false,
                    'reason' => 'taken',
                ],
            ]);

        $response = $this->actingAs($user)->getJson('/api/v1/store-slug/check?slug=available-slug');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available' => true,
                    'reason' => null,
                ],
            ]);

        $response = $this->actingAs($user)->getJson('/api/v1/store-slug/check?slug=admin');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available' => false,
                    'reason' => 'reserved',
                ],
            ]);
    }

    public function test_provisioning_status_endpoint(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $store = Store::factory()->create([
            'owner_id' => $user->id,
            'provisioning_status' => \App\Enums\Store\ProvisioningStatusEnum::RUNNING,
            'provisioning_progress' => 45,
            'provisioning_current_step' => 'setting_up_database',
            'provisioning_message' => 'Creating database tables...',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/provisioning-status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'running',
                    'progress' => 45,
                    'current_step' => 'setting_up_database',
                    'message' => 'Creating database tables...',
                    'retryable' => false,
                ],
            ]);
    }

    public function test_legacy_bootstrap_aliases_resolve_to_bootstrap_payload(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/users/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.onboarding.step', OnboardingStepEnum::CREATE_STORE->value);

        $this->actingAs($user)
            ->getJson('/api/v1/users/auth/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.onboarding.step', OnboardingStepEnum::CREATE_STORE->value);
    }

    public function test_active_store_switch_returns_refreshed_bootstrap_payload(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $firstStore = Store::factory()->create([
            'owner_id' => $user->id,
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);
        $secondStore = Store::factory()->create([
            'owner_id' => $user->id,
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
        ]);

        $user->stores()->attach($firstStore, ['role' => 'owner']);
        $user->stores()->attach($secondStore, ['role' => 'owner']);
        $user->update(['last_active_store_id' => $firstStore->id]);

        $response = $this->actingAs($user)->patchJson('/api/v1/users/auth/active-store', [
            'store_id' => $secondStore->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.active_store_id', $secondStore->id)
            ->assertJsonPath('meta.session.actor_type', 'merchant');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'last_active_store_id' => $secondStore->id,
        ]);
    }

    public function test_login_logout_and_protected_route_flow(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
        ]);

        $loginResponse = $this->withHeaders(['Referer' => 'http://localhost'])->postJson('/api/v1/users/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('meta.session.actor_type', 'merchant');

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', $user->email);

        $this->getJson('/api/v1/store-slug/check?slug=frontend-flow-store')
            ->assertOk()
            ->assertJsonPath('data.available', true);

        $this->withHeaders(['Referer' => 'http://localhost'])->postJson('/api/v1/users/auth/logout')
            ->assertOk()
            ->assertJsonPath('meta.session.actor_type', 'merchant');

        $this->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_error_normalization_for_validation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/stores', [
            'name' => '', // Missing name
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code' => ErrorCode::VAL_001->value,
            ])
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_error_normalization_for_unauthorized_store_access(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $otherStore = Store::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/stores/{$otherStore->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => ErrorCode::IDENTITY_DOMAIN_MISMATCH->value,
                'redirect' => '/dashboard',
            ]);
    }

    public function test_session_management_endpoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        
        // Mock session in DB
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'session1',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'last_activity' => time(),
            'payload' => '',
        ]);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])->actingAs($user)->getJson('/api/v1/users/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'ip_address',
                        'user_agent',
                        'last_active_at',
                        'is_current',
                    ],
                ],
            ]);
    }
}
