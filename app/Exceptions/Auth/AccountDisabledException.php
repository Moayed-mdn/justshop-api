<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class AccountDisabledException extends BaseApiException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            message: $message ?? __('auth.account_disabled'),
            statusCode: 403,
            errorCode: ErrorCode::AUTH_002->value,
        );
    }
}
