<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Support\Observability\MetadataSanitizer;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Support\Facades\Log;

class LogSecurityEventLogger implements SecurityEventLoggerInterface
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
        private readonly MetadataSanitizer $metadataSanitizer,
    ) {}

    public function record(SecurityEventType|string $event, array $metadata = [], string $level = 'warning'): void
    {
        $eventName = $event instanceof SecurityEventType ? $event->value : $event;
        $context = $this->traceContext->current();

        Log::channel((string) config('observability.security_log_channel'))->log($level, $eventName, [
            'event' => $eventName,
            'metadata' => $this->metadataSanitizer->sanitize($metadata),
            ...$context->toLogContext(),
        ]);
    }
}
