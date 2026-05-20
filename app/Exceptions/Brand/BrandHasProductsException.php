<?php

declare(strict_types=1);

namespace App\Exceptions\Brand;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class BrandHasProductsException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('brand.has_products'),
            statusCode: 422,
            errorCode: ErrorCode::BRD_002->value,
        );
    }
}
