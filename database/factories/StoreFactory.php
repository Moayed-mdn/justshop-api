<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'owner_id' => User::factory(),
            'is_active' => true,
            'domain' => $this->faker->optional()->domainName(),
            'currency' => 'USD',
            'timezone' => 'UTC',
        ];
    }

    public function inactive()
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }
}
