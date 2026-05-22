<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\GetMeDTO;
use App\Models\User;

class GetMeAction
{
    public function execute(GetMeDTO $dto): User
    {
        /** @var User|null $user */
        $user = $dto->user;

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
