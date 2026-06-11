<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\Actions\Billing\GetBillingBootstrapAction;
use App\DTOs\Auth\Bootstrap\BootstrapResolution;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;

class BootstrapCompatibilityAdapter
{
    public function __construct(
        private GetBillingBootstrapAction $getBillingBootstrapAction,
    ) {}

    public function adapt(BootstrapResolution $resolution): GetBootstrapResponseDTO
    {
        // Phase 2: Get billing data for the user
        $billing = null;
        if ($resolution->user->id) {
            try {
                $user = \App\Models\User::find($resolution->user->id);
                $activeStoreId = $resolution->activeStore?->id;
                
                if ($user) {
                    $billing = $this->getBillingBootstrapAction->execute($user, $activeStoreId);
                }
            } catch (\Exception $e) {
                // Log error but don't fail bootstrap
                \Illuminate\Support\Facades\Log::channel('billing')->error('billing.bootstrap.failed', [
                    'user_id' => $resolution->user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return new GetBootstrapResponseDTO(
            user: $resolution->user,
            stores: $resolution->stores,
            activeStore: $resolution->activeStore,
            onboarding: $resolution->onboarding,
            permissions: $resolution->permissions,
            capabilities: $resolution->capabilities,
            config: $resolution->config,
            actorContext: $resolution->actorContext,
            session: $resolution->session,
            billing: $billing,
        );
    }
}
