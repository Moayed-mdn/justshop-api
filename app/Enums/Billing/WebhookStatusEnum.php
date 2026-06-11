<?php

namespace App\Enums\Billing;

enum WebhookStatusEnum: string
{
    case RECEIVED   = 'received';
    case PROCESSING = 'processing';
    case PROCESSED  = 'processed';
    case FAILED     = 'failed';
    case SKIPPED    = 'skipped';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
