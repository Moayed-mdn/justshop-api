<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Subscription\PlanTierEnum;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $tier = fake()->randomElement(PlanTierEnum::cases());

        return [
            'code' => 'plan-' . Str::random(8),
            'name' => ['en' => fake()->words(2, true)],
            'description' => ['en' => fake()->sentence()],
            'tier' => $tier,
            'tier_rank' => $tier->tier(),
            'is_public' => true,
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 0,
            'metadata' => [],
        ];
    }
}
