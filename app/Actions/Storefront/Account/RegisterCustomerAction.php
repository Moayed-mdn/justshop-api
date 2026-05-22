<?php

declare(strict_types=1);

namespace App\Actions\Storefront\Account;

use App\DTOs\Storefront\Account\RegisterCustomerDTO;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterCustomerAction
{
    public function execute(RegisterCustomerDTO $dto): User
    {
        return DB::transaction(function () use ($dto): User {
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'onboarding_step' => null,
            ]);

            event(new Registered($user));

            return $user;
        });
    }
}
