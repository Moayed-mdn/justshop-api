<?php

declare(strict_types=1);

namespace App\Enums\Store;

enum ProvisioningStatusEnum: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
