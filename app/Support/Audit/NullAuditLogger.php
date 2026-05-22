<?php

declare(strict_types=1);

namespace App\Support\Audit;

use Illuminate\Support\Facades\Log;

class NullAuditLogger implements AuditLoggerInterface
{
    /**
     * Record a sensitive operational event.
     */
    public function record(string $event, array $metadata = []): void
    {
        Log::info("Audit Event: {$event}", [
            'metadata' => $metadata,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
