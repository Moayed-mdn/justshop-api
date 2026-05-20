<?php

declare(strict_types=1);

namespace App\Services\Lead;

class LeadSanitizerService
{
    public function sanitizeText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public function sanitizeMessage(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;
        $value = preg_replace("/\n{3,}/u", "\n\n", $value) ?? $value;

        return trim($value);
    }

    public function sanitizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = $this->sanitizeText($value);

        return $sanitized !== '' ? $sanitized : null;
    }

    public function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $clean = $this->sanitizeText($value);

            if ($clean !== '') {
                $sanitized[(string) $key] = $clean;
            }
        }

        return $sanitized;
    }
}
