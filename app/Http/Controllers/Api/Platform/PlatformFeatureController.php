<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Support\FeatureFlags\FeatureFlag;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PlatformFeatureController extends Controller
{
    /**
     * Expose the canonical feature flag registry with resolved runtime values.
     */
    public function index(): JsonResponse
    {
        $features = collect(FeatureFlag::all())
            ->map(fn ($config, string $feature) => $this->formatFeature($feature, $config))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    public function update(string $feature): JsonResponse
    {
        $metadata = FeatureFlag::metadata($feature);
        if ($metadata === null) {
            return response()->json([
                'success' => false,
                'message' => 'Feature flag not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $input = request()->has('value')
            ? request()->input('value')
            : request()->input('enabled');

        if ($input === null) {
            return response()->json([
                'success' => false,
                'message' => 'A value or enabled field is required',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $value = $this->coerceValue($input, $metadata['default'] ?? null, $feature);
            FeatureFlag::setValue($feature, $value);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'success' => true,
            'message' => 'Feature flag updated successfully',
            'data' => $this->formatFeature($feature, $metadata),
        ]);
    }

    /**
     * @param mixed $config
     * @return array<string, mixed>
     */
    private function formatFeature(string $feature, mixed $config): array
    {
        $metadata = is_array($config) ? $config : ['default' => $config];
        $value = FeatureFlag::value($feature);
        $type = gettype($metadata['default'] ?? null);

        return [
            'id' => $feature,
            'key' => $feature,
            'name' => $feature,
            'description' => $metadata['description'] ?? $feature,
            'type' => $type,
            'value' => $value,
            'enabled' => $type === 'boolean' ? $value : null,
            'critical' => (bool) ($metadata['kill_switch'] ?? false),
            'owner' => $metadata['owner'] ?? null,
            'business_owner' => $metadata['business_owner'] ?? null,
            'category' => $metadata['category'] ?? null,
            'blast_radius' => $metadata['blast_radius'] ?? null,
            'kill_switch' => (bool) ($metadata['kill_switch'] ?? false),
            'has_override' => FeatureFlag::hasOverride($feature),
            'updated_at' => FeatureFlag::updatedAt($feature),
        ];
    }

    private function coerceValue(mixed $input, mixed $default, string $feature): mixed
    {
        if (is_bool($default)) {
            if (is_bool($input)) {
                return $input;
            }

            if (is_string($input)) {
                $normalized = filter_var($input, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if ($normalized !== null) {
                    return $normalized;
                }
            }

            if (is_int($input)) {
                return (bool) $input;
            }

            throw new \InvalidArgumentException("Feature flag '{$feature}' expects a boolean value.");
        }

        if (is_string($default)) {
            if (!is_scalar($input)) {
                throw new \InvalidArgumentException("Feature flag '{$feature}' expects a scalar string value.");
            }

            return (string) $input;
        }

        return $input;
    }
}
