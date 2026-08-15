<?php

declare(strict_types=1);

namespace App\Actions\Platform\Audit;

use App\DTOs\Platform\Audit\GetAuditLogsDTO;
use App\Repositories\Platform\AuditLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetAuditLogsAction
{
    public function __construct(
        private readonly AuditLogRepository $repository,
    ) {}

    /**
     * Get paginated audit logs with optional filters.
     */
    public function execute(GetAuditLogsDTO $dto): LengthAwarePaginator
    {
        return $this->repository->getPaginated($dto);
    }
}
