<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Lead;

use App\Actions\Lead\CreateLeadAction;
use App\DTOs\Lead\CreateLeadDTO;
use App\Enums\Lead\LeadTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\ContactLeadRequest;
use App\Http\Requests\Lead\DemoLeadRequest;
use App\Http\Requests\Lead\EnterpriseLeadRequest;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function contact(
        ContactLeadRequest $request,
        CreateLeadAction $action,
    ): JsonResponse {
        $action->execute(
            CreateLeadDTO::fromRequest($request, LeadTypeEnum::CONTACT)
        );

        return $this->success(null, 'lead.submitted', 201);
    }

    public function demo(
        DemoLeadRequest $request,
        CreateLeadAction $action,
    ): JsonResponse {
        $action->execute(
            CreateLeadDTO::fromRequest($request, LeadTypeEnum::DEMO)
        );

        return $this->success(null, 'lead.submitted', 201);
    }

    public function enterprise(
        EnterpriseLeadRequest $request,
        CreateLeadAction $action,
    ): JsonResponse {
        $action->execute(
            CreateLeadDTO::fromRequest($request, LeadTypeEnum::ENTERPRISE)
        );

        return $this->success(null, 'lead.submitted', 201);
    }
}
