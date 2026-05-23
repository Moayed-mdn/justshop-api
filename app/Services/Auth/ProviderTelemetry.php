<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Auth\IdentityProviderEnum;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Provider Telemetry
 * 
 * Wave 6: Provider governance telemetry.
 */
class ProviderTelemetry
{
    public function logProviderResolution(User $user, IdentityProviderEnum $provider): void
    {
        Log::info('auth.provider.resolved', [
            'user_id' => $user->id,
            'actor_context' => $user->getActorContext()->value,
            'provider' => $provider->value,
        ]);
    }

    public function logProviderAssumptionDetected(string $assumption, array $metadata): void
    {
        Log::info('auth.provider.assumption_detected', [
            'assumption' => $assumption,
            'metadata' => $metadata,
        ]);
    }

    public function logProviderReadinessCheck(array $report): void
    {
        Log::info('auth.provider.readiness_checked', $report);
    }
}
