<?php

declare(strict_types=1);

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;

final class MediaUrl
{
    public static function resolve(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $normalized = self::normalizeStorablePath($value);

        if ($normalized === null || $normalized === '') {
            return $normalized;
        }

        if (self::isAbsoluteUrl($normalized)) {
            return $normalized;
        }

        return Storage::url($normalized);
    }

    public static function normalizeStorablePath(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (!self::isAbsoluteUrl($value)) {
            return ltrim((string) preg_replace('#^/?storage/#', '', $value), '/');
        }

        $path = parse_url($value, PHP_URL_PATH);

        if (is_string($path) && preg_match('#^/storage/#', $path) === 1) {
            return ltrim((string) preg_replace('#^/?storage/#', '', $path), '/');
        }

        return $value;
    }

    public static function shouldResolve(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (self::isAbsoluteUrl($value)) {
            return true;
        }

        if (str_starts_with($value, '/storage/')) {
            return true;
        }

        return preg_match('/\.(png|jpe?g|gif|webp|svg|bmp|ico|avif)$/i', $value) === 1
            && str_contains($value, '/');
    }

    private static function isAbsoluteUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }
}
