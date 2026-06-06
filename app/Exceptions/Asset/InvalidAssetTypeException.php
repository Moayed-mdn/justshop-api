<?php

namespace App\Exceptions\Asset;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class InvalidAssetTypeException extends BaseApiException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message ?: __('theme.invalid_file_type'),
            statusCode: 422,
            errorCode: ErrorCode::VAL_001->value,
        );
    }
}
