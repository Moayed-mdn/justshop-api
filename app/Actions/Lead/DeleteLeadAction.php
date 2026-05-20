<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\DTOs\Lead\DeleteLeadDTO;
use App\Repositories\Lead\LeadRepository;

class DeleteLeadAction
{
    public function __construct(
        private LeadRepository $repository,
    ) {}

    public function execute(DeleteLeadDTO $dto): void
    {
        $lead = $this->repository->findByIdOrFail($dto->id);

        $this->repository->delete($lead);
    }
}
