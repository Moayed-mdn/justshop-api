# URL Generation Policy

This policy defines the rules for generating URLs and referencing routes within the LaraTenant platform.

## 1. Use Route Helpers & FrontendUrlBuilder
**NEVER** hardcode URLs in controllers, notifications, or mailables.

- For **Backend API** routes: Always use the `route()` helper with canonical named routes.
- For **Frontend** links: Always use `App\Support\System\FrontendUrlBuilder`.

### FrontendUrlBuilder Examples:
- **Simple Link**: `FrontendUrlBuilder::build('/dashboard')`
- **Link with Query**: `FrontendUrlBuilder::build('/search', ['q' => 'shoes'])`
- **Signed Frontend Link**: 
  ```php
  $backendUrl = URL::temporarySignedRoute('merchant.auth.verification.verify', ...);
  $frontendUrl = FrontendUrlBuilder::buildSigned('/verify-email', $backendUrl);
  ```

## 2. Canonical Route Names
Always use the canonical context-based route names:
- `platform.*`
- `merchant.*`
- `storefront.*`
- `customer.*`
- `support.*`
- `public.*`

## 3. Context-Aware URL Generation
A context should generally only generate URLs for its own domain. If a context needs to link to another (e.g., a merchant notification linking to a storefront product), it must be done explicitly and audited for security.

### Cross-Context Safety Rules:
- **Customer -> Merchant**: Strictly forbidden in automated notifications.
- **Merchant -> Storefront**: Allowed for product/store previews.
- **Platform -> Merchant**: Allowed for admin dashboard links.

## 4. Signed URLs
For sensitive actions (email verification, password resets, impersonation), always use `URL::temporarySignedRoute()`.
- Ensure the route name matches the canonical context (e.g., `merchant.auth.verification.verify`).

## 5. Frontend URL Mapping
The backend provides canonical API endpoints. The frontend is responsible for mapping these to its internal navigation structure. Avoid returning full frontend URLs from the API; return route names or relative paths instead.

## 6. Forbidden Patterns
The following patterns are forbidden and will be flagged during architectural audits:
- Hardcoded `/api/v1/admin/`
- Hardcoded `/api/v1/users/`
- References to legacy route names (`v1.admin.*`, `v1.users.*`)
