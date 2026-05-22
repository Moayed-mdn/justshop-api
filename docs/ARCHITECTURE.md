## ✅ Updated Rules File

```markdown
# Laravel API Architecture Rules (Project Contract)

This document defines the **mandatory architecture** for this project.
All contributors (human or AI) MUST follow these rules strictly.

---

# 🔥 Database Enum Rule (CRITICAL)

Database-level enums are STRICTLY FORBIDDEN.

---

## ❌ Forbidden

```php
$table->enum('status', ['pending', 'paid', 'failed']);
```

---

## ✅ Required

```php
$table->string('status');
```

---

## PHP Enum Usage (MANDATORY)

All domain states MUST be defined using PHP Enums.

```php
enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case PAID    = 'paid';
    case FAILED  = 'failed';
}
```

Enums are the **single source of truth** for all valid states.

---

## Eloquent Casting (MANDATORY)

All enum-backed fields MUST be cast in Models:

```php
protected $casts = [
    'status' => OrderStatusEnum::class,
];
```

---

## Request Validation Rules (MANDATORY)

### 1. Pure Domain Fields

If a field represents a strict domain state:

```php
use Illuminate\Validation\Rule;

'status' => [
    'required',
    Rule::in(OrderStatusEnum::values()),
],
```

OR (preferred):

```php
use Illuminate\Validation\Rules\Enum;

'status' => ['required', new Enum(OrderStatusEnum::class)],
```

---

### 2. Filter Fields (SPECIAL RULE)

Filter endpoints (e.g. list APIs) MAY include special values such as:

* "all"
* null

#### ✅ Required Pattern:

```php
'status' => [
    'sometimes',
    'nullable',
    'string',
    Rule::in([
        ...OrderStatusEnum::values(),
        'all',
    ]),
],
```

---

### 🔥 Critical Rules for Filters

* "all" is NOT part of the Enum
* "all" MUST NOT be added to any Enum
* "all" is handled ONLY in Action/DTO logic

---

## Business Logic Rules (MANDATORY)

### ❌ Forbidden:

```php
if ($status === 'pending')
```

### ✅ Required:

```php
if ($status === OrderStatusEnum::PENDING)
```

---

## DTO Rules (RECOMMENDED)

DTOs SHOULD use Enum typing where applicable:

```php
public OrderStatusEnum $status;
```

```php
status: OrderStatusEnum::from($request->string('status')),
```

---

## API Documentation Rule (IMPORTANT)

Validation rules MUST expose allowed values for API documentation tools (e.g. Scalar/OpenAPI).

### Rule:

* Always use `Rule::in([...Enum::values()])` for filters
* Extend with special values like `"all"` when needed

This ensures:

* Correct API documentation
* Frontend discoverability
* No duplication of enum values

---

## Benefits

* No migration friction when adding values
* Safe deployments (no table locks)
* Strong typing at application level
* Clear separation between domain and input logic
* Accurate API documentation
* Scalable and maintainable architecture

---

## Final Rule

* Database stores **strings only**
* PHP Enums define **valid domain states**
* Requests define **allowed inputs**
* Actions/DTOs define **behavior**

Consistency is mandatory.

---

# 1. Core Philosophy

This project follows a strict API-first architecture with clear 
separation of concerns.

---

# 1.5. Authorization Doctrine (ABSOLUTE LAW)

### Policies as Single Source of Truth
Policies are the **ONLY** authorization enforcement layer. 

### Enforcement Rules:
* Controllers MUST invoke `$this->authorize()` or `Gate::authorize()` ONLY.
* Actions MUST NEVER:
    * check roles or permissions
    * call `Auth::user()` or `auth()`
    * call `Gate`
    * contain inline authorization logic
    * validate tenant/store membership manually
    * validate actor capabilities
* Middleware MUST NOT contain business authorization logic.
* Actions assume authorization has already passed.
* Actions MUST receive `actorId`, `actorDTO`, `storeId`, and operational context EXPLICITLY.

### Authorization Boundary Map

| Domain Boundary | Responsible Policy | Description |
| :--- | :--- | :--- |
| **Store Lifecycle** | `StorePolicy` | Ownership, creation, and global store settings. |
| **Product & Inventory** | `ProductPolicy` | Catalog management, stock updates, pricing. |
| **Orders & Fulfillment** | `OrderPolicy` | Order viewing, processing, refunds, and customer access. |
| **Store Memberships** | `MembershipPolicy` | Staff invites, role assignments, access levels. |
| **CMS & Marketing** | `CmsPolicy` | Blog posts, marketing pages, documentation. |
| **Platform Admin** | `PlatformPolicy` | Global settings, super-admin only operations. |
| **Customer Profile** | `AddressPolicy` | Personal data, addresses, payment methods. |

### Capability Taxonomy (Naming Strategy)

Capabilities MUST follow the `{domain}.{action}` pattern to ensure stability and auditability.

*   **products**: `products.view`, `products.create`, `products.update`, `products.delete`, `products.inventory.manage`
*   **orders**: `orders.view`, `orders.create`, `orders.update`, `orders.refund`, `orders.cancel`
*   **stores**: `stores.view`, `stores.update`, `stores.delete`, `stores.settings.manage`
*   **memberships**: `memberships.view`, `memberships.invite`, `memberships.update`, `memberships.revoke`
*   **cms**: `cms.view`, `cms.create`, `cms.update`, `cms.delete`, `cms.publish`
*   **users**: `users.view`, `users.block`, `users.delete`
*   **platform**: `platform.settings.manage`, `platform.stores.view`

---

# 2. Project Structure (UPDATED)

Every layer must be grouped by **domain (feature)** before **type**.
This is a core principle of this architecture.

### Correct Structure

```plaintext
app/
 ├── Actions/
 │    ├── Store/
 │    ├── Cart/
 │    ├── Auth/
 │    ├── Order/
 │    ├── Product/
 │    ├── Payment/
 │    ├── Admin/
 │    │    ├── User/
 │    │    ├── Product/
 │    │    ├── Order/
 │    │    ├── Dashboard/
 │    │    ├── Store/
 │
 ├── DTOs/
 │    ├── Store/
 │    ├── Cart/
 │    ├── Auth/
 │    ├── Order/
 │    ├── Product/
 │    ├── Payment/
 │    ├── Admin/
 │    │    ├── User/
 │    │    ├── Product/
 │    │    ├── Order/
 │    │    ├── Store/
 │
 ├── Repositories/
 │    ├── Store/
 │    ├── Cart/
 │    ├── Order/
 │    ├── Product/
 │
 ├── Services/
 │    ├── Payment/
 │    ├── Store/
 │
 ├── Http/
 │    ├── Controllers/
 │    │    ├── Api/                    ← REQUIRED subfolder
 │    │    │    ├── Store/
 │    │    │    ├── Cart/
 │    │    │    ├── Auth/
 │    │    │    ├── Order/
 │    │    │    ├── Product/
 │    │    │    ├── Payment/
 │    │    │    ├── Admin/
 │    │    │    │    ├── User/
 │    │    │    │    ├── Product/
 │    │    │    │    ├── Order/
 │    │    │    │    ├── Dashboard/
 │    │    │    │    ├── Store/
 │
 │    ├── Requests/
 │    │    ├── Store/
 │    │    ├── Cart/
 │    │    ├── Auth/
 │    │    ├── Order/
 │    │    ├── Product/
 │    │    ├── Payment/
 │    │    ├── Admin/
 │    │    │    ├── User/
 │    │    │    ├── Product/
 │    │    │    ├── Order/
 │    │    │    ├── Store/
 │
 │    ├── Resources/
 │    │    ├── Cart/
 │    │    ├── Order/
 │    │    ├── Product/
 │    │    ├── Admin/
 │    │    │    ├── User/
 │    │    │    ├── Product/
 │    │    │    ├── Order/
 │    │    │    ├── Dashboard/
 │    │    │    ├── Store/
