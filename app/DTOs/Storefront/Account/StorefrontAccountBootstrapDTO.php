<?php

declare(strict_types=1);

namespace App\DTOs\Storefront\Account;

use App\DTOs\Auth\Identity\IdentityContext;
use App\DTOs\Auth\Identity\SessionBoundaryMetadata;
use App\Models\User;

final readonly class StorefrontAccountBootstrapDTO
{
    public function __construct(
        public User $user,
        public IdentityContext $identityContext,
        public SessionBoundaryMetadata $sessionBoundary,
    ) {}
}
