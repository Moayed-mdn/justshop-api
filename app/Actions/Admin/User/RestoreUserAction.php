<?php

namespace App\Actions\Admin\User;

use App\DTOs\Admin\User\RestoreUserDTO;
use App\Enums\RoleEnum;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\User;
use App\Repositories\Admin\User\AdminUserRepository;

use Illuminate\Support\Facades\Auth;

class RestoreUserAction
{
    public function __construct(
        private AdminUserRepository $repository,
    ) {}

    public function execute(RestoreUserDTO $dto): User
    {
        return $this->repository->restore($dto->userId, $dto->storeId);
    }
}
