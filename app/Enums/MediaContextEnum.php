<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaContextEnum: string
{
    case PRODUCTS = 'products';
    case VARIANTS = 'variants';
    case BRANDS = 'brands';
    case CATEGORIES = 'categories';
    case HERO_BANNERS = 'hero';
    case TAGS = 'tags';
    case STORES = 'stores';
    case CMS = 'cms';

    /**
     * Get all enum values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get storage directory path for context.
     */
    public function getStoragePath(): string
    {
        return $this->value;
    }

    /**
     * Validate if path belongs to this context.
     */
    public function validatePath(string $path): bool
    {
        return str_starts_with($path, $this->value . '/');
    }
}
