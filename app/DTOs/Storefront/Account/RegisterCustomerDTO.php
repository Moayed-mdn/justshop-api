<?php

declare(strict_types=1);

namespace App\DTOs\Storefront\Account;

use App\Http\Requests\Storefront\Account\RegisterCustomerRequest;

final readonly class RegisterCustomerDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(RegisterCustomerRequest $request): self
    {
        return new self(
            name: (string) $request->string('name'),
            email: (string) $request->string('email'),
            password: (string) $request->string('password'),
        );
    }
}
