<?php

declare(strict_types=1);

namespace App\Enums;

class PermissionEnum
{
    // --- User ---
    public const USER_VIEW    = 'user.view';
    public const USER_CREATE  = 'user.create';
    public const USER_BLOCK   = 'user.block';
    public const USER_DELETE  = 'user.delete';
    public const USER_RESTORE = 'user.restore';

    // --- Product ---
    public const PRODUCT_VIEW    = 'product.view';
    public const PRODUCT_CREATE  = 'product.create';
    public const PRODUCT_UPDATE  = 'product.update';
    public const PRODUCT_DELETE  = 'product.delete';
    public const PRODUCT_RESTORE = 'product.restore';

    // --- Order ---
    public const ORDER_VIEW          = 'order.view';
    public const ORDER_UPDATE_STATUS = 'order.update_status';
    public const ORDER_CANCEL        = 'order.cancel';
    public const ORDER_REFUND        = 'order.refund';

    // --- Dashboard ---
    public const DASHBOARD_VIEW = 'dashboard.view';

    // --- Category ---
    public const CATEGORY_VIEW    = 'category.view';
    public const CATEGORY_CREATE  = 'category.create';
    public const CATEGORY_UPDATE  = 'category.update';
    public const CATEGORY_DELETE  = 'category.delete';
    public const CATEGORY_RESTORE = 'category.restore';

    // --- Brand ---
    public const BRAND_VIEW    = 'brand.view';
    public const BRAND_CREATE  = 'brand.create';
    public const BRAND_UPDATE  = 'brand.update';
    public const BRAND_DELETE  = 'brand.delete';
    public const BRAND_RESTORE = 'brand.restore';

    // --- Tag ---
    public const TAG_VIEW   = 'tag.view';
    public const TAG_CREATE = 'tag.create';
    public const TAG_UPDATE = 'tag.update';
    public const TAG_DELETE = 'tag.delete';

    // --- Store ---
    public const STORE_VIEW   = 'store.view';
    public const STORE_CREATE = 'store.create';
    public const STORE_UPDATE = 'store.update';
    public const STORE_DELETE = 'store.delete';

    // --- CMS Documentation ---
    public const CMS_DOC_VIEW    = 'cms.doc.view';
    public const CMS_DOC_CREATE  = 'cms.doc.create';
    public const CMS_DOC_UPDATE  = 'cms.doc.update';
    public const CMS_DOC_DELETE  = 'cms.doc.delete';
    public const CMS_DOC_PUBLISH = 'cms.doc.publish';

    // --- CMS Blog ---
    public const CMS_BLOG_VIEW    = 'cms.blog.view';
    public const CMS_BLOG_CREATE  = 'cms.blog.create';
    public const CMS_BLOG_UPDATE  = 'cms.blog.update';
    public const CMS_BLOG_DELETE  = 'cms.blog.delete';
    public const CMS_BLOG_PUBLISH = 'cms.blog.publish';

    // --- CMS Marketing Pages ---
    public const CMS_PAGE_VIEW    = 'cms.page.view';
    public const CMS_PAGE_CREATE  = 'cms.page.create';
    public const CMS_PAGE_UPDATE  = 'cms.page.update';
    public const CMS_PAGE_DELETE  = 'cms.page.delete';
    public const CMS_PAGE_PUBLISH = 'cms.page.publish';

    // --- Platform Marketing ---
    public const MARKETING_PLATFORM_VIEW    = 'marketing.platform.view';
    public const MARKETING_PLATFORM_CREATE  = 'marketing.platform.create';
    public const MARKETING_PLATFORM_UPDATE  = 'marketing.platform.update';
    public const MARKETING_PLATFORM_DELETE  = 'marketing.platform.delete';
    public const MARKETING_PLATFORM_PUBLISH = 'marketing.platform.publish';

    // --- Store Marketing ---
    public const MARKETING_STORE_VIEW    = 'marketing.store.view';
    public const MARKETING_STORE_CREATE  = 'marketing.store.create';
    public const MARKETING_STORE_UPDATE  = 'marketing.store.update';
    public const MARKETING_STORE_DELETE  = 'marketing.store.delete';
    public const MARKETING_STORE_PUBLISH = 'marketing.store.publish';
}