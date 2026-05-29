<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use RuntimeException;

class RuntimeContractException extends RuntimeException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        private readonly string $runtimeCode,
        private readonly int $httpStatus,
        string $message,
        private readonly array $details = [],
        private readonly bool $retryable = false,
    ) {
        parent::__construct($message);
    }

    public function runtimeCode(): string
    {
        return $this->runtimeCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }

    public function retryable(): bool
    {
        return $this->retryable;
    }
}

