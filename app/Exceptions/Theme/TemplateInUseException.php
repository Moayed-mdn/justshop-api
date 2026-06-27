<?php

declare(strict_types=1);

namespace App\Exceptions\Theme;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class TemplateInUseException extends BaseApiException
{
    public function __construct(int $pagesCount)
    {
        parent::__construct(
            message: __('theme.template_in_use', ['count' => $pagesCount]),
            statusCode: 400,
            errorCode: ErrorCode::THEME_002->value,
        );
    }
}
