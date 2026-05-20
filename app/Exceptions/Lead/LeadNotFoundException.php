<?php

declare(strict_types=1);

namespace App\Exceptions\Lead;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class LeadNotFoundException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('lead.not_found'),
            statusCode: 404,
            errorCode: ErrorCode::LED_001->value,
        );
    }
}
