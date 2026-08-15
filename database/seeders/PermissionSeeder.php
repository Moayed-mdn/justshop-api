<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create all permissions from PermissionEnum
        $permissions = [
            PermissionEnum::USER_VIEW,
            PermissionEnum::USER_CREATE,
            PermissionEnum::USER_BLOCK,
            PermissionEnum::USER_DELETE,
            PermissionEnum::USER_RESTORE,
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::PRODUCT_CREATE,
            PermissionEnum::PRODUCT_UPDATE,
            PermissionEnum::PRODUCT_DELETE,
            PermissionEnum::PRODUCT_RESTORE,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
            PermissionEnum::PLATFORM_ORDER_VIEW,
            PermissionEnum::PLATFORM_ORDER_UPDATE_STATUS,
            PermissionEnum::PLATFORM_ORDER_CANCEL,
            PermissionEnum::PLATFORM_ORDER_REFUND,
            PermissionEnum::STORE_UPDATE,
            PermissionEnum::STORE_DELETE,
            PermissionEnum::STORE_VIEW,
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::CATEGORY_CREATE,
            PermissionEnum::CATEGORY_UPDATE,
            PermissionEnum::CATEGORY_DELETE,
            PermissionEnum::CATEGORY_RESTORE,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::BRAND_CREATE,
            PermissionEnum::BRAND_UPDATE,
            PermissionEnum::BRAND_DELETE,
            PermissionEnum::BRAND_RESTORE,
            PermissionEnum::TAG_VIEW,
            PermissionEnum::TAG_CREATE,
            PermissionEnum::TAG_UPDATE,
            PermissionEnum::TAG_DELETE,
            PermissionEnum::CMS_DOC_VIEW,
            PermissionEnum::CMS_DOC_CREATE,
            PermissionEnum::CMS_DOC_UPDATE,
            PermissionEnum::CMS_DOC_DELETE,
            PermissionEnum::CMS_DOC_PUBLISH,
            PermissionEnum::CMS_BLOG_VIEW,
            PermissionEnum::CMS_BLOG_CREATE,
            PermissionEnum::CMS_BLOG_UPDATE,
            PermissionEnum::CMS_BLOG_DELETE,
            PermissionEnum::CMS_BLOG_PUBLISH,
            PermissionEnum::CMS_PAGE_VIEW,
            PermissionEnum::CMS_PAGE_CREATE,
            PermissionEnum::CMS_PAGE_UPDATE,
            PermissionEnum::CMS_PAGE_DELETE,
            PermissionEnum::CMS_PAGE_PUBLISH,
            PermissionEnum::MARKETING_PLATFORM_VIEW,
            PermissionEnum::MARKETING_PLATFORM_CREATE,
            PermissionEnum::MARKETING_PLATFORM_UPDATE,
            PermissionEnum::MARKETING_PLATFORM_DELETE,
            PermissionEnum::MARKETING_PLATFORM_PUBLISH,
            PermissionEnum::MARKETING_STORE_VIEW,
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
            PermissionEnum::MARKETING_STORE_DELETE,
            PermissionEnum::MARKETING_STORE_PUBLISH,

            // New permissions
            PermissionEnum::SHIPPING_VIEW,
            PermissionEnum::SHIPPING_CREATE,
            PermissionEnum::SHIPPING_UPDATE,
            PermissionEnum::SHIPPING_DELETE,
            PermissionEnum::NAVIGATION_VIEW,
            PermissionEnum::NAVIGATION_CREATE,
            PermissionEnum::NAVIGATION_UPDATE,
            PermissionEnum::NAVIGATION_DELETE,
            PermissionEnum::THEME_VIEW,
            PermissionEnum::THEME_CREATE,
            PermissionEnum::THEME_UPDATE,
            PermissionEnum::THEME_DELETE,
            PermissionEnum::THEME_PUBLISH,
            PermissionEnum::TEMPLATE_VIEW,
            PermissionEnum::TEMPLATE_CREATE,
            PermissionEnum::TEMPLATE_UPDATE,
            PermissionEnum::TEMPLATE_DELETE,

            // Billing permissions
            PermissionEnum::SUBSCRIPTION_VIEW,
            PermissionEnum::SUBSCRIPTION_UPGRADE,
            PermissionEnum::SUBSCRIPTION_DOWNGRADE,
            PermissionEnum::SUBSCRIPTION_CANCEL,
            PermissionEnum::SUBSCRIPTION_RESUME,
            PermissionEnum::INVOICE_VIEW,
            PermissionEnum::INVOICE_DOWNLOAD,
            PermissionEnum::BILLING_PORTAL,
            PermissionEnum::PAYMENT_METHOD_UPDATE,
            PermissionEnum::PAYMENT_METHOD_DELETE,

            // Profile permissions
            PermissionEnum::PROFILE_VIEW,
            PermissionEnum::PROFILE_UPDATE_INFO,
            PermissionEnum::PROFILE_UPDATE_PASSWORD,
            PermissionEnum::PROFILE_UPDATE_AVATAR,
            PermissionEnum::PROFILE_DELETE,

            // Feature Flag permissions (Platform Admin Only)
            PermissionEnum::FEATURE_FLAG_VIEW,
            PermissionEnum::FEATURE_FLAG_UPDATE,
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create all roles from RoleEnum
        $superAdmin = Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        $storeAdmin = Role::firstOrCreate(['name' => RoleEnum::STORE_ADMIN->value]);
        $staff = Role::firstOrCreate(['name' => RoleEnum::STAFF->value]);
        $customer = Role::firstOrCreate(['name' => RoleEnum::CUSTOMER->value]);

        // Assign permissions to super_admin (ALL permissions EXCEPT platform-specific permissions)
        // Platform permissions must be explicitly granted, not inherited from role
        $superAdmin->syncPermissions([
            PermissionEnum::USER_VIEW,
            PermissionEnum::USER_CREATE,
            PermissionEnum::USER_BLOCK,
            PermissionEnum::USER_DELETE,
            PermissionEnum::USER_RESTORE,
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::PRODUCT_CREATE,
            PermissionEnum::PRODUCT_UPDATE,
            PermissionEnum::PRODUCT_DELETE,
            PermissionEnum::PRODUCT_RESTORE,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
            // PLATFORM_ORDER_* permissions are NOT assigned to role - must be explicit
            PermissionEnum::STORE_UPDATE,
            PermissionEnum::STORE_DELETE,
            PermissionEnum::STORE_VIEW,
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::CATEGORY_CREATE,
            PermissionEnum::CATEGORY_UPDATE,
            PermissionEnum::CATEGORY_DELETE,
            PermissionEnum::CATEGORY_RESTORE,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::BRAND_CREATE,
            PermissionEnum::BRAND_UPDATE,
            PermissionEnum::BRAND_DELETE,
            PermissionEnum::BRAND_RESTORE,
            PermissionEnum::TAG_VIEW,
            PermissionEnum::TAG_CREATE,
            PermissionEnum::TAG_UPDATE,
            PermissionEnum::TAG_DELETE,
            PermissionEnum::CMS_DOC_VIEW,
            PermissionEnum::CMS_DOC_CREATE,
            PermissionEnum::CMS_DOC_UPDATE,
            PermissionEnum::CMS_DOC_DELETE,
            PermissionEnum::CMS_DOC_PUBLISH,
            PermissionEnum::CMS_BLOG_VIEW,
            PermissionEnum::CMS_BLOG_CREATE,
            PermissionEnum::CMS_BLOG_UPDATE,
            PermissionEnum::CMS_BLOG_DELETE,
            PermissionEnum::CMS_BLOG_PUBLISH,
            PermissionEnum::CMS_PAGE_VIEW,
            PermissionEnum::CMS_PAGE_CREATE,
            PermissionEnum::CMS_PAGE_UPDATE,
            PermissionEnum::CMS_PAGE_DELETE,
            PermissionEnum::CMS_PAGE_PUBLISH,
            PermissionEnum::MARKETING_PLATFORM_VIEW,
            PermissionEnum::MARKETING_PLATFORM_CREATE,
            PermissionEnum::MARKETING_PLATFORM_UPDATE,
            PermissionEnum::MARKETING_PLATFORM_DELETE,
            PermissionEnum::MARKETING_PLATFORM_PUBLISH,
            PermissionEnum::MARKETING_STORE_VIEW,
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
            PermissionEnum::MARKETING_STORE_DELETE,
            PermissionEnum::MARKETING_STORE_PUBLISH,

            // New permissions
            PermissionEnum::SHIPPING_VIEW,
            PermissionEnum::SHIPPING_CREATE,
            PermissionEnum::SHIPPING_UPDATE,
            PermissionEnum::SHIPPING_DELETE,
            PermissionEnum::NAVIGATION_VIEW,
            PermissionEnum::NAVIGATION_CREATE,
            PermissionEnum::NAVIGATION_UPDATE,
            PermissionEnum::NAVIGATION_DELETE,
            PermissionEnum::THEME_VIEW,
            PermissionEnum::THEME_CREATE,
            PermissionEnum::THEME_UPDATE,
            PermissionEnum::THEME_DELETE,
            PermissionEnum::THEME_PUBLISH,
            PermissionEnum::TEMPLATE_VIEW,
            PermissionEnum::TEMPLATE_CREATE,
            PermissionEnum::TEMPLATE_UPDATE,
            PermissionEnum::TEMPLATE_DELETE,

            // Billing permissions
            PermissionEnum::SUBSCRIPTION_VIEW,
            PermissionEnum::SUBSCRIPTION_UPGRADE,
            PermissionEnum::SUBSCRIPTION_DOWNGRADE,
            PermissionEnum::SUBSCRIPTION_CANCEL,
            PermissionEnum::SUBSCRIPTION_RESUME,
            PermissionEnum::INVOICE_VIEW,
            PermissionEnum::INVOICE_DOWNLOAD,
            PermissionEnum::BILLING_PORTAL,
            PermissionEnum::PAYMENT_METHOD_UPDATE,
            PermissionEnum::PAYMENT_METHOD_DELETE,

            // Profile permissions
            PermissionEnum::PROFILE_VIEW,
            PermissionEnum::PROFILE_UPDATE_INFO,
            PermissionEnum::PROFILE_UPDATE_PASSWORD,
            PermissionEnum::PROFILE_UPDATE_AVATAR,
            PermissionEnum::PROFILE_DELETE,

            // Feature Flag permissions (Platform Admin Only)
            PermissionEnum::FEATURE_FLAG_VIEW,
            PermissionEnum::FEATURE_FLAG_UPDATE,
        ]);

        // Assign permissions to store_admin
        $storeAdmin->syncPermissions([
            PermissionEnum::USER_VIEW,
            PermissionEnum::USER_CREATE,
            PermissionEnum::USER_BLOCK,
            PermissionEnum::USER_DELETE,
            PermissionEnum::USER_RESTORE,
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::PRODUCT_CREATE,
            PermissionEnum::PRODUCT_UPDATE,
            PermissionEnum::PRODUCT_DELETE,
            PermissionEnum::PRODUCT_RESTORE,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::ORDER_UPDATE_STATUS,
            PermissionEnum::ORDER_CANCEL,
            PermissionEnum::ORDER_REFUND,
            PermissionEnum::STORE_UPDATE,
            PermissionEnum::STORE_VIEW,
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::CATEGORY_CREATE,
            PermissionEnum::CATEGORY_UPDATE,
            PermissionEnum::CATEGORY_DELETE,
            PermissionEnum::CATEGORY_RESTORE,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::BRAND_CREATE,
            PermissionEnum::BRAND_UPDATE,
            PermissionEnum::BRAND_DELETE,
            PermissionEnum::BRAND_RESTORE,
            PermissionEnum::TAG_VIEW,
            PermissionEnum::TAG_CREATE,
            PermissionEnum::TAG_UPDATE,
            PermissionEnum::TAG_DELETE,
            PermissionEnum::MARKETING_STORE_VIEW,
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
            PermissionEnum::MARKETING_STORE_DELETE,
            PermissionEnum::MARKETING_STORE_PUBLISH,

            // New permissions — management (no delete for store)
            PermissionEnum::SHIPPING_VIEW,
            PermissionEnum::SHIPPING_CREATE,
            PermissionEnum::SHIPPING_UPDATE,
            PermissionEnum::SHIPPING_DELETE,
            PermissionEnum::NAVIGATION_VIEW,
            PermissionEnum::NAVIGATION_CREATE,
            PermissionEnum::NAVIGATION_UPDATE,
            PermissionEnum::NAVIGATION_DELETE,
            PermissionEnum::THEME_VIEW,
            PermissionEnum::THEME_CREATE,
            PermissionEnum::THEME_UPDATE,
            PermissionEnum::THEME_DELETE,
            PermissionEnum::THEME_PUBLISH,
            PermissionEnum::TEMPLATE_VIEW,
            PermissionEnum::TEMPLATE_CREATE,
            PermissionEnum::TEMPLATE_UPDATE,
            PermissionEnum::TEMPLATE_DELETE,

            // Billing permissions
            PermissionEnum::SUBSCRIPTION_VIEW,
            PermissionEnum::SUBSCRIPTION_UPGRADE,
            PermissionEnum::SUBSCRIPTION_DOWNGRADE,
            PermissionEnum::SUBSCRIPTION_CANCEL,
            PermissionEnum::SUBSCRIPTION_RESUME,
            PermissionEnum::INVOICE_VIEW,
            PermissionEnum::INVOICE_DOWNLOAD,
            PermissionEnum::BILLING_PORTAL,
            PermissionEnum::PAYMENT_METHOD_UPDATE,
            PermissionEnum::PAYMENT_METHOD_DELETE,

            // Profile permissions
            PermissionEnum::PROFILE_VIEW,
            PermissionEnum::PROFILE_UPDATE_INFO,
            PermissionEnum::PROFILE_UPDATE_PASSWORD,
            PermissionEnum::PROFILE_UPDATE_AVATAR,
            PermissionEnum::PROFILE_DELETE,
        ]);

        // Assign permissions to staff
        $staff->syncPermissions([
            PermissionEnum::USER_VIEW,
            PermissionEnum::PRODUCT_VIEW,
            PermissionEnum::ORDER_VIEW,
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::CATEGORY_VIEW,
            PermissionEnum::BRAND_VIEW,
            PermissionEnum::TAG_VIEW,
            PermissionEnum::MARKETING_STORE_VIEW,
            PermissionEnum::SHIPPING_VIEW,
            PermissionEnum::NAVIGATION_VIEW,
            PermissionEnum::THEME_VIEW,
            PermissionEnum::TEMPLATE_VIEW,
            PermissionEnum::SUBSCRIPTION_VIEW,
            PermissionEnum::INVOICE_VIEW,

            // Profile permissions
            PermissionEnum::PROFILE_VIEW,

        ]);

        // customer gets no permissions (role exists but no permissions assigned)
    }
}
