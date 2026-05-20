<?php

declare(strict_types=1);

namespace App\Exceptions\Category;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class CategoryHasProductsException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('category.has_products'),
            statusCode: 422,
            errorCode: ErrorCode::CAT_003->value,
        );
    }
}
