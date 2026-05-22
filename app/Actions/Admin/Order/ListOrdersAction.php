<?php

namespace App\Actions\Admin\Order;

use App\DTOs\Admin\Order\ListOrdersDTO;
use App\Enums\RoleEnum;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Repositories\Admin\Order\AdminOrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ListOrdersAction
{
    public function __construct(
        private AdminOrderRepository $repository,
    ) {}

    public function execute(ListOrdersDTO $dto): LengthAwarePaginator
    {
        return $this->repository->listForStore(
            storeId: $dto->storeId,
            search: $dto->search,
            status: $dto->status,
            perPage: $dto->perPage,
        );
    }
}
