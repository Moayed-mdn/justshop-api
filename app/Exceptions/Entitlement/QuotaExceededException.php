<?php

namespace App\Exceptions\Entitlement;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class QuotaExceededException extends BaseApiException
{
    public function __construct(string $message = '', ?string $featureKey = null, ?int $limit = null)
    {
        $defaultMessage = $featureKey && $limit
            ? __('entitlement.quota_exceeded', ['feature' => $featureKey, 'limit' => $limit])
            : __('entitlement.quota_exceeded_generic');

        parent::__construct(
            message: $message ?: $defaultMessage,
            statusCode: 402, // Payment Required
            errorCode: ErrorCode::SUB_006->value,
        );
    }
}
