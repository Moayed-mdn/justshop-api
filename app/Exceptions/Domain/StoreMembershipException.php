<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;

class StoreMembershipException extends DomainException
{
    public function __construct(string $message = "User is not a member of this store.")
    {
        parent::__construct($message, ErrorCode::AUTH_002, 403);
    }
}
