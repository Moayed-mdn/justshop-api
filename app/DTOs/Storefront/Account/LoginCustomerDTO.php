<?php

declare(strict_types=1);

namespace App\DTOs\Storefront\Account;

use App\Http\Requests\Storefront\Account\LoginCustomerRequest;

final readonly class LoginCustomerDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(LoginCustomerRequest $request): self
    {
        return new self(
            email: (string) $request->string('email'),
            password: (string) $request->string('password'),
        );
    }
}
