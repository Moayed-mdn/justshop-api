<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Subscription\BillingCycleEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\BillingAccount;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'billing_account_id' => BillingAccount::factory(),
            'plan_id' => Plan::factory(),
            'plan_price_id' => null,
            'status' => SubscriptionStatusEnum::ACTIVE,
            'billing_cycle' => BillingCycleEnum::MONTHLY,
            'provider' => 'stripe',
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays(30),
            'cancel_at_period_end' => false,
            'collection_paused' => false,
            'metadata' => [],
        ];
    }
}
