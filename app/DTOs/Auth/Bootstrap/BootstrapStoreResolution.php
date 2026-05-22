<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

use App\DTOs\Auth\Membership\MembershipContext;
use App\Models\Store;

final readonly class BootstrapStoreResolution
{
    /**
     * @param BootstrapStoreDTO[] $stores
     * @param array<int, MembershipContext> $membershipsByStoreId
     */
    public function __construct(
        public array $stores,
        public ?BootstrapStoreDTO $activeStore,
        public ?Store $activeStoreModel,
        public array $membershipsByStoreId,
    ) {}
}
