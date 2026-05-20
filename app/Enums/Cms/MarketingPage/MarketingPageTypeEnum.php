<?php

declare(strict_types=1);

namespace App\Enums\Cms\MarketingPage;

enum MarketingPageTypeEnum: string
{
    case HOME = 'home';
    case ABOUT = 'about';
    case CONTACT = 'contact';
    case FEATURES = 'features';
    case ENTERPRISE = 'enterprise';
    case PRICING = 'pricing';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
