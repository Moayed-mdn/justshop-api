<?php

declare(strict_types=1);

namespace App\Support\Observability;

class MetadataSanitizer
{
    /**
     * @var list<string>
     */
    private array $redactedKeys = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'csrf',
    ];

    public function sanitize(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->shouldRedact($normalizedKey)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
                continue;
            }

            if (is_object($value)) {
                $sanitized[$key] = method_exists($value, '__toString')
                    ? (string) $value
                    : '[OBJECT]';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function shouldRedact(string $key): bool
    {
        foreach ($this->redactedKeys as $redactedKey) {
            if (str_contains($key, $redactedKey)) {
                return true;
            }
        }

        return false;
    }
}
