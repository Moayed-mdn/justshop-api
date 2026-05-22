<?php

declare(strict_types=1);

namespace App\Actions\Storefront\Account;

use App\DTOs\Storefront\Account\StorefrontAccountBootstrapDTO;
use App\Enums\Auth\ActorContextEnum;
use App\Exceptions\Domain\InvalidIdentityDomainAccessException;
use App\Models\User;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionBoundaryMetadataResolver;
use Illuminate\Http\Request;

class GetStorefrontAccountBootstrapAction
{
    public function __construct(
        private readonly IdentityContextResolver $identityContextResolver,
        private readonly SessionBoundaryMetadataResolver $sessionBoundaryMetadataResolver,
    ) {}

    public function execute(Request $request, User $user): StorefrontAccountBootstrapDTO
    {
        $identityContext = $this->identityContextResolver->resolve($user);

        if ($identityContext->actorType !== ActorContextEnum::CUSTOMER) {
            throw new InvalidIdentityDomainAccessException(__('auth.customer_account_customer_only'));
        }

        return new StorefrontAccountBootstrapDTO(
            user: $user,
            identityContext: $identityContext,
            sessionBoundary: $this->sessionBoundaryMetadataResolver->resolve($request, $identityContext),
        );
    }
}
