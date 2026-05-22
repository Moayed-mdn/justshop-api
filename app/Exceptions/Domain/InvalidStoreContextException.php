<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;

class InvalidStoreContextException extends DomainException
{
    public function __construct(string $message = "The requested store is inactive or unavailable.")
    {
        parent::__construct($message, ErrorCode::STR_001, 403);
    }
}
