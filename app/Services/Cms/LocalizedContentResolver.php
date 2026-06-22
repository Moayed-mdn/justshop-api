<?php

declare(strict_types=1);

namespace App\Services\Cms;

class LocalizedContentResolver
{
    public function resolveLocalizedField(
        array|string|null $value,
        string $locale,
        string $fallback = 'en',
    ): mixed {
        if (!is_array($value)) {
            return $value;
        }

        if ($this->isLocalizedMap($value)) {
            return $this->resolveLocalizedString($value, $locale, $fallback);
        }

        return $this->resolveLocalizedPayload($value, $locale, $fallback);
    }

    public function resolveLocalizedPayload(
        mixed $payload,
        string $locale,
        string $fallback = 'en',
    ): mixed {
        if (!is_array($payload)) {
            return $payload;
        }

        if ($this->isLocalizedMap($payload)) {
            return $this->resolveLocalizedString($payload, $locale, $fallback);
        }

        // Check if this is a nested locale structure (e.g., {en: {items: [...]}, ar: {items: [...]}})
        if ($this->isNestedLocaleStructure($payload)) {
            return $this->resolveNestedLocaleStructure($payload, $locale, $fallback);
        }

        $resolved = [];

        foreach ($payload as $key => $value) {
            $resolved[$key] = is_array($value)
                ? $this->resolveLocalizedPayload($value, $locale, $fallback)
                : $value;
        }

        return $resolved;
    }

    private function isNestedLocaleStructure(array $value): bool
    {
        if ($value === [] || array_is_list($value)) {
            return false;
        }

        $supportedLocales = config('content.editable_locales', ['en', 'ar']);

        // Check if all keys are locale codes
        if (count(array_diff(array_keys($value), $supportedLocales)) !== 0) {
            return false;
        }

        // Check if all values are arrays (nested structure)
        foreach ($value as $localeValue) {
            if (!is_array($localeValue)) {
                return false;
            }
        }

        return true;
    }

    private function resolveNestedLocaleStructure(
        array $value,
        string $locale,
        string $fallback,
    ): mixed {
        // Try to get the requested locale
        if (isset($value[$locale]) && is_array($value[$locale])) {
            return $this->resolveLocalizedPayload($value[$locale], $locale, $fallback);
        }

        // Fall back to the fallback locale
        if (isset($value[$fallback]) && is_array($value[$fallback])) {
            return $this->resolveLocalizedPayload($value[$fallback], $locale, $fallback);
        }

        // Return the first available locale
        foreach ($value as $localeValue) {
            if (is_array($localeValue)) {
                return $this->resolveLocalizedPayload($localeValue, $locale, $fallback);
            }
        }

        return $value;
    }

    private function isLocalizedMap(array $value): bool
    {
        if ($value === [] || array_is_list($value)) {
            return false;
        }

        $supportedLocales = config('content.editable_locales', ['en', 'ar']);

        // Check if all keys are locale codes
        if (count(array_diff(array_keys($value), $supportedLocales)) !== 0) {
            return false;
        }

        // Check if at least one value is a string (not a nested array/object)
        // If all values are arrays, this is a nested locale structure, not a simple string map
        foreach ($value as $localeValue) {
            if (!is_array($localeValue)) {
                return true; // Found at least one non-array value, treat as string map
            }
        }

        // All values are arrays, so this is not a simple localized string map
        return false;
    }

    private function resolveLocalizedString(
        array $value,
        string $locale,
        string $fallback,
    ): ?string {
        $resolved = $value[$locale] ?? $value[$fallback] ?? null;

        if (is_string($resolved) && $resolved !== '') {
            return $resolved;
        }

        foreach ($value as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return is_string($resolved) ? $resolved : null;
    }
}
