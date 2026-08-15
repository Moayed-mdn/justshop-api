<?php

declare(strict_types=1);

namespace App\Repositories\Platform;

use App\DTOs\Platform\Audit\GetAuditLogsDTO;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository
{
    /**
     * Get paginated audit logs with optional filters.
     */
    public function getPaginated(GetAuditLogsDTO $dto): LengthAwarePaginator
    {
        $query = AuditLog::query()->orderBy('created_at', 'desc');

        // Apply filters if provided
        if ($dto->event) {
            $query->where('event', $dto->event);
        }

        if ($dto->actorId) {
            $query->where('actor_id', $dto->actorId);
        }

        if ($dto->storeId) {
            $query->where('store_id', $dto->storeId);
        }

        if ($dto->startDate) {
            $query->where('created_at', '>=', $dto->startDate);
        }

        if ($dto->endDate) {
            $query->where('created_at', '<=', $dto->endDate);
        }

        return $query->paginate($dto->perPage ?? 20);
    }

    /**
     * Find an audit log by ID.
     */
    public function findById(int $id): ?AuditLog
    {
        return AuditLog::find($id);
    }
}
