<?php

declare(strict_types=1);

namespace App\Exceptions\Store;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;
use Symfony\Component\HttpFoundation\Response;

class StoreDisabledException extends BaseApiException
{
    public function __construct(string $message = 'Store is disabled', ?\Throwable $previous = null)
    {
        parent::__construct(
            $message,
            Response::HTTP_FORBIDDEN,
            ErrorCode::STR_002->value,
            $previous,
        );
    }
}
