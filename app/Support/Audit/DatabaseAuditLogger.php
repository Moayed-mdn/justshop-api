<?php

declare(strict_types=1);

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Support\Observability\MetadataSanitizer;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DatabaseAuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
        private readonly MetadataSanitizer $metadataSanitizer,
        private readonly Request $request,
    ) {}

    public function record(string $event, array $metadata = []): void
    {
        $context = $this->traceContext->current();

        AuditLog::create([
            'event' => $event,
            'actor_id' => $context->actorId,
            'actor_type' => $context->actorType,
            'membership_id' => $context->membershipId,
            'store_id' => $context->storeId,
            'correlation_id' => $context->correlationId,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'metadata' => $this->metadataSanitizer->sanitize($metadata),
            'created_at' => Carbon::now(),
        ]);
    }
}
