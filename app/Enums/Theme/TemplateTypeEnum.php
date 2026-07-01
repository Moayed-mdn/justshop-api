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
    case CHECKOUT_SUCCESS = 'checkout_success';
    case CHECKOUT_CANCEL = 'checkout_cancel';
    case SEARCH = 'search';
    case LOGIN = 'login';
    case REGISTER = 'register';
    case FORGOT_PASSWORD = 'forgot_password';
    case RESET_PASSWORD = 'reset_password';
    case VERIFY_EMAIL = 'verify_email';
    case ACCOUNT = 'account';
    case ORDERS = 'orders';
    case ORDER = 'order';
    case ORDER_TRACK = 'order_track';
    case CATEGORIES = 'categories';
    case BLOG = 'blog';
    case BLOG_POST = 'blog_post';
    case ERROR_404 = 'error_404';
    case ERROR_500 = 'error_500';
    case SHOP = 'shop';
    case HEADER_GROUP = 'header_group';
    case FOOTER_GROUP = 'footer_group';
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
            self::CHECKOUT_SUCCESS => 'Checkout Success',
            self::CHECKOUT_CANCEL => 'Checkout Cancelled',
            self::SEARCH => 'Search Results',
            self::LOGIN => 'Login Page',
            self::REGISTER => 'Register Page',
            self::FORGOT_PASSWORD => 'Forgot Password',
            self::RESET_PASSWORD => 'Reset Password',
            self::VERIFY_EMAIL => 'Verify Email',
            self::ACCOUNT => 'Account / Profile Page',
            self::ORDERS => 'Order History',
            self::ORDER => 'Order Detail',
            self::ORDER_TRACK => 'Order Tracking',
            self::CATEGORIES => 'All Categories',
            self::BLOG => 'Blog Index',
            self::BLOG_POST => 'Blog Post',
            self::ERROR_404 => '404 Not Found',
            self::ERROR_500 => '500 Server Error',
            self::SHOP => 'Shop Page',
            self::HEADER_GROUP => 'Header Section Group',
            self::FOOTER_GROUP => 'Footer Section Group',
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

    /**
     * Check if this template type is a system page (not a CMS/marketing page).
     */
    public function isSystemPage(): bool
    {
        return match ($this) {
            self::PAGE, self::HOME, self::CUSTOM => false,
            default => true,
        };
    }

    /**
     * Check if this template type is a section group (layout-level, not a page).
     */
    public function isSectionGroup(): bool
    {
        return match ($this) {
            self::HEADER_GROUP, self::FOOTER_GROUP => true,
            default => false,
        };
    }

    /**
     * Get the route name pattern for this template type.
     */
    public function routePattern(): string
    {
        return match ($this) {
            self::HOME => '/',
            self::PRODUCT => '/shop/product/{slug}',
            self::CATEGORY => '/shop/category/{slug}',
            self::COLLECTION => '/shop/collection/{slug}',
            self::PAGE => '/{slug}',
            self::CART => '/cart',
            self::CHECKOUT => '/checkout/*',
            self::CHECKOUT_SUCCESS => '/checkout/success',
            self::CHECKOUT_CANCEL => '/checkout/cancel',
            self::SEARCH => '/search',
            self::LOGIN => '/login',
            self::REGISTER => '/register',
            self::FORGOT_PASSWORD => '/forgot-password',
            self::RESET_PASSWORD => '/reset-password',
            self::VERIFY_EMAIL => '/verify-email/{id}/{hash}',
            self::ACCOUNT => '/profile',
            self::ORDERS => '/orders',
            self::ORDER => '/orders/{orderNumber}',
            self::ORDER_TRACK => '/orders/track',
            self::CATEGORIES => '/categories',
            self::BLOG => '/blog',
            self::BLOG_POST => '/blog/{slug}',
            self::SHOP => '/shop',
            self::ERROR_404 => '/*',
            self::ERROR_500 => '/500',
            self::HEADER_GROUP => null,
            self::FOOTER_GROUP => null,
            self::CUSTOM => '/{slug}',
        };
    }
}
