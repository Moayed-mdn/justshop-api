<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapResolutionMetadata;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;

class BootstrapDependencyProfiler
{
    /**
     * @return array<string, mixed>
     */
    public function profile(GetBootstrapResponseDTO $response, BootstrapResolutionMetadata $metadata): array
    {
        $payload = BootstrapPayloadSerializer::toArray($response);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}';
        $permissionJson = json_encode($payload['permissions'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]';

        $sectionPresence = [
            'user' => isset($payload['user']),
            'stores' => isset($payload['stores']),
            'active_store' => array_key_exists('active_store', $payload) && $payload['active_store'] !== null,
            'onboarding' => isset($payload['onboarding']),
            'permissions' => isset($payload['permissions']),
            'capabilities' => isset($payload['capabilities']),
            'config' => isset($payload['config']),
            'actor_context' => isset($payload['actor_context']),
        ];

        return [
            'sections_requested' => array_keys($sectionPresence),
            'section_presence' => $sectionPresence,
            'resolver_timing_distribution' => $this->bucketizeResolverTimings($metadata->resolverTimingsMs),
            'store_count_distribution' => $this->bucketizeInt(count($response->stores), [0, 1, 3, 10]),
            'permission_payload_size_distribution' => $this->bucketizeInt(strlen($permissionJson), [0, 64, 256, 1024]),
            'permission_payload_size_bytes' => strlen($permissionJson),
            'bootstrap_payload_size_growth' => [
                'payload_size_bytes' => strlen($payloadJson),
                'payload_size_distribution' => $this->bucketizeInt(strlen($payloadJson), [0, 512, 1024, 4096, 8192]),
                'response_version' => $metadata->responseVersion,
            ],
            'store_count' => count($response->stores),
            'permission_count' => count($response->permissions),
        ];
    }

    /**
     * @param array<string, float> $resolverTimingsMs
     * @return array<string, string>
     */
    private function bucketizeResolverTimings(array $resolverTimingsMs): array
    {
        $distribution = [];

        foreach ($resolverTimingsMs as $resolver => $elapsedMs) {
            $distribution[$resolver] = $this->bucketizeFloat($elapsedMs, [1.0, 5.0, 10.0, 25.0, 50.0]);
        }

        return $distribution;
    }

    /**
     * @param int[] $thresholds
     */
    private function bucketizeInt(int $value, array $thresholds): string
    {
        foreach ($thresholds as $threshold) {
            if ($value <= $threshold) {
                return '<=' . $threshold;
            }
        }

        return '>' . end($thresholds);
    }

    /**
     * @param float[] $thresholds
     */
    private function bucketizeFloat(float $value, array $thresholds): string
    {
        foreach ($thresholds as $threshold) {
            if ($value <= $threshold) {
                return '<=' . number_format($threshold, 1, '.', '') . 'ms';
            }
        }

        $last = end($thresholds);

        return '>' . number_format((float) $last, 1, '.', '') . 'ms';
    }
}
