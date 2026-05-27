<?php

namespace App\Actions\Admin\User;

use App\DTOs\Admin\User\ListUsersDTO;
use App\Repositories\Admin\User\AdminUserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ListUsersAction
{
    public function __construct(
        private AdminUserRepository $repository,
    ) {}

    public function execute(ListUsersDTO $dto): LengthAwarePaginator
    {
        return $this->repository->listForStore(
            storeId: $dto->storeId,
            search: $dto->search,
            status: $dto->status,
            role: $dto->role,
            perPage: $dto->perPage,
            excludeUserId: Auth::id(),
        );
    }
}
