<?php

declare(strict_types=1);

namespace App\Enums\Cms\Marketing;

/**
 * Marketing Section Types
 * 
 * Shared enum for section classification.
 * Single source of truth for all valid marketing section types.
 * Add new section types here — the API and validation automatically stay in sync.
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

    public function label(): string
    {
        return match ($this) {
            self::CTA  => 'CTA',
            self::FAQ  => 'FAQ',
            default    => ucfirst(str_replace('_', ' ', $this->value)),
        };
    }

    /**
     * Return all cases as value/label pairs for API responses.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases(),
        );
    }
}
