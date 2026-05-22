<?php

declare(strict_types=1);

namespace App\Support\Observability;

use Illuminate\Support\Str;

class CorrelationIdGenerator
{
    public function resolve(?string $incomingCorrelationId): string
    {
        if ($this->isValid($incomingCorrelationId)) {
            return strtolower($incomingCorrelationId);
        }

        return (string) Str::uuid();
    }

    public function isValid(?string $correlationId): bool
    {
        if ($correlationId === null || $correlationId === '') {
            return false;
        }

        return (bool) preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i',
            $correlationId,
        );
    }
}
