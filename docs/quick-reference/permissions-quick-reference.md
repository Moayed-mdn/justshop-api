# Permissions Quick Reference

Quick lookup for roles, permissions, and access levels.

---

## Role Access Summary

| Feature | Super Admin | Store Admin | Staff | Customer |
|---------|-------------|-------------|-------|----------|
| **Dashboard** | ✅ All stores | ✅ Own store | ✅ View only | ❌ |
| **Products** | ✅ CRUD | ✅ CRUD | ✅ View only | ✅ Browse |
| **Orders** | ✅ Full control | ✅ Process & refund | ✅ View only | ✅ Own orders |
| **Users** | ✅ Global | ✅ Store staff | ✅ View only | ✅ Own profile |
| **Categories** | ✅ CRUD | ✅ CRUD | ✅ View only | ❌ |
| **Brands** | ✅ CRUD | ✅ CRUD | ✅ View only | ❌ |
| **Tags** | ✅ CRUD | ✅ CRUD | ✅ View only | ❌ |
| **Store Settings** | ✅ All stores | ✅ Own store | ❌ | ❌ |
| **Marketing (Store)** | ✅ All stores | ✅ Own store | ❌ | ❌ |
| **Marketing (Platform)** | ✅ Full access | ❌ | ❌ | ❌ |
| **Platform CMS** | ✅ Full access | ❌ | ❌ | ❌ |
| **Shipping** | ✅ All stores | ✅ CRUD | ✅ View only | ❌ |
| **Navigation** | ✅ All stores | ✅ CRUD | ✅ View only | ❌ |
| **Themes** | ✅ All stores | ✅ CRUD | ✅ View only | ❌ |
| **Page Templates** | ✅ All stores | ✅ CRUD | ✅ View only | ❌ |

---

## Staff User Capabilities

### ✅ What Staff Can Do
- View dashboard analytics
- Browse products and inventory
- Look up orders and customer information
- View categories, brands, and tags
- Check user accounts
- Access read-only reports

### ❌ What Staff Cannot Do
- Create, edit, or delete products
- Process orders (change status, refund, cancel)
- Manage users (invite, block, delete)
- Modify categories, brands, or tags
- Change store settings
- Access marketing features

### 💬 Error Messages Staff Will See

When staff attempt unauthorized actions:

| Action | Error Message |
|--------|---------------|
| Create product | "You don't have permission to create products. Contact your store administrator." |
| Update order | "You don't have permission to update order status. This action requires Store Admin role." |
| Delete category | "You don't have permission to delete categories. View-only access is granted." |
| Block user | "You don't have permission to block users. This action requires Store Admin role." |

---

## Permission Lookup

### User Management
```
user.view          - View users
user.create        - Invite staff members
user.block         - Block user accounts
user.delete        - Delete users
user.restore       - Restore deleted users
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

### Product Catalog
```
product.view       - View products
product.create     - Create products
product.update     - Edit products
product.delete     - Delete products
product.restore    - Restore deleted products
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

### Order Management
```
order.view          - View orders
order.update_status - Change order status
order.cancel        - Cancel orders
order.refund        - Process refunds
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

**Special Rule:** Customers can view and cancel their own orders.

---

### Dashboard
```
dashboard.view     - Access dashboard
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff ✅

---

### Category Management
```
category.view      - View categories
category.create    - Create categories
category.update    - Edit categories
category.delete    - Delete categories
category.restore   - Restore deleted categories
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

### Brand Management
```
brand.view         - View brands
brand.create       - Create brands
brand.update       - Edit brands
brand.delete       - Delete brands
brand.restore      - Restore deleted brands
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

### Tag Management
```
tag.view           - View tags
tag.create         - Create tags
tag.update         - Edit tags
tag.delete         - Delete tags
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

### Store Settings
```
store.view         - View store settings
store.create       - Create new store (platform-level)
store.update       - Update store settings
store.delete       - Delete store
```

**Access:** Super Admin ✅ | Store Admin ✅

---

### Marketing (Platform-level)
```
marketing.platform.view      - View platform marketing content
marketing.platform.create    - Create platform campaigns
marketing.platform.update    - Edit platform campaigns
marketing.platform.delete    - Delete platform campaigns
marketing.platform.publish   - Publish platform campaigns
```

**Access:** Super Admin ✅ only

**Note:** Platform-level marketing is for cross-store campaigns and platform-wide promotions, managed by super admins only.

---

### Marketing (Store-level)
```
marketing.store.view      - View marketing content
marketing.store.create    - Create campaigns
marketing.store.update    - Edit campaigns
marketing.store.delete    - Delete campaigns
marketing.store.publish   - Publish campaigns
```

**Access:** Super Admin ✅ | Store Admin ✅

---

### Shipping
```
shipping.view       - View shipping zones/methods
shipping.create     - Create shipping zones/methods
shipping.update     - Edit shipping configuration
shipping.delete     - Delete shipping zones/methods
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

