<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\DTOs\Auth\RegisterUserDTO;
use App\Models\User;
use App\Domain\Shared\Events\MerchantRegistered;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function execute(RegisterUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'onboarding_step' => OnboardingStepEnum::PENDING_VERIFICATION,
            ]);

            event(new Registered($user));
            
            // Dispatch internal domain event after commit
            DB::afterCommit(function () use ($user) {
                MerchantRegistered::dispatch(
                    $user->id,
                    $user->email,
                    $user->name
                );
            });

            return $user;
        });
    }
}
