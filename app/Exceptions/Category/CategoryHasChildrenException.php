<?php

declare(strict_types=1);

namespace App\Exceptions\Category;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class CategoryHasChildrenException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('category.has_children'),
            statusCode: 422,
            errorCode: ErrorCode::CAT_002->value,
        );
    }
}
