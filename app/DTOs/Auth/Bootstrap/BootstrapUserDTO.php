<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

use App\Models\User;

class BootstrapUserDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $avatarUrl,
        public bool $isEmailVerified,
        public ?string $emailVerifiedAt,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: (int) $user->id,
            name: $user->name,
            email: $user->email,
            avatarUrl: $user->getAvatarUrl(),
            isEmailVerified: $user->hasVerifiedEmail(),
            emailVerifiedAt: $user->email_verified_at?->toIso8601String(),
        );
    }
}
