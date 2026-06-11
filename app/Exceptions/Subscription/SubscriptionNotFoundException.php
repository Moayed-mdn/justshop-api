<?php

namespace App\Exceptions\Subscription;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class SubscriptionNotFoundException extends BaseApiException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message ?: __('subscription.not_found'),
            statusCode: 404,
            errorCode: ErrorCode::SUB_001->value,
        );
    }
}
