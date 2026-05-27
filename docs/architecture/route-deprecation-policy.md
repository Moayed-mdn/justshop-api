# Route Deprecation Policy

This policy defines how the LaraTenant API handles the deprecation and removal of legacy endpoints.

## Objectives
- Provide a predictable timeline for API changes.
- Minimize disruption to frontend and mobile consumers.
- Maintain a clean and maintainable backend architecture.

## Deprecation Lifecycle

### 1. Identification
A route is identified for deprecation when a new, context-based canonical route is established.

### 2. Annotation
- The legacy route is wrapped with the `api.deprecated` middleware.
- The corresponding controller method is marked with the `@deprecated` PHPDoc tag.
- The `X-API-Deprecated: true` header is added to all responses from the route.
- The `X-API-Suggested-New-Route` header provides the migration target.

### 3. Communication
- Frontend teams are notified via the **Frontend Route Migration Guide**.
- Telemetry is collected to monitor usage of deprecated routes.

### 4. Sunset
- After the designated deprecation period (typically 6 months), the route is removed.
- Removal must be preceded by a "Brownout" period where the route is intentionally disabled for short intervals to catch any remaining consumers.

## Telemetry and Monitoring
The `HandleDeprecatedRoute` middleware logs all legacy route access, including:
- Path and Method
- Suggested New Route
- IP Address
- User ID (if authenticated)

Architecture teams review these logs monthly to identify high-usage legacy endpoints that require targeted migration support.

## Backward Compatibility Guarantees
During the **Dual Support** phase:
- Behavior of legacy routes must remain identical to their canonical counterparts.
- Performance characteristics must not degrade significantly.
- Security patches will be applied to both legacy and canonical controllers.
