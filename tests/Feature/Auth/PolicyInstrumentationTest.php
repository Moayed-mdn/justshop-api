<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Policies\StorePolicy;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PolicyInstrumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_decisions_are_logged_with_policy_actor_store_and_result_context(): void
    {
        Log::spy();

        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();

        $allowed = app(StorePolicy::class)->update($user, $store);

        $this->assertFalse($allowed);

        Log::shouldHaveReceived('info')->with(
            'authorization.policy.decision',
            Mockery::on(fn (array $context): bool => ($context['policy'] ?? null) === StorePolicy::class
                && ($context['ability'] ?? null) === 'update'
                && ($context['capability'] ?? null) === 'store.update'
                && ($context['result'] ?? null) === 'deny'
                && ($context['deny'] ?? null) === true
                && ($context['allow'] ?? null) === false
                && ($context['actor_id'] ?? null) === $user->id
                && ($context['actor_context'] ?? null) === 'merchant'
                && (($context['store_context']['id'] ?? null) === $store->id)),
        )->atLeast()->once();
    }
}
