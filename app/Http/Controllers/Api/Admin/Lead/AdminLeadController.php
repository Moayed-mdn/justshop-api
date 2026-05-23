<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Lead;

use App\Actions\Lead\DeleteLeadAction;
use App\Actions\Lead\GetLeadAction;
use App\Actions\Lead\ListLeadsAction;
use App\Actions\Lead\UpdateLeadStatusAction;
use App\DTOs\Lead\DeleteLeadDTO;
use App\DTOs\Lead\GetLeadDTO;
use App\DTOs\Lead\ListLeadsDTO;
use App\DTOs\Lead\UpdateLeadStatusDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lead\DeleteLeadRequest;
use App\Http\Requests\Admin\Lead\GetLeadRequest;
use App\Http\Requests\Admin\Lead\ListLeadsRequest;
use App\Http\Requests\Admin\Lead\UpdateLeadStatusRequest;
use App\Http\Resources\Admin\Lead\AdminLeadResource;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class AdminLeadController extends Controller
{
    public function index(
        ListLeadsRequest $request,
        ListLeadsAction $action,
    ): JsonResponse {
        // Wave 2 Remediation: Add explicit policy authorization for platform-level admin resource
        $this->authorize('viewAny', Lead::class);
        
        $leads = $action->execute(ListLeadsDTO::fromRequest($request));

        return $this->paginated(
            $leads,
            AdminLeadResource::collection($leads)
        );
    }

    public function show(
        GetLeadRequest $request,
        GetLeadAction $action,
        int $lead,
    ): JsonResponse {
        // Wave 2 Remediation: Add explicit policy authorization for platform-level admin resource
        $leadModel = Lead::findOrFail($lead);
        $this->authorize('view', $leadModel);
        
        $leadModel = $action->execute(
            GetLeadDTO::fromRequest($request, $lead)
        );

        return $this->success(new AdminLeadResource($leadModel));
    }

    public function updateStatus(
        UpdateLeadStatusRequest $request,
        UpdateLeadStatusAction $action,
        int $lead,
    ): JsonResponse {
        // Wave 2 Remediation: Add explicit policy authorization for platform-level admin resource
        $leadModel = Lead::findOrFail($lead);
        $this->authorize('update', $leadModel);
        
        $leadModel = $action->execute(
            UpdateLeadStatusDTO::fromRequest($request, $lead)
        );

        return $this->success(
            new AdminLeadResource($leadModel),
            'lead.status_updated'
        );
    }

    public function destroy(
        DeleteLeadRequest $request,
        DeleteLeadAction $action,
        int $lead,
    ): JsonResponse {
        // Wave 2 Remediation: Add explicit policy authorization for platform-level admin resource
        $leadModel = Lead::findOrFail($lead);
        $this->authorize('delete', $leadModel);
        
        $action->execute(DeleteLeadDTO::fromRequest($request, $lead));

        return $this->success(null, 'lead.deleted');
    }
}
