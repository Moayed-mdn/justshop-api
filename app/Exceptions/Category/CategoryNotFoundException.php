<?php

declare(strict_types=1);

namespace App\Exceptions\Category;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class CategoryNotFoundException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('category.not_found'),
            statusCode: 404,
            errorCode: ErrorCode::CAT_001->value,
        );
    }
}
