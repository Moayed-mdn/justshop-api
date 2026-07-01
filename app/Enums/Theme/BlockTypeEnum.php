<?php

namespace App\Enums\Theme;

enum BlockTypeEnum: string
{
    // Header/footer blocks
    case LOGO = 'logo';
    case NAVIGATION = 'navigation';
    case SEARCH = 'search';
    case CART = 'cart';
    case LANGUAGE_SELECTOR = 'language_selector';
    case SOCIAL_LINKS = 'social_links';
    case COPYRIGHT = 'copyright';
    case PAYMENT_ICONS = 'payment_icons';

    // Content blocks
    case TEXT = 'text';
    case IMAGE = 'image';
    case BUTTON = 'button';
    case HTML = 'html';
    case SPACER = 'spacer';
    case DIVIDER = 'divider';
    case LINK = 'link';
    case LINK_GROUP = 'link_group';

    // Listing blocks
    case PRODUCT_LIST = 'product_list';
    case CATEGORY_LIST = 'category_list';

    // Commerce blocks
    case FEATURE = 'feature';
    case TESTIMONIAL = 'testimonial';
    case GALLERY_ITEM = 'gallery_item';
    case PRICING_PLAN = 'pricing_plan';
    case FAQ_ITEM = 'faq_item';
    case SLIDE = 'slide';
    case STAT = 'stat';
    case PROMISE = 'promise';
    case METRIC = 'metric';
    case TRUST_BADGE = 'trust_badge';

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
            // Header/footer
            self::LOGO => 'Logo',
            self::NAVIGATION => 'Navigation Menu',
            self::SEARCH => 'Search Bar',
            self::CART => 'Shopping Cart',
            self::LANGUAGE_SELECTOR => 'Language Selector',
            self::SOCIAL_LINKS => 'Social Media Links',
            self::COPYRIGHT => 'Copyright Notice',
            self::PAYMENT_ICONS => 'Payment Icons',
            // Content
            self::TEXT => 'Text Block',
            self::IMAGE => 'Image',
            self::BUTTON => 'Button',
            self::HTML => 'Custom HTML',
            self::SPACER => 'Spacer',
            self::DIVIDER => 'Divider',
            self::LINK => 'Link',
            self::LINK_GROUP => 'Link Group',
            // Listing
            self::PRODUCT_LIST => 'Product List',
            self::CATEGORY_LIST => 'Category List',
            // Commerce
            self::FEATURE => 'Feature',
            self::TESTIMONIAL => 'Testimonial',
            self::GALLERY_ITEM => 'Gallery Item',
            self::PRICING_PLAN => 'Pricing Plan',
            self::FAQ_ITEM => 'FAQ Item',
            self::SLIDE => 'Slide',
            self::STAT => 'Stat',
            self::PROMISE => 'Promise',
            self::METRIC => 'Metric',
            self::TRUST_BADGE => 'Trust Badge',
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
