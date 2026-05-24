<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\LogoutSessionDTO;
use Illuminate\Support\Facades\DB;

class LogoutSessionAction
{
    public function execute(LogoutSessionDTO $dto): void
    {
        DB::table('sessions')
            ->where('id', $dto->sessionId)
            ->where('user_id', $dto->userId)
            ->delete();
    }
}
