<?php

namespace App\Enums\Theme;

enum BlockTypeEnum: string
{
    case LOGO = 'logo';
    case NAVIGATION = 'navigation';
    case SEARCH = 'search';
    case CART = 'cart';
    case TEXT = 'text';
    case IMAGE = 'image';
    case BUTTON = 'button';
    case PRODUCT_LIST = 'product_list';
    case CATEGORY_LIST = 'category_list';
    case SOCIAL_LINKS = 'social_links';
    case COPYRIGHT = 'copyright';
    case HTML = 'html';
    case SPACER = 'spacer';
    case DIVIDER = 'divider';
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
            self::LOGO => 'Logo',
            self::NAVIGATION => 'Navigation Menu',
            self::SEARCH => 'Search Bar',
            self::CART => 'Shopping Cart',
            self::TEXT => 'Text Block',
            self::IMAGE => 'Image',
            self::BUTTON => 'Button',
            self::PRODUCT_LIST => 'Product List',
            self::CATEGORY_LIST => 'Category List',
            self::SOCIAL_LINKS => 'Social Media Links',
            self::COPYRIGHT => 'Copyright Notice',
            self::HTML => 'Custom HTML',
            self::SPACER => 'Spacer',
            self::DIVIDER => 'Divider',
            self::CUSTOM => 'Custom Block',
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
