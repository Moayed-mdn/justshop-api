<?php

declare(strict_types=1);

namespace App\Support;

class BlogReadingTimeCalculator
{
    public function calculate(array $translations): ?int
    {
        $minutes = collect($translations)
            ->map(fn (array $translation): ?int => $this->minutesForContent($translation['content'] ?? null))
            ->filter(static fn (?int $value): bool => $value !== null)
            ->max();

        return $minutes ?: null;
    }

    private function minutesForContent(?string $content): ?int
    {
        if ($content === null || trim($content) === '') {
            return null;
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $content, $matches);
        $wordCount = count($matches[0] ?? []);

        if ($wordCount === 0) {
            return null;
        }

        return max(1, (int) ceil($wordCount / 200));
    }
}
