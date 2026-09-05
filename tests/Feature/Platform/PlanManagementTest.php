<?php

namespace Tests\Feature\Platform;

use App\Actions\Billing\CreatePlanAction;
use App\Actions\Billing\UpdatePlanAction;
use App\Actions\Subscription\StartTrialAction;
use App\DTOs\Billing\CreatePlanDTO;
use App\DTOs\Billing\UpdatePlanDTO;
use App\DTOs\Subscription\StartTrialDTO;
use App\Contracts\Billing\BillingProviderInterface;
use App\Enums\RoleEnum;
use App\Models\BillingAccount;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Subscription\PlanRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create platform admin for authenticated requests
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');
        $this->platformAdmin = User::factory()->create([
            'email' => 'admin@platform.test',
        ]);
        $this->platformAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

        // These tests exercise the plan-management Actions directly (not
        // through Stripe), so the real Stripe-backed provider is swapped
        // for a fake that returns provider IDs without hitting the network.
        $this->mock(BillingProviderInterface::class, function ($mock) {
            $mock->shouldReceive('createPrice')
                ->andReturnUsing(fn () => [
                    'provider_product_id' => 'prod_' . Str::random(14),
                    'provider_price_id' => 'price_' . Str::random(14),
                ]);
            $mock->shouldReceive('archivePrice')->andReturnNull();
        });
    }

    /** @test */
    public function it_creates_a_plan_with_features_and_prices()
    {
        $action = app(CreatePlanAction::class);

        $dto = new CreatePlanDTO(
            code: 'test-plan',
            name: ['en' => 'Test Plan'],
            description: ['en' => 'A test plan'],
            tier: 'starter',
            tierRank: 1,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 10,
            metadata: null,
            features: [
                [
                    'featureKey' => 'stores.max',
                    'valueType' => 'limit',
                    'limitValue' => 1,
                    'booleanValue' => null,
                ],
                [
                    'featureKey' => 'products.max',
                    'valueType' => 'limit',
                    'limitValue' => 500,
                    'booleanValue' => null,
                ],
                [
                    'featureKey' => 'analytics.advanced',
                    'valueType' => 'boolean',
                    'limitValue' => null,
                    'booleanValue' => false,
                ],
            ],
            prices: [
                [
                    'billingCycle' => 'monthly',
                    'currency' => 'USD',
                    'amountCents' => 2900,
                    'provider' => 'stripe',
                ],
            ],
        );

        $plan = $action->execute($dto);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'code' => 'test-plan',
            'tier' => 'starter',
            'tier_rank' => 1,
        ]);

        $this->assertCount(3, $plan->features);
        $this->assertCount(1, $plan->prices);
    }

    /** @test */
    public function it_rejects_plan_with_duplicate_code_among_current_plans()
    {
        $action = app(CreatePlanAction::class);

        // Create first plan
        $dto1 = new CreatePlanDTO(
            code: 'duplicate-test',
            name: ['en' => 'First'],
            description: null,
            tier: 'starter',
            tierRank: 1,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 10,
            metadata: null,
            features: [
                [
                    'featureKey' => 'products.max',
                    'valueType' => 'limit',
                    'limitValue' => 100,
                    'booleanValue' => null,
                ],
            ],
            prices: [
                [
                    'billingCycle' => 'monthly',
                    'currency' => 'USD',
                    'amountCents' => 1000,
                ],
            ],
        );

        $action->execute($dto1);

        // Attempt to create duplicate
        $dto2 = new CreatePlanDTO(
            code: 'duplicate-test',
            name: ['en' => 'Second'],
            description: null,
            tier: 'growth',
            tierRank: 2,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 20,
            metadata: null,
            features: [
                [
                    'featureKey' => 'products.max',
                    'valueType' => 'limit',
                    'limitValue' => 200,
                    'booleanValue' => null,
                ],
            ],
            prices: [
                [
                    'billingCycle' => 'monthly',
                    'currency' => 'USD',
                    'amountCents' => 2000,
                ],
            ],
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('BIL_014');

        $action->execute($dto2);
    }

    /** @test */
    public function it_updates_non_breaking_fields_in_place()
    {
        $createAction = app(CreatePlanAction::class);
        $updateAction = app(UpdatePlanAction::class);

        // Create a plan
        $plan = $createAction->execute(new CreatePlanDTO(
            code: 'update-test',
            name: ['en' => 'Original Name'],
            description: null,
            tier: 'starter',
            tierRank: 1,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 10,
            metadata: null,
            features: [
                ['featureKey' => 'products.max', 'valueType' => 'limit', 'limitValue' => 100, 'booleanValue' => null],
            ],
            prices: [
                ['billingCycle' => 'monthly', 'currency' => 'USD', 'amountCents' => 1000],
            ],
        ));

        $originalId = $plan->id;

        // Update non-breaking fields
        $updateDto = new UpdatePlanDTO(
            planId: $plan->id,
            name: ['en' => 'Updated Name'],
            description: ['en' => 'New description'],
            sortOrder: 20,
        );

        $updatedPlan = $updateAction->execute($updateDto);

        // Should be same ID (in-place update)
        $this->assertEquals($originalId, $updatedPlan->id);
        $this->assertEquals('Updated Name', $updatedPlan->name['en']);
        $this->assertEquals(20, $updatedPlan->sort_order);
    }

    /** @test */
    public function it_creates_new_version_when_editing_in_use_plan_with_breaking_changes()
    {
        $createAction = app(CreatePlanAction::class);
        $updateAction = app(UpdatePlanAction::class);
        $planRepository = app(PlanRepository::class);

        // Create a plan
        $plan = $createAction->execute(new CreatePlanDTO(
            code: 'versioning-test',
            name: ['en' => 'Original'],
            description: null,
            tier: 'starter',
            tierRank: 1,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 10,
            metadata: null,
            features: [
                ['featureKey' => 'products.max', 'valueType' => 'limit', 'limitValue' => 100, 'booleanValue' => null],
            ],
            prices: [
                ['billingCycle' => 'monthly', 'currency' => 'USD', 'amountCents' => 1000],
            ],
        ));

        $originalId = $plan->id;

        // Create a subscription on this plan (makes it "in use")
        $user = User::factory()->create();
        $billingAccount = BillingAccount::factory()->create(['owner_user_id' => $user->id]);
        
        Subscription::factory()->create([
            'billing_account_id' => $billingAccount->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        // Verify plan is in use
        $this->assertTrue($planRepository->isInUse($plan->id));

        // Update with breaking change (features)
        $updateDto = new UpdatePlanDTO(
            planId: $plan->id,
            features: [
                ['featureKey' => 'products.max', 'valueType' => 'limit', 'limitValue' => 500, 'booleanValue' => null],
            ],
        );

        $newVersion = $updateAction->execute($updateDto);

        // Should be a NEW plan ID
        $this->assertNotEquals($originalId, $newVersion->id);

        // Old plan should be superseded
        $oldPlan = Plan::find($originalId);
        $this->assertEquals($newVersion->id, $oldPlan->superseded_by_plan_id);
        $this->assertFalse($oldPlan->is_active);
        $this->assertFalse($oldPlan->is_public);

        // New plan should have updated features
        $productsFeature = $newVersion->features->firstWhere('feature_key', 'products.max');
        $this->assertEquals(500, $productsFeature->limit_value);

        // Existing subscription should still point to OLD plan
        $subscription = Subscription::first();
        $this->assertEquals($originalId, $subscription->plan_id);
    }

    /** @test */
    public function it_prevents_deleting_plan_with_subscribers()
    {
        $createAction = app(CreatePlanAction::class);
        
        // Create plan
        $plan = $createAction->execute(new CreatePlanDTO(
            code: 'delete-test',
            name: ['en' => 'Delete Test'],
            description: null,
            tier: 'starter',
            tierRank: 1,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 10,
            metadata: null,
            features: [
                ['featureKey' => 'products.max', 'valueType' => 'limit', 'limitValue' => 100, 'booleanValue' => null],
            ],
            prices: [
                ['billingCycle' => 'monthly', 'currency' => 'USD', 'amountCents' => 1000],
            ],
        ));

        // Create subscription
        $user = User::factory()->create();
        $billingAccount = BillingAccount::factory()->create(['owner_user_id' => $user->id]);
        
        Subscription::factory()->create([
            'billing_account_id' => $billingAccount->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        // Attempt to delete
        $deleteAction = app(\App\Actions\Billing\DeletePlanAction::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('BIL_015');

        $deleteAction->execute($plan->id);
    }

    /** @test */
    public function it_allows_deleting_plan_with_no_subscribers()
    {
        $createAction = app(CreatePlanAction::class);
        
        // Create plan with no subscribers
        $plan = $createAction->execute(new CreatePlanDTO(
            code: 'delete-ok-test',
            name: ['en' => 'Delete OK Test'],
            description: null,
            tier: 'starter',
            tierRank: 1,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 10,
            metadata: null,
            features: [
                ['featureKey' => 'products.max', 'valueType' => 'limit', 'limitValue' => 100, 'booleanValue' => null],
            ],
            prices: [
                ['billingCycle' => 'monthly', 'currency' => 'USD', 'amountCents' => 1000],
            ],
        ));

        $planId = $plan->id;

        // Delete should succeed
        $deleteAction = app(\App\Actions\Billing\DeletePlanAction::class);
        $deleteAction->execute($planId);

        // Plan should be soft-deleted
        $this->assertSoftDeleted('plans', ['id' => $planId]);
    }

    /** @test */
    public function it_finds_only_current_plans_by_code()
    {
        $planRepository = app(PlanRepository::class);
        $createAction = app(CreatePlanAction::class);

        // Create a plan
        $plan1 = $createAction->execute(new CreatePlanDTO(
            code: 'current-test',
            name: ['en' => 'Version 1'],
            description: null,
            tier: 'starter',
            tierRank: 1,
            isPublic: true,
            isActive: true,
            trialDays: 14,
            sortOrder: 10,
            metadata: null,
            features: [
                ['featureKey' => 'products.max', 'valueType' => 'limit', 'limitValue' => 100, 'booleanValue' => null],
            ],
            prices: [
                ['billingCycle' => 'monthly', 'currency' => 'USD', 'amountCents' => 1000],
            ],
        ));

        // Manually supersede it (simulate versioning)
        $plan2 = Plan::factory()->create([
            'code' => 'current-test',
            'tier' => 'starter',
            'tier_rank' => 1,
        ]);

        $plan1->update(['superseded_by_plan_id' => $plan2->id]);

        // findCurrentByCode should return plan2, not plan1
        $current = $planRepository->findCurrentByCode('current-test');
        
        $this->assertNotNull($current);
        $this->assertEquals($plan2->id, $current->id);
    }
}
