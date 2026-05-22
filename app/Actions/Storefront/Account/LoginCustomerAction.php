<?php

declare(strict_types=1);

namespace App\Actions\Storefront\Account;

use App\DTOs\Storefront\Account\LoginCustomerDTO;
use App\Enums\Auth\ActorContextEnum;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Domain\InvalidIdentityDomainAccessException;
use App\Models\User;
use App\Services\Auth\IdentityContextResolver;
use Illuminate\Support\Facades\Hash;

class LoginCustomerAction
{
    public function __construct(
        private readonly IdentityContextResolver $identityContextResolver,
    ) {}

    public function execute(LoginCustomerDTO $dto): User
    {
        $user = User::where('email', $dto->email)->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        $identityContext = $this->identityContextResolver->resolve($user);

        if ($identityContext->actorType !== ActorContextEnum::CUSTOMER) {
            throw new InvalidIdentityDomainAccessException(__('auth.customer_account_customer_only'));
        }

        return $user;
    }
}
