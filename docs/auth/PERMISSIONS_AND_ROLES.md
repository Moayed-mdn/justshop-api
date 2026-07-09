# Permissions and Roles System

## Overview

This platform uses **Spatie Laravel Permission** for Role-Based Access Control (RBAC). The permission system enforces authorization at the Policy layer, ensuring users can only perform actions they're authorized for within their store context.

**Key Principles:**
- **Policies are the single source of truth** for authorization
- **Store-scoped access** - users can only access stores they're members of
- **Role-based permissions** - roles define what actions users can perform
- **Contextual error messages** - clear feedback when permissions are denied

---

## User Roles

The platform defines 5 distinct roles with different access levels:

| Role | Description | Access Level | Use Case |
|------|-------------|--------------|----------|
| **SUPER_ADMIN** | Platform administrator | Unrestricted across all stores | Platform management, support escalations |
| **SUPPORT** | Platform support agent | Platform-level support access | Customer support, issue resolution |
| **STORE_ADMIN** | Store owner/manager | Full control within their store(s) | Store management, staff management |
| **STAFF** | Store employee | View-only access within their store(s) | Order viewing, inventory checking |
| **CUSTOMER** | Shopper | Order viewing, profile management | Shopping, order tracking |

---

## Role Definitions

### RoleEnum (`app/Enums/RoleEnum.php`)

```php
enum RoleEnum: string
{
    case SUPER_ADMIN  = 'super_admin';
    case SUPPORT      = 'support';
    case STORE_ADMIN  = 'store_admin';
    case STAFF        = 'staff';
    case CUSTOMER     = 'customer';
}
```

---

## Permission Structure

Permissions follow a `{domain}.{action}` naming convention for clarity and maintainability.

### Permission Domains

1. **User Management** - `user.*`
2. **Product Catalog** - `product.*`
3. **Order Management** - `order.*`
4. **Dashboard Access** - `dashboard.view`
5. **Category Management** - `category.*`
6. **Brand Management** - `brand.*`
7. **Tag Management** - `tag.*`
8. **Store Settings** - `store.*`
9. **CMS (Platform-level)** - `cms.*`
10. **Marketing (Platform-level)** - `marketing.platform.*`
11. **Marketing (Store-level)** - `marketing.store.*`
12. **Shipping** - `shipping.*`
13. **Navigation** - `navigation.*`
14. **Theme** - `theme.*`
15. **Page Templates** - `template.*`

### Permission Actions

- **view** - Read access to resources
- **create** - Create new resources
- **update** - Modify existing resources
- **delete** - Remove resources
- **restore** - Restore soft-deleted resources
- **block/unblock** - User account actions
- **update_status** - Order status changes
- **cancel** - Order cancellations
- **refund** - Order refunds
- **publish** - Publish CMS content

---

## Complete Permission List

### User Management
- `user.view` - View users
- `user.create` - Create users (invite staff)
- `user.block` - Block/suspend user accounts
- `user.delete` - Delete user accounts
- `user.restore` - Restore deleted users

### Product Catalog
- `product.view` - View products
- `product.create` - Create products
- `product.update` - Update products
- `product.delete` - Delete products
- `product.restore` - Restore deleted products

### Order Management
- `order.view` - View orders
- `order.update_status` - Change order status
- `order.cancel` - Cancel orders
- `order.refund` - Process refunds

### Dashboard
- `dashboard.view` - Access dashboard

### Category Management
- `category.view` - View categories
- `category.create` - Create categories
- `category.update` - Update categories
- `category.delete` - Delete categories
- `category.restore` - Restore deleted categories

### Brand Management
- `brand.view` - View brands
- `brand.create` - Create brands
- `brand.update` - Update brands
- `brand.delete` - Delete brands
- `brand.restore` - Restore deleted brands

### Tag Management
- `tag.view` - View tags
- `tag.create` - Create tags
- `tag.update` - Update tags
- `tag.delete` - Delete tags

### Store Settings
- `store.view` - View store settings
- `store.create` - Create new store (platform-level)
- `store.update` - Update store settings
- `store.delete` - Delete store

