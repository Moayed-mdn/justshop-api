<?php

declare(strict_types=1);

namespace App\DTOs\Lead;

use App\Enums\Lead\LeadStatusEnum;
use App\Http\Requests\Admin\Lead\UpdateLeadStatusRequest;

class UpdateLeadStatusDTO
{
    public function __construct(
        public readonly int $id,
        public readonly LeadStatusEnum $status,
        public readonly int $actorUserId,
        public readonly ?string $resolutionNotes = null,
    ) {}

    public static function fromRequest(
        UpdateLeadStatusRequest $request,
        int $id,
    ): self {
        return new self(
            id: $id,
            status: LeadStatusEnum::from($request->string('status')->toString()),
            actorUserId: (int) $request->user()->id,
            resolutionNotes: $request->string('resolution_notes')->toString() !== ''
                ? $request->string('resolution_notes')->toString()
                : null,
        );
    }
}
