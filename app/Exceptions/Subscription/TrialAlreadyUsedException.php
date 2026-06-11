<?php

namespace App\Exceptions\Subscription;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class TrialAlreadyUsedException extends BaseApiException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message ?: __('subscription.trial_already_used'),
            statusCode: 403,
            errorCode: ErrorCode::SUB_003->value,
        );
    }
}
