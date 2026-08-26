<?php

declare(strict_types=1);

namespace App\Enums\Notification;

enum DevicePlatformEnum: string
{
    case IOS = 'ios';
    case ANDROID = 'android';
    case WEB = 'web';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
