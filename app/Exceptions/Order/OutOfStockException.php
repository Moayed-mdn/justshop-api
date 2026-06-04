<?php

namespace App\Exceptions\Order;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class OutOfStockException extends BaseApiException
{
    public function __construct(?string $message = null, int $statusCode = 400, string $errorCode = ErrorCode::ORD_001->value, ?array $errors = null)
    {
        parent::__construct($message ?? __('services.item_out_of_stock'), $statusCode, $errorCode, $errors);
    }
}