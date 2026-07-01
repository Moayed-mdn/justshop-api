<?php

namespace App\Enums\Address;

enum AddressValidationStatusEnum: string
{
    case VALID = 'valid';
    case WARNING = 'warning';
    case ERROR = 'error';
    case UNKNOWN = 'unknown';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
