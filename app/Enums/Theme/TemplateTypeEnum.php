<?php

namespace App\Enums\Theme;

enum TemplateTypeEnum: string
{
    case HOME = 'home';
    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case COLLECTION = 'collection';
    case PAGE = 'page';
    case CART = 'cart';
    case CHECKOUT = 'checkout';
    case SEARCH = 'search';
    case BLOG = 'blog';
    case BLOG_POST = 'blog_post';
    case ACCOUNT = 'account';
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
            self::HOME => 'Home Page',
            self::PRODUCT => 'Product Page',
            self::CATEGORY => 'Category Page',
            self::COLLECTION => 'Collection Page',
            self::PAGE => 'Static Page',
            self::CART => 'Shopping Cart',
            self::CHECKOUT => 'Checkout',
            self::SEARCH => 'Search Results',
            self::BLOG => 'Blog Index',
            self::BLOG_POST => 'Blog Post',
            self::ACCOUNT => 'Account Page',
            self::CUSTOM => 'Custom Template',
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
