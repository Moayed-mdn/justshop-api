# CMS Quick Reference Guide

## 🎯 Simple Answer to Your Questions

### Platform-Level CMS (Global)

#### 1. Blog Posts
- **Model**: `BlogPost`
- **Table**: `blog_posts` (NO store_id)
- **Controller**: `PlatformBlogController`
- **Routes**: `/api/v1/platform/cms/blog/*`
- **For**: Marketing site blog (company news, updates)

#### 2. Documentation
- **Model**: `CmsDocument`
- **Table**: `cms_documents` (NO store_id)
- **Controller**: `AdminDocumentController`
- **Routes**: `/api/v1/platform/cms/docs/*`
- **For**: Help docs, API docs, guides

#### 3. Marketing Pages
- **Model**: `PlatformMarketingPage`
- **Table**: `platform_marketing_pages` (NO store_id)
- **Controller**: `AdminPlatformMarketingPageController`
- **Routes**: `/api/v1/platform/cms/pages/*`
- **For**: Pricing, Features, About pages

---

### Store-Level CMS (Per Tenant)

#### 1. Store Marketing Pages
- **Model**: `StoreMarketingPage`
- **Table**: `store_marketing_pages` (HAS store_id ✅)
- **Controller**: `AdminStoreMarketingPageController`
- **Routes**: `/api/v1/merchant/stores/{store}/cms/pages/*`
- **For**: Store policies, custom store pages

---

### Legacy System ⚠️

#### marketing_pages Table
- **Model**: `MarketingPage`
- **Table**: `marketing_pages` (NO store_id)
- **Controller**: `AdminMarketingPageController` (NOT ROUTED)
- **Routes**: ❌ NONE (orphaned)
- **Status**: **DEPRECATED - Contains 10 old records**
- **What it was for**: Old marketing pages before `platform_marketing_pages` existed
- **What to do**: Migrate to `platform_marketing_pages` or delete

**Why deprecated?**
- Wrong architecture (used store policies for platform content)
- Different schema than new system
- Not connected to any routes
- Superseded by `platform_marketing_pages`

---

## 📊 Data Summary

```
Platform CMS (Global):
├── blog_posts: 8 records
├── cms_documents: 12 records
└── platform_marketing_pages: 4 records

Store CMS (Per Tenant):
└── store_marketing_pages: 5 records

Legacy (Deprecated):
└── marketing_pages: 10 records ⚠️
```

---

## 🔑 Key Differences

| Aspect | Platform CMS | Store CMS | Legacy |
|--------|-------------|-----------|--------|
| **Scope** | Global (all users see same content) | Per-store (isolated) | Global (but wrong) |
| **store_id** | ❌ None | ✅ Has | ❌ None |
| **Access** | Super admins only | Store admins/staff | N/A |
| **team_id** | 0 (global) | store_id | N/A |
| **Authorization** | Permissions | Policies | Policies (wrong) |
| **Middleware** | `platform.context` | `store.context` | N/A |
| **Purpose** | Marketing website | Tenant storefronts | Old system |
| **Examples** | Shopify.com blog | Merchant's store pages | N/A |

---

## 🚀 Real-World Example (Shopify Model)

### Platform CMS → shopify.com
- Blog: https://shopify.com/blog
- Docs: https://shopify.dev
- Pages: https://shopify.com/pricing

### Store CMS → yourstore.myshopify.com
- Each merchant's store pages
- Policies, About, Custom pages
- Isolated per merchant

---

## ✅ Current Status

| System | Working? | Routed? | Records |
|--------|----------|---------|---------|
| Platform Blog | ✅ | ✅ | 8 |
| Platform Docs | ✅ | ✅ | 12 |
| Platform Pages | ✅ | ✅ | 4 |
| Store Pages | ✅ | ✅ | 5 |
| Legacy | ❌ | ❌ | 10 |

---

## 🎯 Bottom Line

You have **TWO completely separate CMS systems**:

1. **Platform CMS** = Content for YOUR marketing site (like Shopify's website)
2. **Store CMS** = Content for YOUR TENANTS' stores (like merchant stores on Shopify)

The `marketing_pages` table is old/deprecated and should be ignored or migrated.