```

### Core Rules

#### 1. Domain First
- Every file MUST belong to a domain.
- Domains reflect business capabilities, not technical types.
- **Examples**: `Cart`, `Auth`, `Order`, `Product`, `Payment`.

#### 2. No Flat Structures
- **Forbidden**:
  ```plaintext
  Actions/
   ├── AddToCartAction.php
   ├── LoginUserAction.php
   ├── CreateOrderAction.php
  ```
- **Required**:
  ```plaintext
  Actions/
   ├── Cart/AddToCartAction.php
   ├── Auth/LoginUserAction.php
   ├── Order/CreateOrderAction.php
  ```

#### 3. Resources — Flat by Default, Domain When Needed
- Current Resources are **flat** (no domain subfolder).
- This is acceptable for simple resources.
- When a domain has **more than 3 resources**, group them:
  ```plaintext
  Resources/
   ├── CartResource.php         ← simple, stays flat
   ├── Product/
   │    ├── ProductResource.php
   │    ├── ProductCardResource.php
   │    ├── ProductDetailResource.php
   │    ├── ProductVariantResource.php
  ```
- Admin resources MUST always be domain-grouped.

#### 4. Cross-Layer Consistency
- Each use-case MUST stay within the same domain across all layers.
- **Example (`Cart` use case):**
  ```plaintext
  Http/Requests/Cart/AddItemRequest.php
  DTOs/Cart/AddToCartDTO.php
  Actions/Cart/AddToCartAction.php
  Repositories/Cart/CartRepository.php
  Http/Resources/CartResource.php
  ```

#### 5. No Cross-Domain Leakage
- `Cart` MUST NOT contain `Order` logic.
- `Auth` MUST NOT contain `Payment` logic.
- If interaction is needed → use **Services**.

#### 6. Services as Cross-Domain Orchestrators
- Services may coordinate multiple domains.
- **Example**: `Services/Payment/CheckoutService.php` can 
  orchestrate: `Cart` → `Order` → `Payment`.

---

# 3. Controllers

Controllers are **entry points only**.

### Responsibilities:

* Accept request
* Call Action/Service
* Return response

### Rules:

* MUST be thin (≈10–15 lines)
* MUST NOT contain business logic
* MUST NOT access Models directly
* MUST NOT perform validation
* MUST NOT handle exceptions manually
* MUST return responses via `ApiResponserTrait`
* MUST live under `Http/Controllers/Api/` subfolder

---

# 4. Business Logic (Actions & Services)

## Actions

Single-responsibility operations.

### Rules:

* One responsibility only
* Accept DTO
* Return Model or Value Object

## Services

Complex workflows.

### Rules:

* Orchestrate multiple Actions
* No request handling
* Keep logic readable and maintainable

---

# 5. DTOs (Data Transfer Objects)

DTOs are **mandatory**.

### Rules:

* Every Action MUST receive a DTO
* DTOs must be strictly typed
* DTOs must be immutable
* No arrays in business logic
* Provide `fromRequest()` factory

### 🔥 CRITICAL RULE — Multi-Store DTOs

All store-bound DTOs MUST include:

```php
public int $storeId;
```

#### Rules:
* `store_id` MUST NOT be extracted from the request body
* `store_id` MUST be injected from the route parameter `{store}`
* `storeId` MUST be the **first constructor parameter**

#### Exception:
* **Platform-level CMS DTOs** (Documentation, Marketing Pages) do NOT require `storeId` as they are shared globally across the platform.

---

# 6. Repositories

Repositories are the **only DB access layer**.

### Rules:

* No DB queries outside repositories
* No business logic inside repositories
* Return Models or Collections only

### 🔥 HARD RULE — Store Scoping (CRITICAL)

ALL commerce domain queries MUST be scoped by `store_id`.

#### ❌ Forbidden:
```php
Product::all();
Product::find($id);
```

#### ✅ Required:
```php
Product::where('store_id', $storeId)->get();
Product::where('store_id', $storeId)->findOrFail($id);
```

#### Exception:
* **CMS Platform Content**: Queries for `CmsDocument`, `CmsDocumentSection`, and `MarketingPage` are platform-level and MUST NOT include store scoping.

#### Rule:
Repositories MUST NEVER return cross-store data under any 
circumstance.

---

# 7. Localization Strategy (Unified CMS)

This project uses a unified localization strategy for all CMS domains (Blog, Documentation, Marketing).

### JSON Localized Maps (MANDATORY for CMS)

All translatable CMS fields MUST use JSON columns. Relational translation tables are FORBIDDEN for CMS content.

#### Model Casting:
```php
protected $casts = [
    'title'   => 'array',
    'slug'    => 'array',
    'content' => 'array',
];
```

#### Payload Shape:
```json
{
  "title": {
    "en": "English Title",
    "ar": "العنوان العربي"
  }
}
```

#### Benefits:
* Single row updates (no multi-table JOINs)
* Simplified Admin CMS Editor
* Perfect alignment with Next.js App Router metadata generation

---

# 8. API Responses

All responses are **centralized and standardized**.

## Response System

Uses `ApiResponserTrait`:

```php
abstract class Controller
{
    use ApiResponserTrait;
}
```

## Response Format

### Success

```json
{
  "status": true,
  "message": "Success",
  "data": {}
}
```

### Error

```json
{
  "status": false,
  "message": "Error message",
  "error_code": "ERROR_CODE",
  "errors": {}
}
```

## Rules

* Controllers MUST use:
  * `$this->success()`
  * `$this->paginated()`
* API Resources are **mandatory**
* Trait handles structure, Resources handle transformation

## Examples

```php
return $this->success(new CartResource($cart));
```

```php
return $this->paginated(CartResource::collection($carts));
```

## Forbidden

* Returning `response()->json()` directly
* Returning raw Models or arrays
* Bypassing ApiResponserTrait

---

# 8. Error Handling

Error handling is **centralized and exception-driven**.

## ErrorCode Enum

```plaintext
app/Enums/ErrorCode.php
```

### Current Error Codes:

```php
// --- Authentication (AUTH) ---
case AUTH_001 = 'AUTH_001'; // Invalid credentials
case AUTH_002 = 'AUTH_002'; // Unauthorized access
case AUTH_003 = 'AUTH_003'; // Email not verified
case AUTH_004 = 'AUTH_004'; // CSRF token mismatch
case AUTH_005 = 'AUTH_005'; // Password reset failed
case AUTH_006 = 'AUTH_006'; // Social authentication failed
case AUTH_007 = 'AUTH_007'; // Email verification failed
case AUTH_008 = 'AUTH_008'; // Too many requests

// --- Order (ORD) ---
case ORD_001 = 'ORD_001'; // Order not found
case ORD_002 = 'ORD_002'; // Order cancellation failed
case ORD_003 = 'ORD_003'; // Reorder failed

// --- Payment (PMT) ---
case PMT_001 = 'PMT_001'; // Payment failed
case PMT_002 = 'PMT_002'; // Out of stock during payment
case PMT_003 = 'PMT_003'; // Stripe webhook error
case PMT_004 = 'PMT_004'; // Stripe service error

// --- System (SYS) ---
case SYS_001 = 'SYS_001'; // Generic server error
case SYS_002 = 'SYS_002'; // Not Found

// --- Validation (VAL) ---
case VAL_001 = 'VAL_001'; // Validation failed

// --- Product (PRD) ---
case PRD_001 = 'PRD_001'; // Product not found

// --- Store (STR) ---
case STR_001 = 'STR_001'; // Store not found
case STR_002 = 'STR_002'; // Unauthorized store access
```

### Rules:

* ALL errors MUST use `ErrorCode`
* No hardcoded error codes
* Acts as contract with frontend

## Custom Exceptions

```plaintext
app/Exceptions/
 ├── BaseApiException.php
 ├── Auth/
 ├── Order/
 ├── Payment/
 ├── Product/
 ├── System/
 ├── Store/
 │    ├── StoreNotFoundException.php
 │    └── UnauthorizedStoreAccessException.php
```

### Rules:

* Extend `BaseApiException`
* Define: message, status code, error code

## Required Store Exceptions

```php
class StoreNotFoundException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('error.store_not_found'),
            statusCode: 404,
            errorCode: ErrorCode::STR_001->value,
        );
    }
}
```

```php
class UnauthorizedStoreAccessException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('error.unauthorized_store'),
            statusCode: 403,
            errorCode: ErrorCode::STR_002->value,
        );
    }
}
```

## Exception Registration

```php
->withExceptions(function (Exceptions $exceptions): void {
    app(ExceptionRegistrar::class)->handle($exceptions);
})
```

## Error Response Format

```json
{
  "status": false,
  "message": "Error message",
  "error_code": "ERROR_CODE",
  "errors": {}
}
```

## Handled Cases

### Business Exceptions
```php
if ($e instanceof BaseApiException) {
    return $e->render(request());
}
```

### Validation
```php
if ($e instanceof ValidationException) {
    return response()->json([
        'status' => false,
        'message' => 'Validation failed',
        'error_code' => ErrorCode::VAL_001->value,
        'errors' => $e->errors(),
    ], 422);
}
```

### HTTP
```php
if ($e instanceof HttpExceptionInterface) {
    return response()->json([
        'status' => false,
        'message' => $e->getMessage(),
        'error_code' => ErrorCode::SYS_002->value,
        'errors' => null,
    ], $e->getStatusCode());
}
```

### System
```php
Log::error($e);

return response()->json([
    'status' => false,
    'message' => config('app.env') === 'local'
        ? $e->getMessage()
        : 'Server Error',
    'error_code' => ErrorCode::SYS_001->value,
    'errors' => null,
], 500);
```

## Rules

* No try/catch in Controllers
* No manual error responses
* No raw exceptions returned
* No sensitive data exposure
* No hardcoded error codes

## Example: OutOfStockException

```php
class OutOfStockException extends BaseApiException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message ?: __('order.out_of_stock'),
            statusCode: 400,
            errorCode: ErrorCode::PMT_002->value,
        );
    }
}
```

Usage:
```php
if ($variant->quantity < $dto->quantity) {
    throw new OutOfStockException(__('cart.not_enough_stock'));
}
```

---

# 9. Validation

Handled via FormRequest only.

### Rules:

* No validation outside FormRequest
* Rules must be explicit and strict

---

# 10. Naming Conventions

* Actions → `Verb + Entity + Action`
* DTOs → `UseCase + DTO`
* Requests → `UseCaseRequest`
* Resources → `EntityResource`
* Repositories → `EntityRepository`
* Controllers → `EntityController` (under `Api/` subfolder)

---

# 11. Anti-Patterns (Forbidden)

* Fat Controllers
* Business logic in Models
* Static helpers for logic
* Direct `request()` usage
* Raw arrays or Models in responses
* Layer mixing
* Queries without `store_id` constraint 
  (except super_admin global analytics)
* Debug/test routes in `api.php` (`/test`, `/test-mailtrap`)

---

# 12. Performance Rules

* Use eager loading
* Avoid N+1 queries
* Cache heavy data when needed

---

# 13. Flow (Golden Path)

```plaintext
Request
 → FormRequest
 → DTO (with storeId from route)
 → Action
 → Repository (store-scoped)
 → Resource
 → ApiResponserTrait
```

### Rule:

No step may be skipped.

---

# 14. Real Example: Cart (Add to Cart)

## Route

```plaintext
POST /api/v1/stores/{store}/cart/items
```

## Form Request

```php
class AddItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_variant_id' => [
                'required', 
                'exists:product_variants,id',
            ],
            'quantity' => [
                'required', 
                'integer', 
                'min:1', 
                'max:10',
            ],
        ];
    }
}
```

## DTO

```php
class AddToCartDTO
{
    public function __construct(
        public int $storeId,
        public int $productVariantId,
        public int $quantity,
        public int $userId,
    ) {}

    public static function fromRequest(
        AddItemRequest $request,
        int $storeId,
    ): self {
        return new self(
            storeId: $storeId,
            productVariantId: $request->integer(
                'product_variant_id'
            ),
            quantity: $request->integer('quantity'),
            userId: $request->user()->id,
        );
    }
}
```

## Repository

```php
class CartRepository
{
    public function getOrCreate(
        User $user, 
        int $storeId,
    ): Cart {
        return Cart::firstOrCreate(
            [
                'user_id'  => $user->id,
                'store_id' => $storeId,
            ],
            [
                'user_id'  => $user->id,
                'store_id' => $storeId,
            ]
        );
    }

