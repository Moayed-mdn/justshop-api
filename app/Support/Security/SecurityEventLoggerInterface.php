<?php

declare(strict_types=1);

namespace App\Support\Security;

interface SecurityEventLoggerInterface
{
    public function record(SecurityEventType|string $event, array $metadata = [], string $level = 'warning'): void;
}
