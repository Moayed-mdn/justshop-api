<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;

class InvalidActorContextException extends DomainException
{
    public function __construct(string $message = "Action is not allowed for this actor context.")
    {
        parent::__construct($message, ErrorCode::AUTH_002, 403);
    }
}
