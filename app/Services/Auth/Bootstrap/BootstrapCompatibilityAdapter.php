<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapResolution;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;

class BootstrapCompatibilityAdapter
{
    public function adapt(BootstrapResolution $resolution): GetBootstrapResponseDTO
    {
        return new GetBootstrapResponseDTO(
            user: $resolution->user,
            stores: $resolution->stores,
            activeStore: $resolution->activeStore,
            onboarding: $resolution->onboarding,
            permissions: $resolution->permissions,
            capabilities: $resolution->capabilities,
            config: $resolution->config,
            actorContext: $resolution->actorContext,
        );
    }
}