    public function findByUser(
        User $user, 
        int $storeId,
    ): ?Cart {
        return Cart::where('user_id', $user->id)
            ->where('store_id', $storeId)
            ->first();
    }
}
```

## Action

```php
class AddToCartAction
{
    public function __construct(
        private CartRepository $cartRepository,
        private CartItemRepository $cartItemRepository,
        private ProductVariantRepository $productVariantRepository,
    ) {}

    public function execute(AddToCartDTO $dto): Cart
    {
        return DB::transaction(function () use ($dto) {
            $user = User::findOrFail($dto->userId);

            $cart = $this->cartRepository->getOrCreate(
                $user,
                $dto->storeId,
            );

            $variant = $this->productVariantRepository
                ->findWithLock($dto->productVariantId);

            if (!$variant->is_active 
                || $variant->quantity < $dto->quantity) {
                throw new OutOfStockException(
                    __('cart.variant_not_available')
                );
            }

            $existingItem = $this->cartItemRepository
                ->findByCartAndVariant(
                    $cart,
                    $dto->productVariantId,
                );

            if ($existingItem) {
                $newQty = $existingItem->quantity 
                    + $dto->quantity;

                if ($variant->quantity < $newQty) {
                    throw new OutOfStockException(
                        __('cart.not_enough_stock')
                    );
                }

                $this->cartItemRepository->updateQuantity(
                    $existingItem,
                    $newQty,
                );
            } else {
                $this->cartItemRepository->create(
                    $cart,
                    $dto->productVariantId,
                    $dto->quantity,
                );
            }

            return $cart->load(['items.productVariant']);
        });
    }
}
```

## Controller

```php
class CartController extends Controller
{
    public function __construct(
        private AddToCartAction $addToCartAction,
    ) {}

    public function addItem(
        AddItemRequest $request,
        int $store,
    ): JsonResponse {
        $cart = $this->addToCartAction->execute(
            AddToCartDTO::fromRequest($request, $store)
        );

        return $this->success(new CartResource($cart));
    }
}
```

## Resource

```php
class CartResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'items'       => CartItemResource::collection(
                $this->items
            ),
            'total_items' => $this->items->sum('quantity'),
            'total_price' => $this->items->sum(
                fn($item) => $item->quantity 
                    * $item->productVariant->price
            ),
        ];
    }
}
```

## Key Takeaways

* Thin Controller
* DTO enforced with `storeId` from route
* Business logic isolated in Action
* Repository scoped by `store_id`
* Resource + Trait used
* Flow respected

---

# 15. Localization (Multilingual Support)

The project supports multiple languages:

* English (`en`)
* Arabic (`ar`)

## Language Structure

```plaintext
lang/
 ├── en/
 │    ├── auth.php
 │    ├── cart.php
 │    ├── order.php
 │    ├── payment.php
 │    ├── error.php
 │    ├── general.php
 │    ├── services.php
 ├── ar/
 │    ├── auth.php
 │    ├── cart.php
 │    ├── order.php
 │    ├── payment.php
 │    ├── error.php
 │    ├── general.php
 │    ├── services.php
```

## Rules

* All user-facing messages MUST use localization
* Use Laravel `__()` helper
* No hardcoded strings anywhere in the codebase

## Examples

```php
__('order.out_of_stock')
__('error.unauthorized_store')
__('error.store_not_found')
__('cart.variant_not_available')
__('cart.not_enough_stock')
```

## Middleware

Locale is resolved via middleware:

* Uses `Accept-Language` or `locale` header
* Falls back to supported locales
* Defaults to `config('app.locale')`

## Naming Convention

```php
__('order.out_of_stock')
__('cart.item_added')
__('auth.invalid_credentials')
__('payment.failed')
__('error.unauthorized_store')
__('error.store_not_found')
```

---

# 16. Admin & Dashboard Architecture Rules

## 16.1 Core Principles

Rules:
* Admin is NOT a separate model
* Admin = User with role via `spatie/laravel-permission`
* Admin APIs are strictly separated
* Admin logic MUST NOT pollute user-facing domains
* Admin follows same architecture contract
* Admin MUST be store-aware at all times

## 16.2 API Structure

All admin endpoints MUST include the store context:

```http
/api/v1/admin/stores/{store}/...
```

### Examples:
```http
GET    /api/v1/admin/stores/{store}/users
PATCH  /api/v1/admin/stores/{store}/users/{id}/block
DELETE /api/v1/admin/stores/{store}/users/{id}
POST   /api/v1/admin/stores/{store}/products
PATCH  /api/v1/admin/stores/{store}/orders/{id}/status
GET    /api/v1/admin/stores/{store}/dashboard/stats
```

#### ❌ Forbidden:
```http
/api/v1/admin/users
/api/v1/admin/products
/api/v1/admin/orders
```

## 16.3 Folder Structure

```plaintext
app/
 ├── Actions/Admin/
 │    ├── User/
 │    ├── Product/
 │    ├── Order/
 │    ├── Dashboard/
 │    ├── Store/
 │
 ├── DTOs/Admin/
 │    ├── User/
 │    ├── Product/
 │    ├── Order/
 │    ├── Store/
 │
 ├── Http/
 │    ├── Controllers/Api/Admin/
 │    │    ├── User/
 │    │    ├── Product/
 │    │    ├── Order/
 │    │    ├── Dashboard/
 │    │    ├── Store/
 │    │
 │    ├── Requests/Admin/
 │    │    ├── User/
 │    │    ├── Product/
 │    │    ├── Order/
 │    │    ├── Store/
 │    │
 │    ├── Resources/Admin/
 │    │    ├── User/
 │    │    ├── Product/
 │    │    ├── Order/
 │    │    ├── Dashboard/
 │    │    ├── Store/
```

## 16.4 Role & Permission System

Uses `spatie/laravel-permission`.

### Roles

#### Platform:
* `super_admin` → full global access, bypasses store restrictions

#### Store:
* `store_admin` → full access within a store
* `staff` → limited access within a store

#### Customer:
* `customer` → default users

### Permission Format: `entity.action`

```php
class PermissionEnum
{
    public const USER_VIEW          = 'user.view';
    public const USER_BLOCK         = 'user.block';
    public const USER_DELETE        = 'user.delete';
    public const USER_RESTORE       = 'user.restore';
    public const PRODUCT_CREATE     = 'product.create';
    public const PRODUCT_UPDATE     = 'product.update';
    public const PRODUCT_DELETE     = 'product.delete';
    public const ORDER_VIEW         = 'order.view';
    public const ORDER_UPDATE_STATUS = 'order.update_status';
    public const ORDER_CANCEL       = 'order.cancel';
    public const ORDER_REFUND       = 'order.refund';
}
```

### 🔥 Store-Scoped Permissions

#### ❌ Forbidden:
```php
hasPermissionTo('product.update')
```

#### ✅ Required:
```php
hasPermissionTo('product.update', $storeId)
```

## 16.5 Authorization Strategy

### Middleware Stack (ALL admin routes)
```php
->middleware([
    'auth:sanctum',
    'store.context',
    'permission:product.view',
])
```

### Store Membership Check (in Actions)
```php
if (!$user->stores()->where('store_id', $storeId)->exists()) {
    throw new UnauthorizedStoreAccessException();
}
```

**Exception**: `super_admin` bypasses this check.

### Policies (permission check ONLY — no business logic)
```php
public function update(User $user, int $storeId)
{
    return $user->hasPermissionTo(
        'product.update', 
        $storeId,
    );
}
```

### Actions (business rules ONLY)
```php
if ($product->is_locked) {
    throw new ProductLockedException();
}
```

## 16.6 Admin Actions Rules

### Separate Admin Actions
`Actions/Admin/Product/CreateProductAction.php`

### Admin Actions MAY reuse core Actions:
```php
class AdminCreateProductAction
{
    public function __construct(
        private CreateProductAction $createProduct,
    ) {}

    public function execute(AdminCreateProductDTO $dto)
    {
        return $this->createProduct->execute(
            $dto->toBaseDTO()
        );
    }
}
```

## 16.7 DTO Rules (Admin)

* Separate DTOs: `DTOs/Admin/Product/CreateProductDTO.php`
* All Admin DTOs MUST include `storeId` as first parameter
* Admin DTOs MAY transform into core DTOs via `toBaseDTO()`

#### ❌ Forbidden:
* Reusing user DTOs in admin
* Admin DTOs without `storeId`

## 16.8 User Management

### Customer Management (Admin)
* ✔ View (store-scoped)
* ✔ Block / Unblock
* ✔ Soft delete / Restore

### Sub-Admin Management
* ✔ Create / Update
* ✔ Assign roles (store-scoped)
* ✔ Block / Unblock / Delete / Restore

### Rules:
```php
$table->boolean('is_active')->default(true);
$table->softDeletes();
```

## 16.9 Product Management

Admin MUST support: Variants, Media, Categories, 
Pricing, Stock.

Complex operations → Multiple Actions OR a Service.

## 16.10 Order Management

Operations (each = separate Action):
* `UpdateOrderStatusAction`
* `CancelOrderAction`
* `RefundOrderAction`

## 16.11 Dashboard Domain

Location: `Actions/Admin/Dashboard/`

### Store Dashboard (store-scoped):
* Revenue, Orders, Customers per store

### Global Dashboard (super_admin only):
* Total revenue, Total stores, System stats

Dashboard MUST use Repositories, NOT direct Model access.

## 16.12 Soft Delete Strategy

Soft delete queries MUST include `store_id`:

```php
Product::withTrashed()
    ->where('store_id', $storeId)
    ->get();
