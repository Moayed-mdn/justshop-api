<?php
namespace Database\Factories;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => $this->faker->optional()->phoneNumber(),
            'is_active' => true,
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin()
    {
        return $this->afterCreating(function ($user) {
            $role = \Spatie\Permission\Models\Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');
            $user->assignRole($role);
        });
    }

    public function merchant()
    {
        return $this->state(fn() => [
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);
    }

    public function pendingVerification()
    {
        return $this->state(fn() => [
            'email_verified_at' => null,
            'onboarding_step' => OnboardingStepEnum::PENDING_VERIFICATION,
        ]);
    }

    public function createStoreStep()
    {
        return $this->state(fn() => [
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
        ]);
    }

    public function customer()
    {
        return $this->state(fn() => [
            'onboarding_step' => null,
        ]);
    }

    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    public function verified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => now(),
            ];
        });
    }

    public function withPhone()
    {
        return $this->state(function (array $attributes) {
            return [
                'phone' => $this->faker->phoneNumber(),
            ];
        });
    }
}