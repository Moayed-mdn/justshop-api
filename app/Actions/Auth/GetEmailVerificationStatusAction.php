<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\EmailVerificationStatusDTO;
use App\Models\User;

class GetEmailVerificationStatusAction
{
    public function execute(User $user): EmailVerificationStatusDTO
    {
        return new EmailVerificationStatusDTO(
            emailVerified: $user->hasVerifiedEmail(),
            emailVerifiedAt: $user->email_verified_at?->toIso8601String(),
        );
    }
}
