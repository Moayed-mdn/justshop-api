<?php

namespace App\DTOs\Admin\User;

use App\Enums\Store\StoreRoleEnum;
use App\Http\Requests\Admin\User\CreateUserRequest;

class CreateUserDTO
{
    public function __construct(
        public int $storeId,
        public string $name,
        public string $email,
        public string $password,
        public StoreRoleEnum $role,
    ) {}

    public static function fromRequest(CreateUserRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            name: $request->string('name')->trim()->toString(),
            email: $request->string('email')->trim()->toString(),
            password: $request->string('password')->toString(),
            role: StoreRoleEnum::from($request->string('role')->toString()),
        );
    }
}
