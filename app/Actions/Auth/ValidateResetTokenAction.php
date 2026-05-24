<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\ValidateResetTokenDTO;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;

class ValidateResetTokenAction
{
    public function __construct(
        private readonly PasswordBroker $passwordBroker,
    ) {}

    public function execute(ValidateResetTokenDTO $dto): bool
    {
        $user = User::query()
            ->where('email', $dto->userEmail)
            ->first();

        if (!$user) {
            return false;
        }

        return $this->passwordBroker->tokenExists(
            $user,
            $dto->token,
        );
    }
}
