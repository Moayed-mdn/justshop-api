<?php

namespace App\Exceptions\Order;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class OrderCancellationException extends BaseApiException
{
    public function __construct(?string $message = null, int $statusCode = 422, string $errorCode = ErrorCode::ORD_002->value, ?array $errors = null)
    {
        parent::__construct($message ?? __('services.order_cannot_be_cancelled'), $statusCode, $errorCode, $errors);
    }
}