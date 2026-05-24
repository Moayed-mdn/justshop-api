<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

class FeatureFlag
{
    /**
     * Check if a feature flag is enabled
     */
    public static function enabled(string $flag): bool
    {
        $features = static::all();
        $config = $features[$flag] ?? null;

        if ($config === null) {
            throw new \InvalidArgumentException("Feature flag '{$flag}' is not registered in config/features.php");
        }

        if (is_array($config)) {
            return (bool) ($config['default'] ?? false);
        }

        return (bool) $config;
    }

    /**
     * Check if a feature flag is disabled
     */
    public static function disabled(string $flag): bool
    {
        return !static::enabled($flag);
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
}
