<?php

namespace App\Exceptions\Tag;

use App\Exceptions\BaseApiException;
use App\Enums\ErrorCode;

/**
 * Thrown when a tag cannot be found within the requested store scope.
 *
 * ErrorCode: TAG_001
 * Add to app/Enums/ErrorCode.php:
 *   case TAG_001 = 'TAG_001'; // Tag not found
 */
class TagNotFoundException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message:    __('error.tag_not_found'),
            statusCode: 404,
            errorCode:  ErrorCode::TAG_001->value,
        );
    }
}
