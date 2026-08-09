# JustShop API

The backend API for **JustShop**, a multi-tenant commerce platform supporting independent merchant stores, customer storefronts and platform-wide administration.

Built with Laravel 12, the API owns business rules, authentication, authorization, tenant isolation, catalog data, orders, payments and administrative operations.

## Related Repositories

- [Platform Overview](https://github.com/Moayed-mdn/justshop-multitenant-commerce-platform)
- [Merchant Dashboard](https://github.com/Moayed-mdn/justshop-merchant-dashboard)
- [Storefront](https://github.com/Moayed-mdn/justshop-storefront)
- [Platform Dashboard](https://github.com/Moayed-mdn/justshop-platform-dashboard)

## Main Capabilities

- Multi-tenant store architecture
- Merchant and store onboarding
- Laravel Sanctum session authentication
- Actor-aware routes and authorization
- Tenant-scoped products, variants, categories, brands and tags
- Inventory, manufacture dates and expiry dates
- Media and hero-banner management
- Customer carts and orders
- Stripe payment integration
- Store and platform CMS
- Feature flags and audit logs
- Redis-backed queues
- REST documentation and GraphQL support

## Technology Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum and Socialite
- MySQL
- Redis / Predis
- Stripe PHP SDK
- Lighthouse GraphQL
- Scramble and Scalar
- Spatie Enum
- Spatie Laravel TypeScript Transformer
- PHPUnit

## Architecture

```text
Nuxt Storefront ───────────────┐
Next.js Merchant Dashboard ────┼──> Laravel API ──> MySQL
Next.js Platform Dashboard ────┘          │
                                          ├──> Redis / Queues
                                          ├──> Stripe
                                          └──> Media Storage
```

The API is the system of record. Tenant-owned operations must resolve an authorized store context before querying or modifying data.

## Local Setup

### Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Redis

### Installation

```bash
git clone https://github.com/Moayed-mdn/justshop-api.git
cd justshop-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Configure database, Redis, session, Sanctum, CORS and service credentials in `.env`.

Start the API:

```bash
php artisan serve --port=8000
```

Start a queue worker separately:

```bash
php artisan queue:work redis \
  --queue=store-bootstrap,default \
  --sleep=3 \
  --tries=3
```

## Authentication

The browser applications use Laravel Sanctum's stateful SPA authentication flow:

1. Request the CSRF cookie.
2. Submit credentials with the XSRF token.
3. Laravel creates or regenerates the authenticated session.
4. Protected requests carry the session cookie.
5. Logout invalidates the session and regenerates the CSRF token.

Authorization remains enforced by backend middleware, policies and tenant-aware services.

## Tenant Isolation

Tenant-owned resources should be loaded through an authorized store relationship.

Conceptually:

```php
$product = $store->products()->findOrFail($productId);
```

High-value tests include:

- A merchant cannot read another store's products.
- A merchant cannot update another store's orders.
- A customer cannot call merchant routes.
- A merchant cannot call platform routes.
- Nested resources cannot cross store boundaries.

## API Documentation

The project includes documentation tooling through Scramble and Scalar. Use the configured documentation route defined by the application.

GraphQL operations are available through Lighthouse where applicable.

## Testing

```bash
php artisan test
```

Or:

```bash
composer test
```

Code formatting check:

```bash
./vendor/bin/pint --test
```

## Useful Commands

```bash
php artisan optimize:clear
php artisan route:list
php artisan queue:restart
php artisan test
```

## Security

Never commit `.env`, database credentials, Stripe keys, cookies, CSRF tokens or authentication traces.

See the platform [security documentation](https://github.com/Moayed-mdn/justshop-multitenant-commerce-platform/blob/main/docs/security.md).

## Status

Active portfolio project.

Docker, GitHub Actions and automated deployments are planned for the next infrastructure phase.
