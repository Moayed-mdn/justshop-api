<?php

declare(strict_types=1);

namespace App\Support\Observability;

use Stringable;

class MetadataSanitizer
{
    public function sanitize(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->shouldRedact($normalizedKey)) {
                $sanitized[$key] = $this->placeholder();
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
                continue;
            }

            if (is_object($value)) {
                $sanitized[$key] = $this->sanitizeObject($value);
                continue;
            }

            $sanitized[$key] = is_string($value)
                ? $this->sanitizeString($value)
                : $value;
        }

        return $sanitized;
    }

    public function sanitizeMessage(string $message): string
    {
        return $this->sanitizeString($message);
    }

    public function sanitizeThrowableTrace(string $trace): string
    {
        return $this->sanitizeString($trace);
    }

    private function shouldRedact(string $key): bool
    {
        foreach ($this->redactedKeys() as $redactedKey) {
            if (str_contains($key, $redactedKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function redactedKeys(): array
    {
        /** @var list<string> $keys */
        $keys = config('observability.log_redaction.sensitive_keys', []);

        return array_map(
            static fn (mixed $key): string => strtolower((string) $key),
            $keys,
        );
    }

    private function placeholder(): string
    {
        return (string) config('observability.log_redaction.placeholder', '[REDACTED]');
    }

    private function sanitizeObject(object $value): string
    {
        if ($value instanceof Stringable || method_exists($value, '__toString')) {
            return $this->sanitizeString((string) $value);
        }

        return '[OBJECT]';
    }

    private function sanitizeString(string $value): string
    {
        $sanitized = $value;

        foreach ($this->sensitiveQueryParameters() as $parameter) {
            $sanitized = preg_replace(
                sprintf('/(%s=)([^&\s]+)/i', preg_quote($parameter, '/')),
                '$1' . $this->placeholder(),
                $sanitized,
            ) ?? $sanitized;
        }

        foreach ($this->redactedKeys() as $key) {
            $sanitized = preg_replace(
                sprintf('/(%s["\']?\s*[:=]\s*["\']?)([^,"\']+)/i', preg_quote($key, '/')),
                '$1' . $this->placeholder(),
                $sanitized,
            ) ?? $sanitized;
        }

        $maxLength = (int) config('observability.log_redaction.max_string_length', 2048);

        if ($maxLength > 0 && strlen($sanitized) > $maxLength) {
            return substr($sanitized, 0, $maxLength) . '...[TRUNCATED]';
        }

        return $sanitized;
    }

    /**
     * @return list<string>
     */
    private function sensitiveQueryParameters(): array
    {
        /** @var list<string> $parameters */
        $parameters = config('observability.log_redaction.sensitive_query_parameters', []);

        return array_map(
            static fn (mixed $parameter): string => strtolower((string) $parameter),
            $parameters,
        );
    }
}
