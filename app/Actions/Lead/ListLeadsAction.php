<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\DTOs\Lead\ListLeadsDTO;
use App\Repositories\Lead\LeadRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListLeadsAction
{
    public function __construct(
        private LeadRepository $repository,
    ) {}

    public function execute(ListLeadsDTO $dto): LengthAwarePaginator
    {
        return $this->repository->paginate($dto);
    }
}
