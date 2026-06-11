<?php

namespace App\Exceptions\Entitlement;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class FeatureNotAvailableException extends BaseApiException
{
    public function __construct(string $message = '', ?string $featureKey = null)
    {
        $defaultMessage = $featureKey 
            ? __('entitlement.feature_not_available', ['feature' => $featureKey])
            : __('entitlement.feature_not_available_generic');

        parent::__construct(
            message: $message ?: $defaultMessage,
            statusCode: 402, // Payment Required
            errorCode: ErrorCode::SUB_005->value,
        );
    }
}
