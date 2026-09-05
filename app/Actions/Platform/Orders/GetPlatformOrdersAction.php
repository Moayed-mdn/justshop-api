<?php

declare(strict_types=1);

namespace App\Actions\Platform\Orders;

use App\DTOs\Platform\Orders\GetPlatformOrdersDTO;
use App\Repositories\Platform\Order\PlatformOrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPlatformOrdersAction
{
    public function __construct(
        private PlatformOrderRepository $repository,
    ) {}

    public function execute(GetPlatformOrdersDTO $dto): LengthAwarePaginator
    {
        return $this->repository->list(
            search: $dto->search,
            status: $dto->status,
            storeId: $dto->storeId,
            perPage: $dto->perPage,
        );
    }
}
