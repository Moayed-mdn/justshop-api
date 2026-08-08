<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use Illuminate\Support\Facades\Cache;

class FeatureFlag
{
    /**
     * Check if a feature flag is enabled
     */
    public static function enabled(string $flag): bool
    {
        $value = static::value($flag);

        if (!is_bool($value)) {
            throw new \InvalidArgumentException(
                "Feature flag '{$flag}' is not boolean; use " . self::class . "::value() instead."
            );
        }

        return $value;
    }

    /**
     * Check if a feature flag is disabled
     */
    public static function disabled(string $flag): bool
    {
        return !static::enabled($flag);
    }

    /**
     * Get the resolved runtime value for a feature flag.
     */
    public static function value(string $flag): mixed
    {
        $config = static::config($flag);
        $default = is_array($config) ? ($config['default'] ?? false) : $config;

        return Cache::get(static::overrideCacheKey($flag), $default);
    }

    /**
     * Get all resolved feature flag values.
     *
     * @return array<string, mixed>
     */
    public static function values(): array
    {
        $values = [];

        foreach (array_keys(static::all()) as $flag) {
            $values[$flag] = static::value($flag);
        }

        return $values;
    }

    /**
     * Get feature flag metadata
     */
    public static function metadata(string $flag): ?array
    {
        $features = static::all();
        $config = $features[$flag] ?? null;

        if (!is_array($config)) {
            return null;
        }

        return $config;
    }

    /**
     * Determine whether a runtime override exists for a feature flag.
     */
    public static function hasOverride(string $flag): bool
    {
        static::config($flag);

        return Cache::has(static::overrideCacheKey($flag));
    }

    /**
     * Persist a runtime override for a feature flag.
     */
    public static function setValue(string $flag, mixed $value): void
    {
        $config = static::config($flag);
        $default = is_array($config) ? ($config['default'] ?? false) : $config;

        if (gettype($value) !== gettype($default)) {
            throw new \InvalidArgumentException(
                "Feature flag '{$flag}' expects a value of type " . gettype($default) . '.'
            );
        }

        Cache::forever(static::overrideCacheKey($flag), $value);
        Cache::forever(static::updatedAtCacheKey($flag), now()->toISOString());
    }

    /**
     * Get the timestamp of the last runtime override.
     */
    public static function updatedAt(string $flag): ?string
    {
        static::config($flag);

        return Cache::get(static::updatedAtCacheKey($flag));
    }

    /**
     * Get all registered feature flags
     */
    public static function all(): array
    {
        return config('features', []);
    }

    /**
     * Get flags by category
     */
    public static function byCategory(string $category): array
    {
        $flags = static::all();
        $filtered = [];

        foreach ($flags as $name => $config) {
            if (is_array($config) && ($config['category'] ?? null) === $category) {
                $filtered[$name] = $config;
            }
        }

        return $filtered;
    }

    /**
     * Get flags by wave
     */
    public static function byWave(string $wave): array
    {
        $flags = static::all();
        $filtered = [];

        foreach ($flags as $name => $config) {
            if (is_array($config) && ($config['introduced_wave'] ?? null) === $wave) {
                $filtered[$name] = $config;
            }
        }

        return $filtered;
    }

    /**
     * Get all kill switches
     */
    public static function killSwitches(): array
    {
        $flags = static::all();
        $killSwitches = [];

        foreach ($flags as $name => $config) {
            if (is_array($config) && ($config['kill_switch'] ?? false) === true) {
                $killSwitches[$name] = $config;
            }
        }

        return $killSwitches;
    }

    /**
     * Validate flag configuration completeness
     */
    public static function validate(string $flag): array
    {
        $allFlags = static::all();
        
        if (!isset($allFlags[$flag])) {
            return ["Flag '{$flag}' is not registered"];
        }

        $config = $allFlags[$flag];
        $errors = [];

        if (!is_array($config)) {
            $errors[] = "Flag '{$flag}' must have metadata array";
            return $errors;
        }

        $requiredFields = [
            'default',
            'owner',
            'business_owner',
            'description',
            'blast_radius',
            'rollback_effect',
            'expiry_milestone',
            'category',
            'introduced_wave',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $errors[] = "Flag '{$flag}' missing required field: {$field}";
            }
        }

        return $errors;
    }

    /**
     * Validate all flags
     */
    public static function validateAll(): array
    {
        $flags = static::all();
        $allErrors = [];

        foreach (array_keys($flags) as $flag) {
            $errors = static::validate($flag);
            if (!empty($errors)) {
                $allErrors[$flag] = $errors;
            }
        }

        return $allErrors;
    }

    private static function config(string $flag): mixed
    {
        $features = static::all();
        $config = $features[$flag] ?? null;

        if ($config === null) {
            throw new \InvalidArgumentException("Feature flag '{$flag}' is not registered in config/features.php");
        }

        return $config;
    }

    private static function overrideCacheKey(string $flag): string
    {
        return "feature_registry.{$flag}.value";
    }

    private static function updatedAtCacheKey(string $flag): string
    {
        return "feature_registry.{$flag}.updated_at";
    }
}
