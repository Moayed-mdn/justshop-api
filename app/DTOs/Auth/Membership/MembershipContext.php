<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Membership;

final readonly class MembershipContext
{
    public function __construct(
        public int $membershipId,
        public int $userId,
        public int $storeId,
        public string $role,
        public string $source = 'store_user',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            'membership_id' => $this->membershipId,
            'membership_user_id' => $this->userId,
            'membership_store_id' => $this->storeId,
            'membership_role' => $this->role,
            'membership_source' => $this->source,
        ];
    }
}
