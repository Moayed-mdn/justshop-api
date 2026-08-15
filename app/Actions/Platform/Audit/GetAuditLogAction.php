<?php

declare(strict_types=1);

namespace App\Actions\Platform\Audit;

use App\DTOs\Platform\Audit\GetAuditLogDTO;
use App\Models\AuditLog;
use App\Repositories\Platform\AuditLogRepository;

class GetAuditLogAction
{
    public function __construct(
        private readonly AuditLogRepository $repository,
    ) {}

    /**
     * Get a single audit log by ID.
     */
    public function execute(GetAuditLogDTO $dto): AuditLog
    {
        $log = $this->repository->findById($dto->logId);

        if (!$log) {
            abort(404, __('error.audit_log_not_found'));
        }

        return $log;
    }
}
