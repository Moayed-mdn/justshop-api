<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Permission;

final readonly class CapabilityResolutionResult
{
    /**
     * @param string[] $capabilities
     */
    public function __construct(
        public array $capabilities,
        public string $authority,
        public string $resolutionPath,
        public ?int $storeId,
        public ?int $membershipId,
        public ?string $membershipRole,
        public bool $storeScoped,
        public bool $superAdminBypass,
    ) {}

    /**
     * @return string[]
     */
    public function permissions(): array
    {
        $permissions = array_values(array_unique($this->capabilities));
        sort($permissions);

        return $permissions;
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            'authority' => $this->authority,
            'resolution_path' => $this->resolutionPath,
            'store_id' => $this->storeId,
            'membership_id' => $this->membershipId,
            'membership_role' => $this->membershipRole,
            'store_scoped' => $this->storeScoped,
            'super_admin_bypass' => $this->superAdminBypass,
            'capability_count' => count($this->capabilities),
            'capabilities' => $this->permissions(),
        ];
    }
}