```

## 16.13 Admin Controllers

```php
class AdminUserController extends Controller
{
    public function index(
        GetUsersRequest $request,
        int $store,
        GetUsersAction $action,
    ) {
        return $this->paginated(
            AdminUserResource::collection(
                $action->execute(
                    GetUsersDTO::fromRequest($request, $store)
                )
            )
        );
    }
}
```

## 16.14 Admin Resources

Admin resources MUST be domain-grouped and MAY differ 
from user resources.

Admin sees: `email`, `status`, `roles`, `store_id`  
User sees: limited data only

## 16.15 Security Rules

* ✔ Role must be `store_admin` or `super_admin`
* ✔ Permissions must be store-scoped
* ✔ Store membership validated on every request
* ✔ Unauthorized → 403

## 16.16 Super Admin Rules

* ✔ Access ALL stores
* ✔ Bypass store membership check
* ✔ Access global dashboard

## 16.17 Golden Flow (Admin)

```plaintext
Request
 → auth:sanctum
 → store.context middleware
 → permission middleware (store-scoped)
 → FormRequest
 → Admin DTO (storeId from route)
 → Store Membership Check (in Action)
 → Admin Action
 → (optional) Core Action
 → Repository (store-scoped)
 → Admin Resource
 → ApiResponserTrait
```

---

# 17. Multi-Store Architecture Rules

## 17.1 Core Principle

Multi-tenant (single database, shared schema).

Rules:
* Every business entity MUST belong to a Store
* Store isolation is mandatory
* No data leakage between stores
* Store context MUST exist in every request
* Must support future marketplace extension

## 17.3 Store Ownership Model

User ↔ Store is **MANY-TO-MANY**.

### Tables:
* `users`
* `stores`
* `store_user` (pivot)

### Pivot columns:
```php
$table->foreignId('store_id')
      ->constrained('stores')
      ->cascadeOnDelete();

$table->foreignId('user_id')
      ->constrained('users')
      ->cascadeOnDelete();

$table->string('role');

$table->unique(['store_id', 'user_id']);
```

## 17.4 Required Tables

* `stores` (with `owner_id`, `slug`, `is_active`, softDeletes)
* `store_user` (pivot with role)

## 17.5 Required store_id Columns (STRICT)

ALL of the following MUST include `store_id`:

* `products`
* `orders`
* `carts`
* `cart_items` 🔥
* `addresses`
* `reviews`
* `categories` *(recommended)*

## 17.6 Route Structure (ENFORCED)

```plaintext
/api/v1/stores/{store}/...
/api/v1/admin/stores/{store}/...
```

#### ❌ Forbidden:
```plaintext
/api/v1/products
/api/v1/orders
/api/v1/admin/users
```

## 17.7 StoreContext Middleware

```php
class StoreContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $storeId = $request->route('store');

        $store = Store::where('id', $storeId)
            ->where('is_active', true)
            ->first();

        if (!$store) {
            throw new StoreNotFoundException();
        }

        app()->instance('storeId', $store->id);
        app()->instance('currentStore', $store);

        return $next($request);
    }
}
```

Registered as alias in `bootstrap/app.php`:
```php
$middleware->alias([
    'store.context' => StoreContext::class,
]);
```

## 17.7 Authorization Layers

### 1. Middleware
```php
->middleware(['auth:sanctum', 'store.context', 'permission:x'])
```

### 2. Store Membership (in Actions)
```php
if (!$user->stores()->where('store_id', $storeId)->exists()) {
    throw new UnauthorizedStoreAccessException();
}
```

### 3. Policies → permission check only
### 4. Actions → business logic only

## 17.8 Roles

| Scope    | Role          | Access                        |
|----------|---------------|-------------------------------|
| Platform | `super_admin` | All stores, bypass all checks |
| Store    | `store_admin` | Full access within store      |
| Store    | `staff`       | Limited access within store   |
| Default  | `customer`    | Customer-facing actions only  |

## 17.9 Super Admin Exception

Super admin global analytics are the **only exception** 
to the `store_id` constraint.

## 17.10 Soft Deletes

Even trashed queries MUST include `store_id`:

```php
Model::withTrashed()
    ->where('store_id', $storeId)
    ->get();
```

## 17.11 Future Marketplace Compatibility

Architecture MUST allow:
* `vendors`
* `shared_products`
* `store_products` (mapping table)

---

# 18. CMS Architecture Rules

## 18.1 CMS Philosophy
The CMS is an **independent domain** responsible for managing non-commerce content. While the Commerce domain (Products, Orders, Cart) handles transactional entities, the CMS domain handles the storytelling, layout, and informational aspects of the platform.

*   **Commerce Domain**: Products, Orders, Cart, Payments, Inventory.
*   **CMS Domain**: Pages, Blocks, Sections, Navigation, SEO, Media.

## 18.2 CMS Entity Ownership (Multi-Tenancy)
All CMS entities are strictly **store-scoped**.
*   Every Page, Block, or Menu MUST belong to a `store_id`.
*   Store isolation is absolute; CMS content is never shared across stores.
*   Queries for CMS entities MUST include the `store_id` constraint in Repositories.

## 18.3 CMS Rendering Principles (API-First Only)
The backend serves as a **pure content provider**.
*   **No Blade Rendering**: The backend MUST NOT generate HTML or use Blade templates for CMS content.
*   **JSON Delivery**: The backend delivers structured JSON data representing the page structure and content.
*   **Frontend Responsibility**: The Next.js frontend is responsible for interpreting the JSON schema and rendering the corresponding UI components.
*   **Storefront Rendering**: The storefront API delivers optimized, read-only content for public consumption.

## 18.4 Dynamic Schema Philosophy
The CMS uses a **flexible block-based schema** to support modern page builders.
*   **Reusable Blocks/Sections**: Pages are composed of an ordered array of blocks (e.g., `Hero`, `ProductCarousel`, `RichText`).
*   **Schema Validation**: Each block type has a predefined JSON schema. The backend MUST validate block content against these schemas during creation/update.
*   **Flexible Layouts**: The architecture allows for nested blocks and flexible section arrangements without database schema changes.

## 18.5 Separation between Content and Commerce
*   **Decoupled Entities**: CMS pages should reference Commerce entities (e.g., `product_id`, `category_slug`) but MUST NOT duplicate commerce data.
*   **Reference-based Integration**: A "Featured Product" block stores the `product_id`. The API response may include basic product details via a Repository call, but the source of truth remains the Commerce domain.

## 18.6 Draft/Publish Workflow
The CMS supports a robust content lifecycle:
*   **Draft**: Content currently being edited; visible only via Admin APIs.
*   **Published**: The live version of the content; delivered by the Storefront API.
*   **Workflow**: Content transitions from Draft to Published via a `PublishAction`.

## 18.7 CMS Folder Structure (Domain-First)
The CMS system follows the strict **Domain-First** architecture. All CMS domains MUST be nested under a `Cms` namespace to maintain isolation from commerce domains.

### Structure:
```plaintext
app/
 ├── Actions/Cms/
 │    ├── Page/           (e.g., CreatePageAction, PublishPageAction)
 │    ├── Block/          (e.g., CreateBlockAction, SyncGlobalBlockAction)
 │    ├── Menu/           (e.g., BuildMenuTreeAction)
 │    ├── Seo/            (e.g., UpdateSeoMetadataAction)
 │    ├── Redirect/       (e.g., CreateRedirectAction)
 │    ├── Template/       (e.g., RegisterTemplateAction)
 │    ├── Media/          (e.g., ProcessCmsMediaAction)
 │
 ├── DTOs/Cms/
 │    ├── Page/           (e.g., PageDTO, CreatePageDTO)
 │    ├── Block/          (e.g., BlockDTO, BlockSchemaDTO)
 │    ├── Menu/
 │    ├── Seo/
 │    ├── Redirect/
 │    ├── Template/
 │    ├── Media/
 │
 ├── Repositories/Cms/
 │    ├── Page/           (e.g., PageRepository)
 │    ├── Block/          (e.g., BlockRepository)
 │    ├── Menu/           (e.g., MenuRepository)
 │    ├── Redirect/
 │    ├── Media/
 │
 ├── Services/Cms/
 │    ├── Content/        (e.g., ContentCompositionService)
 │    ├── Rendering/      (e.g., BlockRendererContractService)
 │    ├── SEO/            (e.g., SitemapGeneratorService)
 │
 ├── Http/
 │    ├── Controllers/Api/
 │    │    ├── Cms/       (STOREFRONT APIs - Read-only)
 │    │    │    ├── PageController.php
 │    │    │    ├── MenuController.php
 │    │    │    ├── SitemapController.php
 │    │    ├── Admin/Cms/ (ADMIN APIs - Management)
 │    │    │    ├── PageController.php
 │    │    │    ├── BlockController.php
 │    │    │    ├── MenuController.php
 │    │    │    ├── RedirectController.php
 │    │
 │    ├── Requests/Cms/ (and Admin/Cms/)
 │    │    ├── Page/
 │    │    ├── Block/
 │    │
 │    ├── Resources/Cms/ (and Admin/Cms/)
 │    │    ├── Page/
 │    │    ├── Block/
