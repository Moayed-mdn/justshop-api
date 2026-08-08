<?php

namespace App\Exceptions\Entitlement;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class QuotaExceededException extends BaseApiException
{
    public function __construct(
        ?string $featureKey = null,
        ?int $limit = null,
        string $message = ''
    ) {
        // Generate appropriate message based on feature key
        $defaultMessage = $this->generateMessage($featureKey, $limit);

        parent::__construct(
            message: $message ?: $defaultMessage,
            statusCode: 402, // Payment Required
            errorCode: ErrorCode::SUB_006->value,
        );
    }

    private function generateMessage(?string $featureKey, ?int $limit): string
    {
        if (!$featureKey) {
            return __('entitlement.quota_exceeded_generic');
        }

        // Use specific messages for known features
        $specificKey = match ($featureKey) {
            'stores.max' => 'entitlement.quota_exceeded_stores',
            'products.max' => 'entitlement.quota_exceeded_products',
            'users.max' => 'entitlement.quota_exceeded_users',
            default => 'entitlement.quota_exceeded',
        };

        return __($specificKey, [
            'feature' => $featureKey,
            'limit' => $limit ?? 'N/A',
        ]);
    }
}
