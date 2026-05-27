# Controller Deprecation Map

This map tracks the migration from legacy controllers to their context-based canonical replacements.

| Legacy Controller Namespace | Canonical Context Namespace | Status |
| :--- | :--- | :--- |
| `App\Http\Controllers\Api\Auth` | `App\Http\Controllers\Api\Merchant` | **REMOVED** |
| `App\Http\Controllers\Api\Admin` | `App\Http\Controllers\Api\Merchant` or `App\Http\Controllers\Api\Platform` | **REMOVED** |
| `App\Http\Controllers\Api\Product` | `App\Http\Controllers\Api\Storefront` | **REMOVED** |
| `App\Http\Controllers\Api\Order` | `App\Http\Controllers\Api\Storefront` | **REMOVED** |
| `App\Http\Controllers\Api\Cart` | `App\Http\Controllers\Api\Storefront` | **REMOVED** |
| `App\Http\Controllers\Api\Address` | `App\Http\Controllers\Api\Storefront` | **REMOVED** |
| `App\Http\Controllers\Api\Search` | `App\Http\Controllers\Api\Storefront` | **REMOVED** |
| `App\Http\Controllers\Api\Homepage` | `App\Http\Controllers\Api\Storefront" | **REMOVED** |
| `App\Http\Controllers\Api\Store` | `App\Http\Controllers\Api\Merchant` | **REMOVED** |
| `App\Http\Controllers\Api\V1` | `App\Http\Controllers\Api\Merchant` | **REMOVED** |

## Deprecation Rules

1.  **Do Not Modify**: Legacy controllers should not receive new features. Fixes should be applied to canonical replacements first.
2.  **Telemetry Hits**: Any request to a legacy controller is logged as a warning.
3.  **Removal Target**: Legacy controllers are scheduled for removal in `v2.0.0`.

## Mapping Details

### Auth -> Merchant
- `AuthController` -> `Merchant\AuthController`
- `SessionController` -> `Merchant\SessionController`
- `ProfileController` -> `Merchant\ProfileController`

### Admin -> Platform/Merchant
- `AdminLeadController` -> `Platform\AdminLeadController`
- `AdminProductController` -> `Merchant\AdminProductController`
- `AdminOrderController` -> `Merchant\AdminOrderController`

### Storefront -> Storefront
- `ProductController` -> `Storefront\ProductController`
- `CartController` -> `Storefront\CartController`
- `CheckoutController` -> `Storefront\CheckoutController`