```

## 18.8 Admin CMS vs. Storefront API
The CMS domain is split into two distinct API surfaces:

### 1. Admin CMS (Management)
*   **Purpose**: Full CRUD operations for content managers.
*   **Rules**:
    *   MUST support Draft vs. Published states.
    *   MUST allow raw schema editing and validation.
    *   Endpoints live under `/api/v1/admin/stores/{store}/cms/...`.
    *   Returns detailed management metadata (author, timestamps, version history).

### 2. Storefront CMS (Public Delivery)
*   **Purpose**: High-performance, read-only content delivery for Next.js.
*   **Rules**:
    *   MUST ONLY return "Published" content.
    *   MUST deliver optimized JSON payloads (stripped of admin metadata).
    *   Endpoints live under `/api/v1/stores/{store}/cms/...`.
    *   MUST be cache-friendly and optimized for SEO.

## 18.9 Content Composition & Reusability Rules

### Reusable Blocks
*   **Definition**: A "Global Block" is a block entity stored independently of any page.
*   **Rule**: Global blocks are identified by a unique `handle` or `uuid`.
*   **Usage**: Multiple pages may reference the same Global Block. Updating the Global Block updates all referencing pages.

### Content Composition
*   **Composition Model**: A Page is a collection of `BlockInstances`.
*   **Block Instance**: Contains the block `type` (e.g., `hero-banner`), its local `content` (data), and `settings` (layout/styling).
*   **Mixed Content**: A page can contain both local-only blocks and references to Global Blocks.

### Content Rendering Contract
To ensure the Next.js frontend can render content reliably, the API MUST follow a strict JSON contract:

```json
{
  "type": "block-type-identifier",
  "data": {
    "title": "...",
    "image": "...",
    "cta_link": "..."
  },
  "settings": {
    "background_color": "#ffffff",
    "padding": "large"
  }
}
```
*   **Contract Rule**: Every block MUST have a `type`, `data`, and `settings` key.

## 18.10 Shared Reusable CMS Services
The `Services/Cms/` folder contains orchestrators that don't belong to a single entity:
*   **`ContentCompositionService`**: Responsible for resolving a page's block tree, including fetching global blocks and injecting commerce data (e.g., resolving a product ID into a product card).
*   **`BlockRendererContractService`**: Validates that block JSON payloads adhere to the defined schema before storage.

## 18.11 SEO & Localization
*   **SEO Content**: Every Page and major CMS entity MUST include metadata (Title, Description, OG Tags, Canonical URLs).
*   **Localized Content**: CMS content MUST support multi-lingual fields. Translatable strings are handled at the application level, following the project's localization rules.

## 18.12 Architectural Integration
The CMS system MUST integrate naturally with the existing core architecture:
*   **DTO Architecture**: Every CMS Action (e.g., `CreatePageAction`) MUST receive a strictly typed DTO including `storeId` as the first parameter.
*   **Repository Pattern**: All CMS data access MUST go through Repositories and MUST be store-scoped.
*   **Action-Based Logic**: All business logic (publishing, validation, schema processing) MUST be encapsulated in Actions.

## 18.13 Future Extensibility
The CMS architecture is designed to be extensible:
*   New block types can be added by defining a new schema and frontend component.
*   Supports future features like scheduled publishing, content versioning, and A/B testing.

# 19. CMS Database Architecture Rules

## 19.1 Core Storage Principles
The CMS database architecture is designed for maximum flexibility while maintaining strict multi-tenant isolation and data integrity.

### 1. Mandatory JSON for Dynamic Content
JSON columns MUST be used for all entities requiring structural flexibility.
*   **`blocks.content`**: Stores the actual data (text, images, links) for a block.
*   **`block_instances.settings`**: Stores layout-specific settings (padding, colors, visibility).
*   **`pages.layout`**: Stores the ordered list of block instances and their configurations.
*   **`seo_metas.meta`**: Stores open graph tags, schema.org data, and custom meta tags.

### 2. No Rigid Schemas for Dynamic Content
DO NOT create relational tables for specific block types (e.g., `hero_blocks`, `carousel_blocks`). All dynamic structures must live within JSON fields to allow the frontend and page builders to evolve without migration friction.

## 19.2 Store Isolation (STRICT)
ALL CMS entities MUST include a `store_id` foreign key.
*   **Entities**: `pages`, `blocks`, `menus`, `redirects`, `media`, `seo_metas`.
*   **Uniqueness**: Slugs and handles MUST be unique per store.
    *   `UNIQUE(store_id, slug)`
    *   `UNIQUE(store_id, handle)`

## 19.3 Content Lifecycle & States
Every CMS entity (except redirects and media) MUST support:
*   **`is_published`**: Boolean flag.
*   **`published_at`**: Timestamp for scheduled or historical publishing.
*   **`deleted_at`**: Mandatory Soft Deletes.
*   **Draft State**: Managed via a `version` system or a dedicated `draft_content` JSON column.

## 19.4 Localization Architecture
The CMS MUST be localization-ready from the database layer.
*   **JSON-based Translations**: Translatable fields (e.g., `title`, `content`) MUST be stored as JSON objects where keys are locale codes (`en`, `ar`).
    *   Example: `{"en": "Hello", "ar": "مرحبا"}`.
*   **Rule**: Never create separate tables for translations.

## 19.5 Relationships & Polymorphism

### 1. SEO Entities
SEO metadata MUST be attached via a **Polymorphic Relationship** (`seoable`).
*   Supported entities: `Page`, `Product`, `Category`, `GlobalBlock`.

### 2. Media Ownership
Media entities (images, videos, documents) are global to the store but attached to entities via a many-to-many or polymorphic relation.
*   **Rule**: Media MUST belong to a `store_id`.
*   **Rule**: Deleting a store MUST cascade and delete all associated media files and records.

### 3. Page-Block Relationship
*   A **Page** has many **BlockInstances**.
*   A **BlockInstance** references a **GlobalBlock** (optional) and contains local overrides.

## 19.6 Selection Criteria: JSON vs Relational

| Feature | Use JSON When... | Use Relational When... |
|---------|------------------|------------------------|
| **Data Structure** | Dynamic, nested, or evolving | Fixed, flat, and stable |
| **Querying** | Primarily fetched as a whole | Needs filtering or joining |
| **Integrity** | Schema-less validation (JSON Schema) | Foreign key constraints needed |
| **Example** | Block content, Page layouts | Slugs, Store ownership, Redirects |

## 19.7 Cache Invalidation Principles
CMS content is high-read, low-write.
*   **Store-Level Tagging**: All CMS cache entries MUST be tagged with `store:{id}`.
*   **Entity-Level Tagging**: Cache entries for pages/blocks MUST be tagged with `cms:{entity}:{id}`.
*   **Invalidation**: Actions (Create/Update/Delete/Publish) MUST trigger tag-based invalidation.
*   **Rule**: Storefront APIs MUST never serve stale CMS content after a "Publish" action.

# 20. CMS DTO & Action Rules

## 20.1 DTO Architecture (MANDATORY)
All CMS operations MUST use strictly typed DTOs. CMS DTOs MUST follow the project-wide requirement of having `storeId` as the first constructor parameter.

### Rules:
*   **Nested Payloads**: DTOs for Pages MUST support nested block structures (typically as an array of `BlockDTO` or `BlockInstanceDTO`).
*   **Localized Fields**: Translatable fields MUST be accepted as associative arrays (`['en' => '...', 'ar' => '...']`) and typed as `array`.
*   **SEO Structures**: DTOs MUST include a nested `SeoDTO` or `meta` array for SEO metadata.
*   **Immutability**: All CMS DTOs MUST be immutable.

### Example: CreatePageDTO
```php
class CreatePageDTO
{
    public function __construct(
        public int $storeId,
        public array $title, // Localized: ['en' => 'Home', 'ar' => 'الرئيسية']
        public string $slug,
        public array $layout, // Array of block configurations
        public CreateSeoDTO $seo,
        public bool $isPublished = false,
    ) {}

