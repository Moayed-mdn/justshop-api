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

        $resolved = [];

        foreach ($payload as $key => $value) {
            $resolved[$key] = is_array($value)
                ? $this->resolveLocalizedPayload($value, $locale, $fallback)
                : $value;
        }

        return $resolved;
    }

    private function isLocalizedMap(array $value): bool
    {
        if ($value === [] || array_is_list($value)) {
            return false;
        }

        $supportedLocales = config('content.editable_locales', ['en', 'ar']);

        return count(array_diff(array_keys($value), $supportedLocales)) === 0;
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
