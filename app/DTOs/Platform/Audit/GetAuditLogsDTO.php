<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Audit;

use App\Http\Requests\Platform\Audit\GetAuditLogsRequest;

class GetAuditLogsDTO
{
    public function __construct(
        public readonly ?string $event = null,
        public readonly ?int $actorId = null,
        public readonly ?int $storeId = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?int $perPage = 20,
    ) {}

    public static function fromRequest(GetAuditLogsRequest $request): self
    {
        return new self(
            event: $request->validated('event'),
            actorId: $request->validated('actor_id') ? (int) $request->validated('actor_id') : null,
            storeId: $request->validated('store_id') ? (int) $request->validated('store_id') : null,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            perPage: $request->validated('per_page') ? (int) $request->validated('per_page') : 20,
        );
    }
}
