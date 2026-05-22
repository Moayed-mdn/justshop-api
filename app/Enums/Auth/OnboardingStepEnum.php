<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum OnboardingStepEnum: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case CREATE_STORE = 'create_store';
    case COMPLETED = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