### CMS (Platform-level)
- `cms.doc.view` - View documentation
- `cms.doc.create` - Create documentation
- `cms.doc.update` - Update documentation
- `cms.doc.delete` - Delete documentation
- `cms.doc.publish` - Publish documentation
- `cms.blog.view` - View blog posts
- `cms.blog.create` - Create blog posts
- `cms.blog.update` - Update blog posts
- `cms.blog.delete` - Delete blog posts
- `cms.blog.publish` - Publish blog posts
- `cms.page.view` - View marketing pages
- `cms.page.create` - Create marketing pages
- `cms.page.update` - Update marketing pages
- `cms.page.delete` - Delete marketing pages
- `cms.page.publish` - Publish marketing pages

### Marketing (Platform-level)
- `marketing.platform.view` - View platform marketing content
- `marketing.platform.create` - Create platform marketing campaigns
- `marketing.platform.update` - Update platform marketing content
- `marketing.platform.delete` - Delete platform marketing campaigns
- `marketing.platform.publish` - Publish platform marketing content

### Marketing (Store-level)
- `marketing.store.view` - View store marketing content
- `marketing.store.create` - Create store marketing campaigns
- `marketing.store.update` - Update store marketing content
- `marketing.store.delete` - Delete store marketing campaigns
- `marketing.store.publish` - Publish store marketing content

### Shipping
- `shipping.view` - View shipping zones and methods
- `shipping.create` - Create shipping zones and methods
- `shipping.update` - Update shipping configuration
- `shipping.delete` - Delete shipping zones and methods

### Navigation
- `navigation.view` - View navigation menus
- `navigation.create` - Create navigation menus
- `navigation.update` - Update navigation menus
- `navigation.delete` - Delete navigation menus

### Theme System
- `theme.view` - View themes
- `theme.create` - Create themes
- `theme.update` - Update theme settings
- `theme.delete` - Delete themes
- `theme.publish` - Publish/activate themes

### Page Templates
- `template.view` - View page templates
- `template.create` - Create page templates
- `template.update` - Update page templates
- `template.delete` - Delete page templates

---

## Role Permissions Matrix

### SUPER_ADMIN
**Access:** ✅ ALL permissions across ALL stores

- Full unrestricted access to the entire platform
- Bypasses all policy checks
- Can manage all stores, users, and content
- Platform-level CMS and configuration access

**Use Cases:**
- Platform administration
- Global configuration changes
- Cross-store operations
- Emergency support interventions

---

### STORE_ADMIN
**Access:** ✅ Full CRUD within their store(s)

**Granted Permissions:**
```
✅ user.view, user.create, user.block, user.delete, user.restore
✅ product.view, product.create, product.update, product.delete, product.restore
✅ order.view, order.update_status, order.cancel, order.refund
✅ dashboard.view
✅ category.view, category.create, category.update, category.delete, category.restore
✅ brand.view, brand.create, brand.update, brand.delete, brand.restore
✅ tag.view, tag.create, tag.update, tag.delete
✅ store.view, store.update
✅ marketing.store.view, marketing.store.create, marketing.store.update, 
   marketing.store.delete, marketing.store.publish
✅ shipping.view, shipping.create, shipping.update, shipping.delete
✅ navigation.view, navigation.create, navigation.update, navigation.delete
✅ theme.view, theme.create, theme.update, theme.delete, theme.publish
✅ template.view, template.create, template.update, template.delete
```

**Cannot Access:**
- ❌ Platform-level CMS (blog, documentation, platform marketing)
- ❌ Other stores (strict tenant isolation)
- ❌ Global platform settings
- ❌ Platform marketing (marketing.platform.*)

**Use Cases:**
- Store management and configuration
- Staff member invitations and management
- Product catalog management
- Order processing and fulfillment
- Store marketing campaigns

---

### STAFF
**Access:** ✅ View-only access within their store(s)

**Granted Permissions:**
```
✅ user.view
✅ product.view
✅ order.view
✅ dashboard.view
✅ category.view
✅ brand.view
✅ tag.view
✅ shipping.view
✅ navigation.view
✅ theme.view
✅ template.view
```

**Cannot Access:**
- ❌ Create, update, or delete any resources
- ❌ Process orders (change status, cancel, refund)
- ❌ Manage users (invite, block, delete staff)
- ❌ Modify store settings
- ❌ Access marketing features

**Use Cases:**
- Order lookup and customer support
- Inventory checking
- Dashboard monitoring
- Read-only store operations

**Permission Denied Messages:**

When staff users attempt unauthorized actions, they receive clear, contextual error messages:

```json
{
  "success": false,
  "code": "ACCESS_DENIED",
  "message": "You don't have permission to create products. Contact your store administrator."
}
```

