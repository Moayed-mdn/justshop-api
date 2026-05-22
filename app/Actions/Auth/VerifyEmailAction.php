<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\DTOs\Auth\VerifyEmailDTO;
use App\Exceptions\Auth\EmailVerificationException;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class VerifyEmailAction
{
    public function execute(VerifyEmailDTO $dto): array
    {
        $user = User::findOrFail($dto->id);

        $expectedHash = sha1($user->getEmailForVerification());
        if (!hash_equals($expectedHash, $dto->hash)) {
            throw new EmailVerificationException(__('auth.verification_link_invalid'));
        }

        if ($user->hasVerifiedEmail()) {
            return ['already_verified' => true];
        }

        $user->markEmailAsVerified();

        // Advance onboarding step if it was pending verification
        if ($user->onboarding_step === OnboardingStepEnum::PENDING_VERIFICATION) {
            $user->update(['onboarding_step' => OnboardingStepEnum::CREATE_STORE]);
        }

        event(new Verified($user));

        return ['already_verified' => false];
    }
}
