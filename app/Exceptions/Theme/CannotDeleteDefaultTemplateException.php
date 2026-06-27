<?php

declare(strict_types=1);

namespace App\Exceptions\Theme;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class CannotDeleteDefaultTemplateException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('theme.cannot_delete_default_template'),
            statusCode: 400,
            errorCode: ErrorCode::THEME_001->value,
        );
    }
}
