<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\DTOs\Lead\UpdateLeadStatusDTO;
use App\Enums\Lead\LeadStatusEnum;
use App\Models\Lead;
use App\Repositories\Lead\LeadRepository;
use Illuminate\Support\Facades\DB;

class UpdateLeadStatusAction
{
    public function __construct(
        private LeadRepository $repository,
    ) {}

    public function execute(UpdateLeadStatusDTO $dto): Lead
    {
        $lead = $this->repository->findByIdOrFail($dto->id);

        return DB::transaction(function () use ($lead, $dto): Lead {
            return $this->repository->updateStatus($lead, [
                'status' => $dto->status->value,
                'contacted_at' => $dto->status === LeadStatusEnum::CONTACTED
                    ? ($lead->contacted_at ?? now())
                    : $lead->contacted_at,
                'archived_at' => $dto->status === LeadStatusEnum::ARCHIVED ? now() : null,
                ...$this->resolveResolutionAttributes($lead, $dto->status, $dto->actorUserId),
            ]);
        });
    }

    private function resolveResolutionAttributes(
        Lead $lead,
        LeadStatusEnum $status,
        int $actorUserId,
    ): array {
        return match ($status) {
            LeadStatusEnum::CONTACTED,
            LeadStatusEnum::ARCHIVED,
            LeadStatusEnum::SPAM => [
                'resolved_at' => $lead->resolved_at ?? now(),
                'resolved_by' => $actorUserId,
            ],
            LeadStatusEnum::NEW,
            LeadStatusEnum::IN_PROGRESS => [
                'resolved_at' => null,
                'resolved_by' => null,
            ],
        };
    }
}
