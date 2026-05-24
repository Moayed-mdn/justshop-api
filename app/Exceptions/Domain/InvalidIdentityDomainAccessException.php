<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;

class InvalidIdentityDomainAccessException extends DomainException
{
    public function __construct(string $message = 'Identity context is not allowed to access this route.')
    {
        parent::__construct($message, ErrorCode::STORE_ACCESS_DENIED, 403);
    }
}
