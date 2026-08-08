<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapResolutionMetadata;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;
use App\Models\User;
use App\Support\FeatureFlags\FeatureFlag;
use Illuminate\Support\Facades\Log;

class BootstrapShadowParityService
{
    public function __construct(
        private readonly BootstrapPayloadDiffTool $payloadDiffTool,
    ) {}

    public function compare(
        User $user,
        GetBootstrapResponseDTO $authoritative,
        GetBootstrapResponseDTO $shadow,
        BootstrapResolutionMetadata $metadata,
        string $shadowPath,
    ): void {
        $authoritativePayload = BootstrapPayloadSerializer::toArray($authoritative);
        $shadowPayload = BootstrapPayloadSerializer::toArray($shadow);
        $diffs = $this->payloadDiffTool->diff($authoritativePayload, $shadowPayload);

        $storesParity = ($authoritativePayload['stores'] ?? []) === ($shadowPayload['stores'] ?? []);
        $activeStoreParity = ($authoritativePayload['active_store'] ?? null) === ($shadowPayload['active_store'] ?? null);
        $onboardingParity = ($authoritativePayload['onboarding'] ?? null) === ($shadowPayload['onboarding'] ?? null);
        $actorContextParity = ($authoritativePayload['actor_context'] ?? null) === ($shadowPayload['actor_context'] ?? null);
        $permissionsParity = ($authoritativePayload['permissions'] ?? []) === ($shadowPayload['permissions'] ?? []);
        $fieldPresenceParity = array_keys($this->payloadDiffTool->diff(
            $this->presenceOnly($authoritativePayload),
            $this->presenceOnly($shadowPayload),
        )) === [];

        $parityCounters = [
            'matched_sections' => count(array_filter([
                $storesParity,
                $activeStoreParity,
                $onboardingParity,
                $actorContextParity,
                $permissionsParity,
                $fieldPresenceParity,
            ])),
            'total_sections' => 6,
        ];

        $context = [
            'actor_id' => (int) $user->id,
            'shadow_path' => $shadowPath,
            'drift_count' => count($diffs),
            'has_drift' => $diffs !== [],
            'diff_paths' => array_keys($diffs),
            'diffs' => $diffs,
            'stores_parity' => $storesParity,
            'active_store_parity' => $activeStoreParity,
            'onboarding_parity' => $onboardingParity,
            'actor_context_parity' => $actorContextParity,
            'permission_payload_parity' => $permissionsParity,
            'field_presence_parity' => $fieldPresenceParity,
            'parity_counters' => $parityCounters,
            'flag_state' => $this->flagState(),
            ...$metadata->toLogContext(),
        ];

        Log::info('bootstrap.parity.checked', $context);
        Log::info('bootstrap.parity.counter', [
            'actor_id' => (int) $user->id,
            'shadow_path' => $shadowPath,
            'drift_count' => count($diffs),
            'has_drift' => $diffs !== [],
            'parity_counters' => $parityCounters,
            'stores_parity' => $storesParity,
            'active_store_parity' => $activeStoreParity,
            'onboarding_parity' => $onboardingParity,
            'actor_context_parity' => $actorContextParity,
            'permission_payload_parity' => $permissionsParity,
            'flag_state' => $this->flagState(),
            ...$metadata->toLogContext(),
        ]);

        if ($diffs !== []) {
            Log::warning('bootstrap.parity.drift_detected', $context);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function presenceOnly(array $payload): array
    {
        $presence = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $presence[$key] = $this->presenceOnly($value);
                continue;
            }

            $presence[$key] = '__present__';
        }

        return $presence;
    }

    /**
     * @return array<string, bool>
     */
    private function flagState(): array
    {
        return [
            'bootstrap.v2.enabled' => FeatureFlag::enabled('bootstrap.v2.enabled'),
            'bootstrap.shadow_read' => FeatureFlag::enabled('bootstrap.shadow_read'),
            'rbac.resolver.v2' => FeatureFlag::enabled('rbac.resolver.v2'),
            'rbac.dual_resolve' => FeatureFlag::enabled('rbac.dual_resolve'),
        ];
    }
}
