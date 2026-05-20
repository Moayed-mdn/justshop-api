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
use Illuminate\Http\JsonResponse;

class AdminLeadController extends Controller
{
    public function index(
        ListLeadsRequest $request,
        ListLeadsAction $action,
    ): JsonResponse {
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
        $action->execute(DeleteLeadDTO::fromRequest($request, $lead));

        return $this->success(null, 'lead.deleted');
    }
}
