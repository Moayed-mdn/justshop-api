<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'session_id' => null,
            'currency' => 'USD',
            'is_active' => true,
            'expires_at' => null,
        ];
    }

    /**
     * A guest cart with no owning user, identified by session id instead.
     */
    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
