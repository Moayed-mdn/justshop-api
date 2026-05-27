# Controller Ownership Rules

This document outlines the rules and guidelines for controller ownership within the LaraTenant API.

## Core Principle

**One Controller, One Context.**

A controller must belong to exactly one application context. This ensures that:
1.  **Identity Isolation**: Controllers only handle requests from the intended actor.
2.  **Authorization Clarity**: Middleware and policies can be applied with full confidence.
3.  **Audit Integrity**: Logging and telemetry accurately reflect which part of the system is being used.

## Context Namespaces

| Context | Namespace |
| :--- | :--- |
| **Platform** | `App\Http\Controllers\Api\Platform` |
| **Merchant** | `App\Http\Controllers\Api\Merchant` |
| **Storefront** | `App\Http\Controllers\Api\Storefront` |
| **Customer** | `App\Http\Controllers\Api\Customer` |
| **Support** | `App\Http\Controllers\Api\Support` |
| **Public** | `App\Http\Controllers\Api\Public` |

## Sharing Logic Safely

If multiple contexts require similar functionality (e.g., retrieving a product), do **not** reuse the controller. Instead, use one of the following patterns:

### 1. Actions (Recommended)
Extract the core business logic into an Action class.
- `App\Actions\Product\GetProductAction`
- Both `Merchant\AdminProductController` and `Storefront\ProductController` can call this action, but they handle their own request validation and response formatting.

### 2. Services
For complex domain logic that spans multiple entities.
- `App\Services\Order\OrderTaxCalculator`

### 3. Traits
For shared controller helper methods (e.g., specific response formats).
- `App\Http\Controllers\Api\Concerns\HandlesStorefrontResponses`

## Refactoring Guidelines

When a controller is found to be shared across contexts:
1.  **Duplicate and Relocate**: Copy the controller to the appropriate context namespaces.
2.  **Update Namespaces**: Update the `namespace` and `use` statements.
3.  **Refine Authorization**: Audit the methods to ensure they use the correct guards and policies for the new context.
4.  **Update Routes**: Point the context-specific routes to the new controller.
5.  **Deprecate Old Controller**: If the old controller is still needed for legacy routes, mark it as `@deprecated`.

## Naming Conventions
Controllers should reflect their context and responsibility:
- `Merchant\AdminProductController` (Admin actions for products)
- `Storefront\ProductController` (Public actions for products)
- `Platform\PlatformStoreController` (SaaS level store management)
