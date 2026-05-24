<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;

/**
 * Thrown when an invalid store lifecycle status transition is attempted.
 * For example: trying to transition from DELETED_PENDING to ACTIVE.
 */
class InvalidStoreLifecycleTransitionException extends DomainException
{
    public function __construct(string $message = "Invalid store lifecycle transition.")
    {
        parent::__construct($message, ErrorCode::STR_003, 422);
    }
}
