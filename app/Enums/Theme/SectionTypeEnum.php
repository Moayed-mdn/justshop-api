<?php

namespace App\Enums\Theme;

enum SectionTypeEnum: string
{
    // Layout sections
    case HEADER = 'header';
    case FOOTER = 'footer';
    case ANNOUNCEMENT_BAR = 'announcement_bar';
    case HEADER_MAIN = 'header_main';
    case FOOTER_MAIN = 'footer_main';
    case COPYRIGHT_BAR = 'copyright_bar';

    // Content sections
    case HERO = 'hero';
    case FEATURES = 'features';
    case CONTENT = 'content';
    case CTA = 'cta';
    case FAQ = 'faq';
    case GALLERY = 'gallery';
    case VIDEO = 'video';
    case TESTIMONIALS = 'testimonials';
    case PRICING = 'pricing';
    case NEWSLETTER = 'newsletter';

    // Commerce sections
    case PRODUCTS = 'products';
    case PRODUCT_GRID = 'product_grid';
    case PRODUCT_DETAIL = 'product_detail';
    case CATEGORIES = 'categories';
    case CATEGORY_GRID = 'category_grid';

    // Cart sections
    case CART_ITEMS = 'cart_items';
    case CART_SUMMARY = 'cart_summary';
    case CART_EMPTY = 'cart_empty';

    // Search sections
    case SEARCH_FORM = 'search_form';
    case SEARCH_RESULTS = 'search_results';
    case SEARCH_FILTERS = 'search_filters';

    // Auth sections
    case LOGIN_FORM = 'login_form';
    case REGISTER_FORM = 'register_form';
    case FORGOT_PASSWORD_FORM = 'forgot_password_form';
    case RESET_PASSWORD_FORM = 'reset_password_form';
    case VERIFY_EMAIL_NOTICE = 'verify_email_notice';

    // Account sections
    case ACCOUNT_PROFILE = 'account_profile';
    case ACCOUNT_PASSWORD = 'account_password';
    case ACCOUNT_ORDERS = 'account_orders';
    case ACCOUNT_ADDRESSES = 'account_addresses';

    // Order sections
    case ORDER_DETAIL = 'order_detail';
    case ORDER_TRACKING = 'order_tracking';

    // Error sections
    case ERROR_404 = 'error_404';
    case ERROR_500 = 'error_500';

    // Legacy
    case FEATURED = 'featured';
    case BANNER = 'banner';
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
            // Layout
            self::HEADER => 'Header',
            self::FOOTER => 'Footer',
            self::ANNOUNCEMENT_BAR => 'Announcement Bar',
            self::HEADER_MAIN => 'Header Main',
            self::FOOTER_MAIN => 'Footer Main',
            self::COPYRIGHT_BAR => 'Copyright Bar',
            // Content
            self::HERO => 'Hero Banner',
            self::FEATURES => 'Feature List',
            self::CONTENT => 'Rich Content',
            self::CTA => 'Call to Action',
            self::FAQ => 'FAQ Accordion',
            self::GALLERY => 'Gallery / Team',
            self::VIDEO => 'Video',
            self::TESTIMONIALS => 'Testimonials',
            self::PRICING => 'Pricing Plans',
            self::NEWSLETTER => 'Newsletter Signup',
            // Commerce
            self::PRODUCTS => 'Products Section',
            self::PRODUCT_GRID => 'Product Grid',
            self::PRODUCT_DETAIL => 'Product Detail',
            self::CATEGORIES => 'Categories Section',
            self::CATEGORY_GRID => 'Category Grid',
            // Cart
            self::CART_ITEMS => 'Cart Items List',
            self::CART_SUMMARY => 'Cart Order Summary',
            self::CART_EMPTY => 'Cart Empty State',
            // Search
            self::SEARCH_FORM => 'Search Form',
            self::SEARCH_RESULTS => 'Search Results',
            self::SEARCH_FILTERS => 'Search Filters',
            // Auth
            self::LOGIN_FORM => 'Login Form',
            self::REGISTER_FORM => 'Register Form',
            self::FORGOT_PASSWORD_FORM => 'Forgot Password Form',
            self::RESET_PASSWORD_FORM => 'Reset Password Form',
            self::VERIFY_EMAIL_NOTICE => 'Email Verification Notice',
            // Account
            self::ACCOUNT_PROFILE => 'Account Profile',
            self::ACCOUNT_PASSWORD => 'Account Password',
            self::ACCOUNT_ORDERS => 'Account Orders',
            self::ACCOUNT_ADDRESSES => 'Account Addresses',
            // Order
            self::ORDER_DETAIL => 'Order Detail',
            self::ORDER_TRACKING => 'Order Tracking',
            // Error
            self::ERROR_404 => '404 Error Page',
            self::ERROR_500 => '500 Error Page',
            // Legacy
            self::FEATURED => 'Featured Content',
            self::BANNER => 'Banner',
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

    /**
     * Get the category this section type belongs to.
     */
    public function category(): string
    {
        return match ($this) {
            self::HEADER, self::FOOTER, self::ANNOUNCEMENT_BAR,
            self::HEADER_MAIN, self::FOOTER_MAIN, self::COPYRIGHT_BAR => 'layout',
            self::HERO, self::FEATURES, self::CONTENT, self::CTA,
            self::FAQ, self::GALLERY, self::VIDEO,
            self::TESTIMONIALS, self::PRICING, self::NEWSLETTER => 'content',
            self::PRODUCTS, self::PRODUCT_GRID, self::PRODUCT_DETAIL,
            self::CATEGORIES, self::CATEGORY_GRID,
            self::CART_ITEMS, self::CART_SUMMARY, self::CART_EMPTY,
            self::SEARCH_FORM, self::SEARCH_RESULTS, self::SEARCH_FILTERS => 'commerce',
            self::LOGIN_FORM, self::REGISTER_FORM, self::FORGOT_PASSWORD_FORM,
            self::RESET_PASSWORD_FORM, self::VERIFY_EMAIL_NOTICE,
            self::ACCOUNT_PROFILE, self::ACCOUNT_PASSWORD,
            self::ACCOUNT_ORDERS, self::ACCOUNT_ADDRESSES,
            self::ORDER_DETAIL, self::ORDER_TRACKING,
            self::ERROR_404, self::ERROR_500 => 'system',
            self::FEATURED, self::BANNER, self::CUSTOM => 'custom',
        };
    }
}
