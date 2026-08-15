<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Actions\Platform\Audit\GetAuditLogAction;
use App\Actions\Platform\Audit\GetAuditLogsAction;
use App\DTOs\Platform\Audit\GetAuditLogDTO;
use App\DTOs\Platform\Audit\GetAuditLogsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Audit\GetAuditLogsRequest;
use App\Http\Resources\Platform\AuditLogResource;
use App\Policies\Platform\AuditLogPolicy;
use Illuminate\Http\JsonResponse;

/**
 * Platform Audit Controller
 * 
 * Thin controller following architecture rules.
 */
class PlatformAuditController extends Controller
{
    public function __construct(
        private readonly GetAuditLogsAction $getAuditLogsAction,
        private readonly GetAuditLogAction $getAuditLogAction,
    ) {}

    public function index(GetAuditLogsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLogPolicy::class);

        $logs = $this->getAuditLogsAction->execute(
            GetAuditLogsDTO::fromRequest($request)
        );

        return $this->paginated($logs, AuditLogResource::collection($logs));
    }

    public function show(int $log): JsonResponse
    {
        $this->authorize('viewAny', AuditLogPolicy::class);

        $auditLog = $this->getAuditLogAction->execute(
            new GetAuditLogDTO(logId: $log)
        );

        return $this->success(new AuditLogResource($auditLog));
    }
}
