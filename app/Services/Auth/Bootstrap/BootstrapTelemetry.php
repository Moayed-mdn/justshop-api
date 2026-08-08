<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapResolutionMetadata;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;
use App\Models\User;
use App\Support\FeatureFlags\FeatureFlag;
use Illuminate\Support\Facades\Log;

class BootstrapTelemetry
{
    public function __construct(
        private readonly BootstrapDependencyProfiler $dependencyProfiler,
    ) {}
    public function measure(string $resolver, callable $callback): array
    {
        $startedAt = microtime(true);
        $value = $callback();
        $elapsedMs = round((microtime(true) - $startedAt) * 1000, 3);

        Log::info('bootstrap.resolver.timed', [
            'resolver' => $resolver,
            'elapsed_ms' => $elapsedMs,
            'elapsed_bucket' => $this->bucketizeElapsedMs($elapsedMs),
        ]);

        return [
            'value' => $value,
            'elapsed_ms' => $elapsedMs,
        ];
    }

    public function logStarted(User $user, string $authorityPath, string $responseVersion): void
    {
        Log::info('bootstrap.resolution.started', [
            'actor_id' => (int) $user->id,
            'authority_path' => $authorityPath,
            'response_version' => $responseVersion,
            'flag_state' => $this->flagState(),
        ]);
    }

    public function logCompleted(User $user, BootstrapResolutionMetadata $metadata, GetBootstrapResponseDTO $response): void
    {
        $dependencyProfile = $this->dependencyProfiler->profile($response, $metadata);

        Log::info('bootstrap.resolution.completed', [
            'actor_id' => (int) $user->id,
            'store_count' => count($response->stores),
            'has_active_store' => $response->activeStore !== null,
            'permission_count' => count($response->permissions),
            'flag_state' => $this->flagState(),
            ...$dependencyProfile,
            ...$metadata->toLogContext(),
        ]);

        Log::info('bootstrap.dependencies.profiled', [
            'actor_id' => (int) $user->id,
            ...$dependencyProfile,
            ...$metadata->toLogContext(),
        ]);
    }

    private function bucketizeElapsedMs(float $elapsedMs): string
    {
        foreach ([1.0, 5.0, 10.0, 25.0, 50.0] as $threshold) {
            if ($elapsedMs <= $threshold) {
                return '<=' . number_format($threshold, 1, '.', '') . 'ms';
            }
        }

        return '>50.0ms';
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
