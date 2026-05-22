<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

class BootstrapPayloadDiffTool
{
    /**
     * @param array<string, mixed> $authoritative
     * @param array<string, mixed> $shadow
     * @return array<string, mixed>
     */
    public function diff(array $authoritative, array $shadow): array
    {
        $flattenedAuthoritative = $this->flatten($authoritative);
        $flattenedShadow = $this->flatten($shadow);

        $paths = array_values(array_unique([
            ...array_keys($flattenedAuthoritative),
            ...array_keys($flattenedShadow),
        ]));
        sort($paths);

        $diffs = [];

        foreach ($paths as $path) {
            $authoritativeExists = array_key_exists($path, $flattenedAuthoritative);
            $shadowExists = array_key_exists($path, $flattenedShadow);

            if (!$authoritativeExists || !$shadowExists) {
                $diffs[$path] = [
                    'authoritative_present' => $authoritativeExists,
                    'shadow_present' => $shadowExists,
                    'authoritative' => $flattenedAuthoritative[$path] ?? null,
                    'shadow' => $flattenedShadow[$path] ?? null,
                ];

                continue;
            }

            if ($flattenedAuthoritative[$path] !== $flattenedShadow[$path]) {
                $diffs[$path] = [
                    'authoritative' => $flattenedAuthoritative[$path],
                    'shadow' => $flattenedShadow[$path],
                ];
            }
        }

        return $diffs;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function flatten(array $payload, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $flattened += $this->flatten($value, $path);
                continue;
            }

            $flattened[$path] = $value;
        }

        return $flattened;
    }
}
