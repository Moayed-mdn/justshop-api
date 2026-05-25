<?php

declare(strict_types=1);

namespace App\Enums\Cms\Marketing;

/**
 * Marketing Section Types
 * 
 * Shared enum for section classification
 */
enum MarketingSectionTypeEnum: string
{
    case HERO = 'hero';
    case FEATURES = 'features';
    case PRODUCTS = 'products';
    case PRICING = 'pricing';
    case TESTIMONIALS = 'testimonials';
    case CTA = 'cta';
    case FAQ = 'faq';
    case GALLERY = 'gallery';
    case VIDEO = 'video';
    case CUSTOM = 'custom';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