**Examples:**
- Creating product → "You don't have permission to create products. Contact your store administrator."
- Deleting category → "You don't have permission to delete categories. View-only access is granted."
- Updating order → "You don't have permission to update order status. This action requires Store Admin role."
- Blocking user → "You don't have permission to block users. This action requires Store Admin role."

---

### CUSTOMER
**Access:** ✅ Order viewing, profile management

**Granted Permissions:**
```
(No global permissions assigned - customers have implicit access to their own data)
```

**Implicit Access:**
- ✅ View own orders
- ✅ Cancel own orders (before fulfillment)
- ✅ View own profile
- ✅ Manage own addresses and payment methods
- ✅ Browse store products (public access)

**Cannot Access:**
- ❌ Store dashboard
- ❌ Other customers' orders or data
- ❌ Store management features

---

## Authorization Architecture

### Policy-Based Authorization

All authorization checks MUST be performed in **Laravel Policies**. This ensures:
- Single source of truth for authorization logic
- Consistent permission enforcement
- Separation of concerns (business logic in Actions, authorization in Policies)
- Clear audit trail

### Example Policy Structure

```php
class ProductPolicy
{
    use HasStoreMembership;

    public function create(User $user, Store $store): bool
    {
        return $this->decision(
            $user, 
            'create', 
            $this->canManage($user, $store, PermissionEnum::PRODUCT_CREATE, 'product', 'create'), 
            $store
        );
    }

    private function canManage(User $user, Store $store, string $permission, string $resource, string $action): bool
    {
        // Super admin bypass
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        // Check if user is admin and has permission
        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can($permission);

        if ($isAdmin && $hasPermission) {
            return true;
        }

        // Staff member trying to perform admin action
        if ($this->isMember($user, $store) && !$isAdmin) {
            $this->denyWithContext($resource, $action, $permission);
        }

        // Non-member or other failure
        return false;
    }
}
```

### Authorization Flow

```
Request
  ↓
Controller: $this->authorize('create', [Product::class, $store])
  ↓
Policy: ProductPolicy::create($user, $store)
  ↓
  ├─ Super Admin? → ✅ Allow
  ├─ Store Admin + Permission? → ✅ Allow
  ├─ Staff Member? → ❌ Throw PermissionDeniedException (contextual message)
  └─ Non-member? → ❌ Return false (generic AuthorizationException)
  ↓
Action: Assumes authorization passed, executes business logic
  ↓
Response: Success or error
```

---

## Store Membership

### Store Roles (Store-Specific)

In addition to global roles, users have **store-specific roles** via the `store_user` pivot table:

```php
enum StoreRoleEnum: string
{
    case STORE_ADMIN = 'store_admin';
    case STAFF       = 'staff';
}
```

### Membership Checks

The `HasStoreMembership` trait provides helper methods:

```php
// Check if user is a member of the store
$this->isMember($user, $store);

// Check if user is an admin of the store
$this->isAdmin($user, $store);

// Check if user is a merchant (vs customer)
$this->isMerchant($user);
```

### Impersonation Support

Super admins can impersonate store contexts for support purposes:

```php
// Super admin impersonating a store
if ($this->isGovernedImpersonationActive($user)) {
    return true; // Grant store admin access
}
```

---

## Permission Error Handling

### PermissionDeniedException

When staff users attempt unauthorized actions, a `PermissionDeniedException` is thrown with contextual information:

```php
class PermissionDeniedException extends BaseApiException
{
    protected string $resource;  // e.g., "product"
    protected string $action;    // e.g., "create"
    protected ?string $permission; // e.g., "product.create"
    
    // Returns 403 with contextual message
}
```

### Error Response Format

```json
{
  "success": false,
  "code": "ACCESS_DENIED",
  "message": "You don't have permission to create products. Contact your store administrator.",
  "errors": {}
}
```

### Localization Support

Permission error messages are fully localized:

**English (`lang/en/error.php`):**
```php
'permission' => [
    'product' => [
        'create' => 'You don\'t have permission to create products. Contact your store administrator.',
    ],
]
```

**Arabic (`lang/ar/error.php`):**
```php
'permission' => [
    'product' => [
        'create' => 'ليس لديك صلاحية إنشاء المنتجات. اتصل بمسؤول المتجر.',
    ],
]
```

---

## Adding New Permissions

### Step 1: Add to PermissionEnum

```php
// app/Enums/PermissionEnum.php
public const SHIPMENT_VIEW = 'shipment.view';
public const SHIPMENT_CREATE = 'shipment.create';
```

