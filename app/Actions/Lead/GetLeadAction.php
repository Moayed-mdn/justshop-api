<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\DTOs\Lead\GetLeadDTO;
use App\Models\Lead;
use App\Repositories\Lead\LeadRepository;

class GetLeadAction
{
    public function __construct(
        private LeadRepository $repository,
    ) {}

    public function execute(GetLeadDTO $dto): Lead
    {
        return $this->repository->findByIdOrFail($dto->id);
    }
}