### Navigation
```
navigation.view     - View navigation menus
navigation.create   - Create navigation menus
navigation.update   - Edit navigation menus
navigation.delete   - Delete navigation menus
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

### Themes
```
theme.view         - View themes
theme.create       - Create themes
theme.update       - Edit theme settings
theme.delete       - Delete themes
theme.publish      - Publish/activate themes
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

### Page Templates
```
template.view      - View page templates
template.create    - Create page templates
template.update    - Edit page templates
template.delete    - Delete page templates
```

**Access:** Super Admin ✅ | Store Admin ✅ | Staff (view only) ✅

---

## Common Use Cases

### Inviting Staff Members

**Who can do this:** Store Admin, Super Admin

**Steps:**
1. Navigate to Users → Invite Staff
2. Enter email and select "Staff" role
3. Staff receives invite with view-only access

**Staff will be able to:**
- Log in to the dashboard
- View all store data
- Answer customer questions
- Check inventory

**Staff will NOT be able to:**
- Modify any data
- Process orders
- Change settings

---

### Promoting Staff to Store Admin

**Who can do this:** Super Admin only

**Steps:**
1. Edit user role from "Staff" to "Store Admin"
2. User gains full CRUD access to store

**Note:** Store admins can manage other staff members but cannot promote to admin.

---

### Checking What a User Can Do

**Via Code:**
```php
// Check if user can create products
if ($user->can(PermissionEnum::PRODUCT_CREATE)) {
    // Allow product creation
}

// Check role
if ($user->hasRole(RoleEnum::STORE_ADMIN->value)) {
    // User is store admin
}
```

**Via Database:**
```sql
-- Get user's permissions
SELECT p.name 
FROM permissions p
JOIN model_has_permissions mp ON p.id = mp.permission_id
WHERE mp.model_id = {user_id};

-- Get user's roles
SELECT r.name 
FROM roles r
JOIN model_has_roles mr ON r.id = mr.role_id
WHERE mr.model_id = {user_id};
```

---

## API Endpoints & Required Permissions

### Products
```
GET    /api/v1/admin/stores/{store}/products         → product.view
POST   /api/v1/admin/stores/{store}/products         → product.create
PATCH  /api/v1/admin/stores/{store}/products/{id}    → product.update
DELETE /api/v1/admin/stores/{store}/products/{id}    → product.delete
```

### Orders
```
GET    /api/v1/admin/stores/{store}/orders           → order.view
PATCH  /api/v1/admin/stores/{store}/orders/{id}      → order.update_status
POST   /api/v1/admin/stores/{store}/orders/{id}/refund → order.refund
```

### Users
```
GET    /api/v1/admin/stores/{store}/users            → user.view
POST   /api/v1/admin/stores/{store}/users            → user.create
PATCH  /api/v1/admin/stores/{store}/users/{id}/block → user.block
DELETE /api/v1/admin/stores/{store}/users/{id}       → user.delete
```

---

## Troubleshooting

### User can't access dashboard
- ✅ Check user has `dashboard.view` permission
- ✅ Check user is a member of the store
- ✅ Check user has merchant actor context

### Staff sees "permission denied" errors
- ✅ **Expected behavior** - staff have view-only access
- ✅ Error messages should be clear and actionable
- ✅ Contact store admin to upgrade role if needed

### Permission changes not taking effect
```bash
# Clear permission cache
php artisan permission:cache-reset

# Clear application cache
php artisan cache:clear
```

---

## Related Files

- **Full Documentation:** `docs/auth/PERMISSIONS_AND_ROLES.md`
- **Architecture Rules:** `docs/ARCHITECTURE.md`
- **Permission Enum:** `app/Enums/PermissionEnum.php`
- **Role Enum:** `app/Enums/RoleEnum.php`
- **Permission Seeder:** `database/seeders/PermissionSeeder.php`
