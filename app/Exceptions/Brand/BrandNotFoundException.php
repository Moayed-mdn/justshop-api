<?php

declare(strict_types=1);

namespace App\Exceptions\Brand;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class BrandNotFoundException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('brand.not_found'),
            statusCode: 404,
            errorCode: ErrorCode::BRD_001->value,
        );
    }
}
