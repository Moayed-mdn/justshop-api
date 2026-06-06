<?php

namespace App\Enums\Theme;

enum SectionTypeEnum: string
{
    case HEADER = 'header';
    case FOOTER = 'footer';
    case HERO = 'hero';
    case PRODUCTS = 'products';
    case CATEGORIES = 'categories';
    case FEATURED = 'featured';
    case BANNER = 'banner';
    case TESTIMONIALS = 'testimonials';
    case NEWSLETTER = 'newsletter';
    case CUSTOM = 'custom';

    /**
     * Get all enum values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::HEADER => 'Header',
            self::FOOTER => 'Footer',
            self::HERO => 'Hero Banner',
            self::PRODUCTS => 'Products Section',
            self::CATEGORIES => 'Categories Section',
            self::FEATURED => 'Featured Content',
            self::BANNER => 'Banner',
            self::TESTIMONIALS => 'Testimonials',
            self::NEWSLETTER => 'Newsletter Signup',
            self::CUSTOM => 'Custom Section',
        };
    }

    /**
     * Get options for API responses
     */
    public static function options(): array
    {
        return array_map(
            fn(self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}
