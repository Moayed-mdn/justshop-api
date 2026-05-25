<?php

declare(strict_types=1);

namespace App\Enums\Cms\Marketing;

/**
 * Marketing Page Template Types
 * 
 * Shared enum for template classification
 */
enum MarketingPageTemplateEnum: string
{
    // Platform templates
    case HOME = 'home';
    case PRICING = 'pricing';
    case FEATURES = 'features';
    case ABOUT = 'about';
    
    // Store templates
    case LANDING = 'landing';
    case CAMPAIGN = 'campaign';
    case PROMOTION = 'promotion';
    
    // Shared
    case GENERIC = 'generic';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function platformTemplates(): array
    {
        return [
            self::HOME->value,
            self::PRICING->value,
            self::FEATURES->value,
            self::ABOUT->value,
            self::GENERIC->value,
        ];
    }

    public static function storeTemplates(): array
    {
        return [
            self::LANDING->value,
            self::CAMPAIGN->value,
            self::PROMOTION->value,
            self::GENERIC->value,
        ];
    }
}
