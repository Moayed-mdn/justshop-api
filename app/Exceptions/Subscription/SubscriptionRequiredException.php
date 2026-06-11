<?php

namespace App\Exceptions\Subscription;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class SubscriptionRequiredException extends BaseApiException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message ?: __('subscription.required'),
            statusCode: 402, // Payment Required
            errorCode: ErrorCode::SUB_002->value,
        );
    }
}
