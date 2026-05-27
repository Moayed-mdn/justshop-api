# Identity Boundaries

LaraTenant maintains strict isolation between different user types and application contexts to ensure security and multitenant integrity.

## Identity Types

### Merchant Identity (`merchant` guard)
- Represents store owners, store staff, and platform admins.
- Stored in the `users` table.
- Isolated by `tenant_id` for store-specific operations.
- Platform admins are distinguished by roles/permissions within this identity type.

### Customer Identity (`customer` guard)
- Represents shoppers on individual stores.
- Stored in the `customers` table.
- Fully isolated from merchant identities. A merchant cannot log in as a customer using their merchant credentials and vice-versa.

## Boundary Enforcement

### Middleware-Based Enforcement
The `identity.route` middleware is the primary mechanism for enforcing boundaries. It takes three parameters:
1. `context_name`: The logical name of the context (e.g., `merchant_admin`).
2. `identity_type`: The expected guard/identity type (`merchant`, `customer`, or `platform`).
3. `mode`: Either `enforce` (strict check) or `observe` (context awareness without strict block).

### Platform vs. Merchant Separation
Even though Platform and Merchant users may share the same database table, they are logically separated:
- **Platform routes** require `platform.authority` middleware, which checks for `SUPER_ADMIN` status.
- **Merchant routes** use `merchant` guard and are scoped to the `tenant_id` of the store they are managing.

### Storefront Isolation
Storefront routes are scoped to a specific store via the `store.context` middleware. This ensures that a shopper on Store A cannot interact with the cart or orders of Store B.

## Risks of Boundary Leakage
1. **Implicit Authority**: Never allow a route to inherit merchant authority implicitly if it's a platform route.
2. **Shared Controllers**: Avoid using the same controller methods for both merchant and platform contexts unless they are purely read-only and context-agnostic.
3. **Session Leakage**: Different contexts use different session/cookie configurations where possible to prevent cross-context hijacking.

## Hardened Boundaries (Phase 2 Refactor)

The following improvements were made to harden identity boundaries:

### 1. Guard Alignment
Every context now has a strictly defined guard:
- **Platform**: `merchant` (with `SUPER_ADMIN` check)
- **Merchant**: `merchant`
- **Storefront**: `customer` (observed) or `guest`
- **Customer**: `customer` (enforced)
- **Support**: `merchant` (with `support.authority` check)

### 2. Explicit Identity Assertions
Controllers now use explicit identity assertions instead of assuming a user type based on the request path.
- `Merchant` controllers use `$request->user()` and expect a `User` model.
- `Customer` controllers use `Auth::guard('customer')->user()` and expect a `Customer` model.

### 3. Isolated Middleware Groups
Middleware groups in `bootstrap/app.php` and `routes/api.php` are now context-aware. The `identity.route` middleware ensures that the incoming authentication token matches the required identity type for the context.
