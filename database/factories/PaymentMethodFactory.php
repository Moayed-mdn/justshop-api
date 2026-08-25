<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'stripe',
            'payment_method_id' => 'pm_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'brand' => $this->faker->randomElement(['visa', 'mastercard', 'amex']),
            'last_four' => (string) $this->faker->numberBetween(1000, 9999),
            'exp_month' => $this->faker->numberBetween(1, 12),
            'exp_year' => (int) now()->addYears(2)->format('Y'),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'exp_month' => 1,
            'exp_year' => (int) now()->subYears(1)->format('Y'),
        ]);
    }
}