### Step 2: Update PermissionSeeder

```php
// database/seeders/PermissionSeeder.php
$permissions = [
    // ... existing permissions
    PermissionEnum::SHIPMENT_VIEW,
    PermissionEnum::SHIPMENT_CREATE,
];
```

### Step 3: Assign to Roles

```php
// Assign to store_admin
$storeAdmin->syncPermissions([
    // ... existing permissions
    PermissionEnum::SHIPMENT_VIEW,
    PermissionEnum::SHIPMENT_CREATE,
]);

// Assign to staff (if view-only)
$staff->syncPermissions([
    // ... existing permissions
    PermissionEnum::SHIPMENT_VIEW,
]);
```

### Step 4: Create Policy

```php
// app/Policies/ShipmentPolicy.php
class ShipmentPolicy
{
    use HasStoreMembership;

    public function create(User $user, Store $store): bool
    {
        return $this->decision(
            $user, 
            'create', 
            $this->canManage($user, $store, PermissionEnum::SHIPMENT_CREATE, 'shipment', 'create'), 
            $store
        );
    }

    private function canManage(User $user, Store $store, string $permission, string $resource, string $action): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can($permission);

        if ($isAdmin && $hasPermission) {
            return true;
        }

        if ($this->isMember($user, $store) && !$isAdmin) {
            $this->denyWithContext($resource, $action, $permission);
        }

        return false;
    }
}
```

### Step 5: Add Translations

```php
// lang/en/error.php
'permission' => [
    // ... existing
    'shipment' => [
        'create' => 'You don\'t have permission to create shipments. Contact your store administrator.',
    ],
]
```

### Step 6: Use in Controller

```php
public function create(CreateShipmentRequest $request, Store $store): JsonResponse
{
    $this->authorize('create', [Shipment::class, $store]);
    
    // Business logic in Action
}
```

---

## Security Considerations

### Tenant Isolation

**Critical:** All queries MUST be scoped by `store_id` in repositories to prevent cross-tenant data access.

```php
// ❌ FORBIDDEN
Product::all();

// ✅ REQUIRED
Product::where('store_id', $storeId)->get();
```

### Authorization Rules

1. **Never bypass policies** - Always use `$this->authorize()` in controllers
2. **Never check permissions in Actions** - Actions assume authorization passed
3. **Never use direct role checks** - Use policy methods and permission checks
4. **Never trust client input** - Extract `store_id` from route parameter, not request body

### Guard Architecture

The platform uses guard context switching:
- **Merchant routes:** Use 'merchant' guard
- **Customer routes:** Use 'customer' guard (planned)
- **Platform routes:** Use 'merchant' guard (super_admin only)

Permissions are shared across guards for the current architecture.

---

## Testing Permissions

### Feature Test Example

```php
public function test_staff_cannot_create_product(): void
{
    $staff = $this->createStaffUser();
    $store = Store::factory()->create();
    $staff->stores()->attach($store->id, ['role' => StoreRoleEnum::STAFF->value]);

    $response = $this->actingAs($staff)
        ->postJson("/api/v1/admin/stores/{$store->id}/products", [
            'name' => 'Test Product',
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'code' => 'ACCESS_DENIED',
            'message' => 'You don\'t have permission to create products. Contact your store administrator.',
        ]);
}
```

---

## Troubleshooting

### "This action is unauthorized" (Generic Message)

**Cause:** Policy returns `false` without throwing `PermissionDeniedException`

**Fix:** Ensure staff members throw contextual exception:
```php
if ($this->isMember($user, $store) && !$isAdmin) {
    $this->denyWithContext($resource, $action, $permission);
}
```

### Permission Not Working After Adding

**Cause:** Permission cache not cleared

**Fix:** Clear permission cache:
```bash
php artisan permission:cache-reset
```

### Staff User Can See But Not Act

**Expected Behavior:** Staff users have view-only access. They should see resources but get clear error messages when attempting modifications.

---

## Related Documentation

- **Architecture:** `docs/ARCHITECTURE.md` - Authorization doctrine
- **Auth Routing:** `docs/AUTH_ROUTING.md` - Route-level auth
- **Exception System:** `docs/exception-system.md` - Error handling
- **API Standard:** `docs/admin-api.md` - API conventions

---

## Permission Seeder Reference

See: `database/seeders/PermissionSeeder.php`

Run: `php artisan db:seed --class=PermissionSeeder`
