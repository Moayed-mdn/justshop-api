<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;
use Exception;
use Throwable;

abstract class DomainException extends Exception
{
    public function __construct(
        string $message = "",
        protected ErrorCode $errorCode = ErrorCode::SYS_001,
        int $code = 400,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode->value;
    }

    public function getStatus(): int
    {
        return $this->getCode();
    }
}
