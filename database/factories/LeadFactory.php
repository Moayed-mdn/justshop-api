<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Lead\LeadStatusEnum;
use App\Enums\Lead\LeadTypeEnum;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => LeadTypeEnum::CONTACT,
            'status' => LeadStatusEnum::NEW,
            'locale' => 'en',
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'company' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'message' => $this->faker->paragraph(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'metadata' => [],
        ];
    }

    public function contact(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => LeadTypeEnum::CONTACT,
        ]);
    }

    public function demo(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => LeadTypeEnum::DEMO,
        ]);
    }

    public function enterprise(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => LeadTypeEnum::ENTERPRISE,
        ]);
    }
}
