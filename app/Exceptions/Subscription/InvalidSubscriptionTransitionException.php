<?php

namespace App\Exceptions\Subscription;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class InvalidSubscriptionTransitionException extends BaseApiException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message ?: __('subscription.invalid_transition'),
            statusCode: 400,
            errorCode: ErrorCode::SUB_004->value,
        );
    }
}
