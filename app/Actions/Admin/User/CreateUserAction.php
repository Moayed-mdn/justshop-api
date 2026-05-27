<?php

namespace App\Actions\Admin\User;

use App\DTOs\Admin\User\CreateUserDTO;
use App\Models\User;
use App\Repositories\Admin\User\AdminUserRepository;

class CreateUserAction
{
    public function __construct(
        private AdminUserRepository $repository,
    ) {}

    public function execute(CreateUserDTO $dto): User
    {
        return $this->repository->create(
            storeId: $dto->storeId,
            name: $dto->name,
            email: $dto->email,
            password: $dto->password,
            role: $dto->role,
        );
    }
}
