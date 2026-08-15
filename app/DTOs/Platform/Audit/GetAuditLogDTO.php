<?php

declare(strict_types=1);

namespace App\DTOs\Platform\Audit;

class GetAuditLogDTO
{
    public function __construct(
        public readonly int $logId,
    ) {}
}
