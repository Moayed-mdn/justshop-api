<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Billing\BillingAccountStatusEnum;
use App\Models\BillingAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillingAccount>
 */
class BillingAccountFactory extends Factory
{
    protected $model = BillingAccount::class;

    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'billing_email' => fake()->unique()->safeEmail(),
            'legal_name' => fake()->company(),
            'country_code' => 'US',
            'default_currency' => 'USD',
            'status' => BillingAccountStatusEnum::ACTIVE,
            'trial_used' => false,
            'stores_count' => 0,
            'metadata' => [],
        ];
    }
}
