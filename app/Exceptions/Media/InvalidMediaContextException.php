<?php

declare(strict_types=1);

namespace App\Exceptions\Media;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class InvalidMediaContextException extends BaseApiException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message ?: __('media.invalid_context'),
            statusCode: 400,
            errorCode: ErrorCode::SYS_001->value,
        );
    }
}
