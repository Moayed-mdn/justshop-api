<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCreationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_store_creation_completes_onboarding(): void
    {
        /** @var User $user */
        $user = User::factory()->createStoreStep()->create();
        $this->actingAs($user);

        $payload = [
            'name' => 'My First Store',
            'slug' => 'my-first-store',
        ];

        $response = $this->postJson('/api/v1/stores', $payload);

        $response->assertStatus(201);
        
        $user->refresh();
        $this->assertEquals(OnboardingStepEnum::COMPLETED, $user->onboarding_step);
        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertNotNull($user->last_active_store_id);
    }
}
