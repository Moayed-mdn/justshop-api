<?php

declare(strict_types=1);

namespace App\Support\Audit;

interface AuditLoggerInterface
{
    /**
     * Record a sensitive operational event.
     */
    public function record(string $event, array $metadata = []): void;
}
