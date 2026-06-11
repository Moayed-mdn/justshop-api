<?php

namespace App\Exceptions\Billing;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class BillingAccountNotFoundException extends BaseApiException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message ?: __('billing.account_not_found'),
            statusCode: 404,
            errorCode: ErrorCode::BIL_001->value,
        );
    }
}