    public static function fromRequest(CreatePageRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            title: $request->array('title'),
            slug: $request->string('slug'),
            layout: $request->array('layout'),
            seo: CreateSeoDTO::fromArray($request->array('seo')),
            isPublished: $request->boolean('is_published'),
        );
    }
}
```

## 20.2 CMS Actions (Single Responsibility)
Actions MUST perform exactly one business operation. Complex workflows MUST be handled by **Services**.

### Core CMS Actions:
*   **`CreatePageAction`**: Validates slug availability within the store and persists the page entity.
*   **`PublishPageAction`**: Validates content readiness, updates `is_published` and `published_at`, and triggers cache invalidation.
*   **`DuplicatePageAction`**: Clones a page entity, generating a unique slug (e.g., `-copy`) and duplicating its layout/blocks.
*   **`ReorderBlocksAction`**: Updates the sequence of blocks within a page's layout JSON.
*   **`ResolvePageBySlugAction`**: (Storefront) Retrieves a published page by slug, ensuring store-scoping.

### Action Rules:
*   **Slug Collision**: Actions MUST handle slug collisions within the same store. A 422 error or automatic suffixing is required.
*   **Block Validation**: Actions MUST delegate block content validation to a `BlockValidatorService` or similar.
*   **Reusable References**: When a page layout references a Global Block, the Action MUST only store the reference (ID/Handle), not a copy of the block content.

## 20.3 CMS Orchestration Services
Services are used to coordinate multiple Actions or complex logic that spans entities.

*   **`PagePublishingService`**: Orchestrates `PublishPageAction` + `SitemapUpdateAction` + `CacheClearAction`.
*   **`PageRenderingService`**: (Storefront) Fetches a page, resolves all Global Block references, injects commerce data, and returns a fully hydrated response.
*   **`MenuGenerationService`**: Builds nested menu trees from raw database records, handling localization and active-state logic.
*   **`SeoResolutionService`**: Merges entity-specific SEO metadata with store-wide defaults to produce the final meta payload.

## 20.4 Repository Rules
Repositories are the **only** layer allowed to perform database queries.

### Retrieval Patterns:
*   **Hydrated Pages**: Repositories MUST support eager loading or manual hydration of SEO metadata and associated media.
*   **Nested Blocks**: For storefront delivery, Repositories MUST provide methods to retrieve pages with their full block tree resolved.
*   **Localization**: Repositories MUST return localized fields as they are stored (JSON objects), leaving transformation to the **API Resource** layer.

### Strict Separation of Concerns:
*   **Repositories**: DB access, store-scoping (`where('store_id', $storeId)`).
*   **Actions**: Business rules, slug validation, permission checks (store-scoped).
*   **Services**: Workflow orchestration, cross-domain coordination (e.g., CMS + Product).

# 21. CMS API Contract Rules

## 21.1 Storefront API (Public Delivery)
Storefront APIs are read-only and optimized for Next.js (SSR/ISR/SWR).

### Endpoints:
*   `GET /api/v1/stores/{store}/cms/pages/{slug}`: Returns a published page by slug.
*   `GET /api/v1/stores/{store}/cms/menus/{handle}`: Returns a nested menu tree.
*   `GET /api/v1/stores/{store}/cms/sitemap`: Returns data for sitemap generation.

### Rules:
*   **Published Only**: Storefront APIs MUST NEVER return draft content.
*   **Slug Resolution**: Slugs are resolved within the store context. Homepages typically use the slug `index` or `/`.
*   **Cache Control**: Responses SHOULD include tags for Next.js On-Demand Revalidation.

## 21.2 Admin API (Management)
Admin APIs handle the full content lifecycle within the dashboard.

### Endpoints:
*   `GET|POST|PUT|DELETE /api/v1/admin/stores/{store}/cms/pages`
*   `GET|POST|PUT|DELETE /api/v1/admin/stores/{store}/cms/blocks`
*   `GET|POST|PUT|DELETE /api/v1/admin/stores/{store}/cms/menus`
*   `GET /api/v1/admin/stores/{store}/cms/pages/{id}/preview`: Secure endpoint for draft preview.

## 21.3 Response Contracts
The backend delivers **Structure**; the frontend renders **Components**.

### Page Response Structure:
```json
{
  "id": 1,
  "title": "Home Page",
  "slug": "index",
  "seo": {
    "title": "Best Ecommerce Store",
    "description": "...",
    "og_image": "https://..."
  },
  "layout": [
    {
      "type": "hero",
      "data": {
        "title": "Welcome to Our Store",
        "cta_label": "Shop Now",
        "cta_url": "/products"
      },
      "settings": { "full_width": true, "theme": "dark" }
    },
    {
      "type": "product-grid",
      "data": { "category_id": 12, "limit": 4 },
      "settings": { "columns": 4 }
    }
  ]
}
```

### Rules:
*   **Block Identifier**: Every block MUST have a `type` string that matches a Next.js component name.
*   **Localized Content**: Content fields MUST be returned in the requested locale or fall back to the store default.
*   **SEO Payloads**: Every page response MUST include a complete SEO object for SSR metadata injection.

## 21.4 Draft Preview & Revalidation Strategy
*   **Preview Mode**: The Admin API provides a signed URL or token that allows the Next.js frontend to bypass cache and fetch `is_published = false` content.
*   **ISR Revalidation**: Upon a "Publish" action, the backend SHOULD trigger a webhook to Next.js to revalidate the affected page paths.

## 21.5 Validation Philosophy
Dynamic blocks are validated using **JSON Schema**.
*   Each `type` (e.g., `hero`) has a corresponding JSON Schema.
*   The backend validates the `data` and `settings` objects against the schema before persisting.

## 21.6 Future Extensibility
The CMS architecture is built for growth:
*   **Landing Pages**: Ad-hoc pages with unique block arrangements for marketing.
*   **Blog System**: Extension of the `Page` model with categories, tags, and author profiles.
*   **Marketing Campaigns**: Time-bound content visibility using `published_at` and `expired_at`.
*   **A/B Testing**: Ability for the API to return variant `layout` arrays for experiment-tracked users.
*   **Reusable Templates**: Library of predefined block configurations (e.g., "Contact Page Template").

# 22. CMS Authorization & Security Rules

## 22.1 Permissions & Roles (Store-Scoped)
The CMS system uses `spatie/laravel-permission` with mandatory store-scoping. All CMS permissions follow the `cms.{entity}.{action}` format.

### Required Permissions:
*   `cms.page.view` - Access to view pages in admin.
*   `cms.page.create` - Ability to create new pages.
*   `cms.page.publish` - Dedicated permission for the "Publish" action.
*   `cms.block.update` - Ability to manage global/reusable blocks.
*   `cms.menu.manage` - Ability to build and update menus.
*   `cms.seo.update` - Ability to edit SEO metadata.

### Rules:
*   **Store-Scoped Checks**: `hasPermissionTo('cms.page.publish', $storeId)` is mandatory for all admin actions.
*   **Super Admin Bypass**: `super_admin` role bypasses all store-scoped permission checks.

## 22.2 Data Isolation & Public Access
*   **Query Scoping**: ALL CMS queries (Pages, Blocks, Menus, Media) MUST include the `where('store_id', $storeId)` constraint in the Repository layer.
*   **Public API Restriction**: Public storefront APIs MUST enforce `is_published = true` and `published_at <= now()`. Draft pages MUST NEVER be publicly accessible.
*   **Slug Integrity**: Slug uniqueness is enforced at the store level. Actions MUST prevent slug collisions before persistence.

## 22.3 Content Security (XSS & JSON)
Since the CMS handles dynamic JSON content, security is a shared responsibility between backend and frontend.

### Backend Rules:
*   **JSON Schema Validation**: Every block payload MUST be validated against a JSON Schema to prevent injection of unexpected fields or scripts.
*   **Untrusted Content**: The backend delivers content "as-is" from the database but MUST ensure JSON integrity.
*   **XSS Prevention**: The backend MUST NOT store raw, unencoded script tags. While Next.js handles most XSS by default, the backend should perform basic sanitization on rich-text inputs using a library like `HTMLPurifier` during the Action phase.

### Frontend Expectations:
*   **Sanitization**: The Next.js frontend is responsible for sanitizing content when using `dangerouslySetInnerHTML`.
*   **Component Safety**: Block components MUST NOT execute arbitrary JavaScript strings passed via the API.

## 22.4 Media & Preview Security
*   **Media Ownership**: Media uploads MUST be validated against the active `store_id`. A user cannot reference media from another store in their CMS content.
*   **Preview Security**: Preview URLs (for draft content) MUST be signed using Laravel's `URL::signedRoute()` or protected by a short-lived token. Access to previews requires `cms.page.view` permission.
*   **Media Exposure**: Private media (e.g., restricted documents) MUST NOT be served via public CMS URLs.

## 22.5 Cache Invalidation Security
*   **Tag-Based Clearing**: When content is published, the `cms:{entity}:{id}` and `store:{id}` cache tags MUST be cleared immediately to prevent unauthorized access to stale content or metadata leaks.

# 23. CMS Adoption & Migration Strategy

## 23.1 Core Principles for Migration
The CMS is designed to be introduced into the existing ecommerce ecosystem as a non-breaking, optional extension.

### Rules:
*   **Optionality**: CMS functionality MUST be optional. A store should be fully operational for commerce without any CMS records.
*   **Isolation**: CMS routes, actions, and repositories MUST remain isolated from core commerce logic (Products, Orders, Cart).
*   **Backward Compatibility**: Existing ecommerce APIs (e.g., `/api/v1/stores/{store}/products`) MUST continue to work without modification.

## 23.2 Recommended Rollout Order
Adoption should follow a phased approach to minimize risk:

1.  **Phase 1: Static Pages**: Migrate existing static frontend pages (About Us, Contact, FAQ) to the CMS using a simple "Rich Text" block.
2.  **Phase 2: Dynamic Landing Pages**: Introduce the ability to create new pages with multiple block types for marketing campaigns.
3.  **Phase 3: Reusable Content**: Introduce Global Blocks for shared sections like footers, promotional banners, or size guides.
4.  **Phase 4: Advanced CMS Integration**: Implement complex features like SEO management, dynamic menus, and commerce-data-injected blocks (e.g., "Trending Products" carousel).

## 23.3 Frontend Migration Safety
The Next.js storefront can adopt the CMS incrementally using the following strategy:
*   **Hybrid Routing**: Use Next.js middleware or catch-all routes (`[[...slug]].tsx`) to check for a CMS page. If no CMS page exists, fall back to the existing static frontend route or a 404.
*   **Parallel Coexistence**: Static frontend pages and CMS-driven pages can coexist. A page like `/about` can be migrated to the CMS while `/checkout` remains a dedicated, static commerce page.

## 23.4 SEO Migration Considerations
To maintain search rankings during migration:
*   **Redirect Management**: Use the CMS `Redirect` domain to manage legacy URLs that may change during the move to a CMS-driven structure.
*   **Metadata Parity**: Ensure that CMS-driven pages provide at least the same level of SEO metadata (titles, descriptions) as the static pages they replace.

## 23.5 API Versioning & Compatibility
*   **Stable Versioning**: Introduce CMS endpoints under the current API version (`/api/v1/...`).
*   **Feature Detection**: The frontend should check for the presence of CMS data before attempting to render CMS components.
* **Graceful Fallbacks**: If a CMS API call fails or returns empty, the frontend MUST have a fallback state (e.g., rendering a default static message or hiding the block).

# 24. Authentication & Onboarding Architecture

## 24.1 Actor Types & Contexts
The system distinguishes between actors to ensure proper lifecycle handling.

*   **SUPER_ADMIN**: Platform-level global access. Bypasses all store restrictions.
*   **STORE_OWNER**: The creator/legal owner of a store.
*   **STORE_ADMIN**: Managed user with high-level access to a specific store.
*   **STORE_STAFF**: Managed user with limited access (e.g., Order processing).
*   **CUSTOMER**: Public user who browses and purchases. No dashboard access.

### Actor Separation Rules
1.  **Merchant Auth != Customer Auth**: Merchant dashboard sessions are operational; customer sessions are storefront sessions.
2.  **Identity Boundaries**: Store context MUST NEVER leak into a customer's global identity. Customers belong to storefronts; Merchants belong to organizations/stores.
3.  **Session Lifecycle**: Merchant sessions are strictly stateful (Sanctum) and bound to the administrative domain.

## 24.2 Merchant vs. Customer Lifecycles
Authentication is separated by "Actor Context" even if sharing the same database table.

### Merchant Lifecycle (Dashboard)
1.  **Register**: `POST /v1/users/auth/register`
2.  **Verify**: Email verification wall.
3.  **Onboard**: Create first store.
4.  **Manage**: Access `/api/v1/admin/stores/{store}/...`

### Customer Lifecycle (Storefront)
1.  **Browse**: Public access to products.
2.  **Auth**: `POST /v1/account/auth/login` (Context-aware).
3.  **Purchase**: Checkout and order management.
4.  **Profile**: Access `/api/v1/account/...`

## 24.3 Merchant Onboarding State Machine
All merchant users MUST pass through the onboarding state machine.

1.  **PENDING_VERIFICATION**: Registered but email not verified. Restricted to verification screen.
2.  **CREATE_STORE**: Email verified but no store exists. Restricted to store creation screen.
3.  **COMPLETED**: Store created. Full access to dashboard.

### Onboarding Gating Rules
*   **Rule**: NO merchant dashboard access without `onboarding_step = COMPLETED` (except for `super_admin`).
*   **Rule**: `CUSTOMER` users bypass onboarding entirely (onboarding is for merchants only).

## 24.4 Bootstrap API Philosophy
To ensure a high-performance SPA experience, the backend provides a "Bootstrap Payload".

*   **Endpoint**: `GET /api/v1/users/bootstrap`
*   **Purpose**: Single call to initialize the frontend state.
*   **Contract Stability**: The bootstrap payload is a typed DTO contract. Frontend MUST treat it as the single source of truth.
*   **Typed Contents**: User, Stores, Active Store, Permissions, Onboarding status, and App Config.

## 24.5 Store Context & Active Selection
The system must always know which store the merchant is currently managing.

*   **Resolution**: Resolved via `{store}` route parameter OR `last_active_store_id`.
*   **Active Store Resolution Rules**:
    1.  Explicit `{store}` parameter takes precedence.
    2.  `last_active_store_id` is the session fallback.
    3.  If both missing, fallback to the first available store.
    4.  If no stores exist, the user is forced into the `CREATE_STORE` onboarding step.
*   **Switching**: Updating `last_active_store_id` via `PATCH /v1/users/active-store`.
*   **Validation**: Every request MUST validate that the authenticated user is a member of the resolved store.

## 24.6 Store Ownership & Membership
*   **Ownership**: One user is the `owner_id` of the `Store`.
*   **Membership**: Managed via `store_user` pivot table with `role` and `status`.
*   **Guarantees**: A store MUST have exactly one owner.

## 24.7 Store Slug Hardening
Store slugs are sensitive as they define the tenant's identity.

*   **Reserved Keywords**: Slugs like `admin`, `api`, `support`, `billing` are forbidden.
*   **Normalization**: Slugs are lowercase, URL-safe strings.
*   **Validation**: Real-time validation via `POST /api/v1/stores/validate-slug`.

## 24.8 Policy Architecture
To ensure clean authorization, all store-scoped actions MUST be authorized via `StorePolicy`.

*   **Rule**: Controllers MUST use `$this->authorize('view|update|delete', $storeModel)`.
*   **Rule**: Actions MUST NOT perform authorization checks; they assume the controller has already verified access.
*   **Rule**: The `StorePolicy` is responsible for checking `store_user` membership and role-based access.

## 24.9 Permission Resolution Rules
Permissions are resolved dynamically based on the active context.

*   **Layer**: `PermissionResolver` service centralizes logic.
*   **Merchant Permissions**: Resolved via `store_user` role in the active store.
*   **Super Admin Permissions**: Global bypass (all permissions).
*   **Customer Permissions**: Limited to storefront-specific actions (orders, profile).

## 24.10 Frontend vs Backend Responsibilities
*   **Backend**: Enforces onboarding state, validates slugs, provides the bootstrap payload, and secures all admin routes via `onboarding.completed` and `StorePolicy`.
*   **Frontend**: Uses the `bootstrap` payload as the single source of truth for UI state (e.g., redirecting to verification, store creation, or dashboard).

## 24.11 Customer Architecture (Future)
The platform is designed to support a dedicated customer identity domain.

*   **Identity**: Customers register and login from the storefront context.
*   **Scoping**: Customers can belong to multiple stores but maintain a unified identity.
*   **Separation**: Customer APIs (`/api/v1/storefront/*`) are intentionally separated from Merchant Admin APIs.
*   **No Onboarding**: Customers do not undergo merchant onboarding or store creation lifecycles.

## 24.12 Future Multi-Guard Strategy
While currently using a shared `users` table, the architecture is prepared for Guard separation (e.g., `merchants` vs `customers`) if scaling requirements dictate.

### Customer Guard Preparation
To prevent session contamination, the platform will transition to a dedicated `customer` guard.
*   **Isolation**: Merchant and customer sessions MUST become isolated systems.
*   **Tokens/Cookies**: Future separation of `merchant_session` and `customer_session` cookies.
*   **Social Auth**: Social login (Google, Apple) for customers will be scoped strictly to the `customer` context.

# 25. API Domain Architecture
The platform is organized into operational domains to ensure clear boundaries and prevent contamination.

## 25.1 Merchant Authentication Domain
*   **Purpose**: Operational identity, onboarding, store switching, and dashboard access.
*   **Routes**: `/api/v1/users/*`, `/api/v1/auth/*`.
*   **Characteristics**: Requires onboarding completion (for merchants), store-aware, and operational permissions.

## 25.2 Merchant Admin Domain
*   **Purpose**: Managing store resources (products, orders, staff).
*   **Routes**: `/api/v1/admin/*`.
*   **Characteristics**: Strictly tenant-aware, requires operational roles, and follows the `StorePolicy` enforcement.

## 25.3 Storefront Domain
*   **Purpose**: Public commerce APIs for browsing and checkout.
*   **Routes**: `/api/v1/storefront/*`.
*   **Characteristics**: Public-first, store-context-driven, and customer-safe. NO merchant onboarding logic.

## 25.4 Customer Identity Domain
*   **Purpose**: Customer registration, login, profile, and order history.
*   **Routes**: `/api/v1/storefront/account/*`.
*   **Characteristics**: Customer identity only. NEVER accesses merchant admin APIs. No onboarding.

## 25.5 Platform Administration Domain
*   **Purpose**: Super admin operations, platform analytics, and global governance.
*   **Routes**: `/api/v1/platform/*`.
*   **Characteristics**: Platform-wide scope. Not tenant or storefront scoped.

## 25.6 Feature Domains (Future)
*   **Billing Domain**: Subscription and payment processing.
*   **Notification Domain**: Multi-channel alerts (email, SMS, push).
*   **Audit Domain**: Tracking operational changes across the platform.
*   **Analytics Domain**: Global and tenant-specific data insights.

# 26. Authorization & RBAC Evolution
The platform uses a dynamic RBAC system where permissions are the source of truth.

*   **Roles**: Organizational abstractions (e.g., `STORE_ADMIN`).
*   **Permissions**: Operational capabilities (e.g., `product.create`).
*   **Resolution**: `PermissionResolver` dynamically generates capabilities based on actor and store context.
*   **Enforcement**: Policies consume permissions; controllers consume policies. Actions MUST NOT contain authorization logic.

# 27. API Response & Layering Philosophy
To maintain stability, the platform follows a strict layering doctrine.

*   **DTOs**: Mandatory for all data movement. Carry typed structure only.
*   **Actions**: Mandatory for all business logic. Atomic and testable.
*   **Services**: Contain reusable domain logic (e.g., Slug normalization).
*   **API Resources**: Responsible for transformation only. No business logic.
*   **Policies**: The single source of authorization enforcement.
*   **Middleware**: Responsible for request gating and context resolution only.

## 27.1 API Error Standards
All API errors MUST follow a predictable, machine-readable structure.

```json
{
  "message": "Human readable error message",
  "code": "DOMAIN_ERROR_001",
  "status": 403,
  "errors": []
}
```

*   **Rule**: Use explicit domain exceptions (e.g., `OnboardingIncompleteException`) to trigger these responses.
*   **Rule**: Codes MUST be stable and documented for frontend consumption.

# 28. Testing & Reliability Architecture
The platform enforces a multi-layered testing strategy to ensure operational integrity.

## 28.1 Testing Layers
*   **Unit Tests**: Isolated testing of Services, Resolvers (Actor/Permission/Domain), and Slug logic.
*   **Feature Tests**: End-to-end lifecycle testing (Auth, Onboarding, Store Switching, Policy Enforcement).
*   **Integration Tests**: Testing interactions between Middleware, Store Context, and RBAC resolution.

## 28.2 Reliability Rules
*   **Rule**: ALL critical business flows (e.g., Registration, Store Creation, Checkout) MUST have feature tests.
*   **Rule**: No silent failures. Use explicit Domain Exceptions for business logic violations.
*   **Rule**: Regression testing is mandatory for all architectural hardening fixes.

# 29. Observability & Security Events
The platform is designed for deep observability and proactive security monitoring.

## 29.1 Security Event Philosophy
Critical security events MUST be identifiable for future logging/alerting:
*   Failed merchant access attempts.
*   Onboarding bypass attempts.
*   Suspicious store switching patterns.
*   Permission denial spikes.

## 29.2 Observability Foundations
*   **Actor Tracing**: Every request is bound to an `ActorContext`.
*   **Domain Tracing**: Every request is bound to an `ApiDomain`.
*   **Audit Logging**: Actions should fire events for sensitive operational changes.

# 30. Event-Driven Architecture (Future)
To maintain decoupling, the platform will transition to an event-driven side-effect model.

*   **Pattern**: Actions execute business logic -> Fire Event (e.g., `StoreCreated`) -> Listeners handle side effects (e.g., Email, Analytics).

# 31. Platform Stabilization & Scale Readiness
This section documents the architectural hardening and scale-readiness audit findings and enforcement rules.

## 31.1 Audit Findings & Technical Debt
*   **Transactional Integrity**: Some actions (e.g., `RegisterUserAction`) currently lack explicit database transactions, risking partial persistence if events or post-creation logic fails.
*   **Event Gaps**: Core business facts (e.g., Store created, Email verified) are currently handled as procedural side-effects rather than formal domain events.
*   **Auditability**: The platform lacks a centralized audit trail for sensitive merchant and platform-level operations.
*   **N+1 Risks**: The `GetBootstrapAction` currently performs multiple individual queries for stores and permissions; this must be monitored as user complexity grows.

## 31.2 Domain Event Architecture
To ensure scalability and async readiness, business facts are dispatched as Domain Events.
*   **Rule**: Actions MAY dispatch events; Controllers NEVER dispatch events.
*   **Rule**: Events MUST represent completed business facts (past tense, e.g., `StoreCreated`).
*   **Rule**: Events MUST NOT expose Eloquent models; they must carry primitive types or DTOs for safe serialization.
*   **Location**: `app/Domain/Shared/Events`.

## 31.3 Audit & Activity Trail Philosophy
*   **Requirement**: Sensitive operations (auth, store creation, plan changes, user blocking) MUST be audited.
*   **Context**: Audit trails must include `actor_id`, `actor_context`, `store_id` (if applicable), `ip_address`, and `user_agent`.
*   **Security**: PII (passwords, specific personal details) MUST NEVER be logged in audit trails.

## 31.4 Transactional Doctrine
To prevent data corruption, all state-changing actions follow the transactional doctrine:
1.  **Open Transaction**: Ensure atomicity.
2.  **Validate**: Perform final state checks (e.g., slug availability).
3.  **Persist**: Write to database first.
4.  **Commit**: Ensure persistence before side effects.
5.  **Dispatch Events**: Side effects (emails, analytics) happen AFTER successful commit.

## 31.5 RBAC & Permission Scaling
*   **Convention**: Use dot-notation for permissions (`domain.action`, e.g., `product.create`).
*   **Resolution**: `PermissionResolver` is the single source of truth for dynamic capability generation.
*   **Hard Rule**: Policies are the ONLY enforcement layer. Middleware is for coarse gating (onboarding, domain separation).

## 31.6 Storefront & Customer Readiness
*   **Isolation**: Customer architecture MUST NEVER inherit merchant onboarding assumptions.
*   **Identity**: Multi-store customer accounts are unified by identity but scoped by storefront session.

---

## 🚨 FINAL HARD RULES (UPDATED)

```
NO QUERY MAY EXECUTE WITHOUT store_id CONSTRAINT.
EXCEPTION: super_admin global analytics ONLY.

NO PERMISSION CHECK MAY EXECUTE WITHOUT STORE SCOPE.
EXCEPTION: super_admin role check ONLY.

NO ADMIN ROUTE MAY EXIST WITHOUT {store} IN THE PATH.
EXCEPTION: super_admin global routes ONLY.

NO MERCHANT DASHBOARD ACCESS WITHOUT onboarding_step = COMPLETED.
EXCEPTION: super_admin access ONLY.

ALL STORE-SCOPED ADMIN ACTIONS MUST USE StorePolicy.

NO AUTHORIZATION LOGIC INSIDE ACTIONS.

NO BUSINESS LOGIC INSIDE CONTROLLERS, RESOURCES, OR MIDDLEWARE.

NO DEBUG OR TEST ROUTES IN api.php.
```

---

# Final Note

This architecture is **strict by design**.

If a feature does not fit:

* Do NOT break the rules
* Extend the architecture properly

Consistency > convenience.
```

---

## Cookie Authentication (Sanctum Stateful SPA)

All authentication uses Laravel Sanctum stateful sessions.
HttpOnly cookies — no tokens exposed to JavaScript.

### How It Works

1. Frontend: GET /sanctum/csrf-cookie
   → Sets XSRF-TOKEN cookie (JS-readable)
   → Sets ecommerce_session cookie (httpOnly)

2. Frontend: POST /api/v1/users/auth/login
   → Sends X-XSRF-TOKEN header
   → Sends withCredentials: true
   → Server calls Auth::attempt() + session written

3. All subsequent requests
   → Browser sends ecommerce_session cookie automatically
   → auth:sanctum validates session
   → No token management needed

### Configuration

```env
SESSION_DRIVER=cookie
SESSION_DOMAIN= (empty)
SESSION_SECURE_COOKIE=false (local) / true (production)
SESSION_COOKIE=ecommerce_session
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
```

```php
// config/session.php
'http_only' => true,
'same_site' => 'lax',
```

### Rules

- NEVER use auth:web on API routes
- ALWAYS use auth:sanctum on API routes
- HasApiTokens removed from User model (SPA cookie auth only)
- Auth::attempt() in LoginUserAction — never createToken()
- Auth::guard('web')->logout() + session invalidate in LogoutUserAction
- statefulApi() MUST be first in withMiddleware block
- SESSION_DOMAIN MUST be empty for localhost development
- same_site MUST be lax for cross-origin SPA
- NO custom Sanctum::getAccessTokenFromRequestUsing()
- NO ->cookie() token responses

---

# Appendix: Product-Variant Architecture

## Overview

This system uses a **variant-first ecommerce architecture** where:

| Entity | Role | Purchasable? | Has SKU? | Has Inventory? |
|--------|------|--------------|----------|----------------|
| **Product** | Abstract container (name, description, category, brand) | ❌ No | ❌ No (deprecated) | ❌ No |
| **ProductVariant** | Actual purchasable item | ✅ Yes | ✅ Yes | ✅ Yes |

## Core Principles

### 1. Variant-Owned Fields

The following fields belong **ONLY** to `product_variants`:

- `sku` - Stock Keeping Unit (unique identifier)
- `price` - Selling price
- `compare_at_price` - Original/compare price
- `cost_price` - Cost per item
- `quantity` - Stock/inventory level
- `low_stock_threshold` - Alert threshold
- `track_inventory` - Whether to track stock
- `manufacture_date` - Batch manufacture date
- `expiry_date` - Batch expiry date
- `batch_number` - Batch identifier
- `weight` - Product weight
- `weight_unit` - Weight unit

### 2. Product-Owned Fields

The following fields belong to `products`:

- `category_id` - Product categorization
- `brand_id` - Product brand
- `store_id` - Multi-tenant store ownership
- `product_variant_id` - Reference to **default/primary variant**
- `is_active` - Product visibility status
- `is_featured` - Featured product flag
- `sort_order` - Display order

### 3. Default Variant Strategy

Every product MUST have at least one variant. The `product_variant_id` field designates the **default/primary variant**.

```php
// Getting a product's primary variant
$product->primaryVariant(); // Returns ProductVariant|null

// Getting SKU (backward compatibility accessor)
$product->sku; // Returns primary variant's SKU or null
```

### 4. Inventory Architecture

**Stock belongs ONLY to variants:**

```php
// CORRECT: Check variant stock
$variant->quantity;           // Current stock
$variant->decrement('quantity', $amount);  // Reduce stock
$variant->increment('quantity', $amount);    // Add stock

// WRONG: Never check product-level stock
$product->quantity; // This field does not exist on products
```

**Order items reference variants:**

```php
// OrderItem captures variant details at order time
$orderItem->product_variant_id;  // Reference to variant
$orderItem->sku;                 // SKU snapshot (from variant)
$orderItem->unit_price;          // Price snapshot (from variant)
```

### 5. Cart Architecture

Cart items reference **variants**, not products:

```php
// CartItem model
'product_variant_id' => 'required|exists:product_variants,id',

// Add to cart DTO
public int $productVariantId,  // Not product_id
```

### 6. API Response Structure

**Product responses include variants as nested collection:**

```json
{
  "id": 1,
  "name": "T-Shirt",
  "category": "Clothing",
  "variants": [
    {
      "id": 101,
      "sku": "TSHIRT-RED-L",
      "price": 29.99,
      "stock": 50,
      "attributes": [
        {"name": "Color", "value": "Red"},
        {"name": "Size", "value": "L"}
      ]
    }
  ]
}
```

**Order item responses include variant snapshot:**

```json
{
  "product_variant_id": 101,
  "product_name": "T-Shirt",
  "sku": "TSHIRT-RED-L",
  "unit_price": 29.99,
  "quantity": 2
}
```

### 7. Search by SKU

SKU search queries the **variants** table, not products:

```php
// Search repository - CORRECT
->orWhereHas('variants', function ($sq) use ($query) {
    $sq->where('sku', 'LIKE', "%{$query}%");
})
```

### 8. Backward Compatibility

For legacy code expecting `$product->sku`:

```php
// Product model provides computed accessor
public function getSkuAttribute(): ?string
{
    return $this->primaryVariant()?->sku;
}
```

**Note:** This accessor is deprecated. New code should use `$product->primaryVariant()->sku`.

## Migration Status

### Completed
- ✅ Variant-first architecture implemented
- ✅ Inventory tracked on variants
- ✅ Order items reference variants
- ✅ Cart uses variant IDs
- ✅ Product creation requires at least one variant

### In Progress
- 🔄 SKU uniqueness validation per store
- 🔄 Complete variant update logic in admin

### Future
- ⏳ Remove `products.sku` column (after frontend migration)

## Anti-Patterns to Avoid

### ❌ Never Do This

```php
// Wrong: Treating product as purchasable entity
$product->sku;
$product->price;
$product->quantity;

// Wrong: Creating products without variants
Product::create([...]); // Without creating a variant

// Wrong: Flattening variant fields onto product
[
    'id' => $product->id,
    'sku' => $variant->sku,  // Don't flatten - nest under variants
    'price' => $variant->price,
]
```

### ✅ Always Do This

```php
// Correct: Using primary variant for display
$product->primaryVariant()?->sku;
$product->primaryVariant()?->price;

// Correct: Auto-creating default variant
if (empty($variants)) {
    $product->variants()->create(['sku' => ..., 'price' => ...]);
}
$product->update(['product_variant_id' => $firstVariant->id]);

// Correct: Nesting variant data
[
    'id' => $product->id,
    'name' => $product->name,
    'variants' => $variants->map(fn($v) => [
        'id' => $v->id,
        'sku' => $v->sku,
        'price' => $v->price,
    ]),
]
```

## Helper Methods

### Product Model

```php
// Get primary/default variant
$product->primaryVariant(): ?ProductVariant;

// Get display variant (alias for primaryVariant)
$product->display_variant: ?ProductVariant;

// Backward compatibility SKU accessor (deprecated)
$product->sku: ?string;
```

### ProductVariant Model

```php
// Standard relationships
$variant->product(): BelongsTo;
$variant->images(): MorphMany;
$variant->attributeValues(): BelongsToMany;

// Primary image helper
$variant->primary_image: ?Image;
```