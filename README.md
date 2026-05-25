# LaraTenant Backend

A production-grade, API-first multi-tenant commerce platform built with Laravel 11.

## 🚀 Overview

This platform provides a robust infrastructure for multi-tenant e-commerce and CMS management. It features a strict separation between commerce domains (store-scoped) and platform-level CMS content.

### Key Domains
- **Commerce**: Products, Orders, Cart, Payments (Store-scoped)
- **CMS Blog**: Store-scoped blog system with JSON-based localization.
- **CMS Documentation**: Platform-level documentation system.
- **Marketing CMS**: Platform-level landing page management.
- **Admin**: Multi-tenant management and platform administration.

## 🏗 Architecture

This project follows a strict **Action-DTO-Repository** pattern.

- **Actions**: Single-responsibility business logic.
- **DTOs**: Strictly typed data transfer between layers.
- **Repositories**: Isolated database access with mandatory scoping.
- **Resources**: Standardized API response transformation.

### Localization & SEO
- **Unified SEO Contract**: All public resources deliver a consistent SEO payload compatible with Next.js `generateMetadata()`.
- **JSON Localized Maps**: Standardized localization strategy using JSON columns in the database for optimal performance and flexibility.

## 🛠 Tech Stack

- **Framework**: Laravel 11 (PHP 8.3)
- **Database**: MySQL 8.0 / SQLite (Testing)
- **Auth**: Laravel Sanctum (Token-based)
- **Permissions**: Spatie Permission (Team-scoped)
- **Testing**: PHPUnit

## 🚦 Getting Started

### Prerequisites
- PHP 8.3+
- Composer
- MySQL 8.0+

### Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env` and configure your database.
4. Run migrations and seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```
5. Start the server:
   ```bash
   php artisan serve
   ```

## 🧪 Testing

Run the full test suite:
```bash
php artisan test
```

Wave 3A readiness artifact:
```bash
php artisan architecture:wave3a-readiness-report --output=storage/app/testing/wave3a-readiness-report.json
```

Wave 3B readiness artifact:
```bash
php artisan architecture:wave3b-guard-readiness-report --output=storage/app/testing/wave3b-guard-readiness-report.json
```

Wave 3C validation artifact:
```bash
php artisan architecture:wave3c-guard-split-validation-report --output=storage/app/testing/wave3c-guard-split-validation-report.json
```

## 🔐 Wave 3A / 3B Identity & Guard Preparation

The backend now includes additive identity and session/guard preparation layers that make actor and future guard boundaries explicit without changing auth authority.

Implemented through Wave 3A / 3B / 3C:

- explicit `IdentityContext` resolution for `merchant`, `customer`, and `super_admin`
- merchant/customer route-domain ownership metadata
- additive customer namespace at `/api/v1/storefront/account/*`
- merchant-only onboarding applicability isolation
- identity telemetry and session-boundary preparation metadata
- explicit session ownership modeling
- observe-only merchant/customer guard shadow resolvers
- contamination detection telemetry
- logout ownership tracing and CSRF preparation headers
- additive frontend session metadata
- minimal customer-safe storefront bootstrap
- non-authoritative guard split simulation engine
- concurrent-session, csrf, and logout readiness validation
- split-readiness scoring and operational risk analysis

Still intentionally unchanged:

- shared `users` table
- shared Sanctum session authority
- shared session cookie authority
- merchant auth route authority
- checkout auth model
- active guard/session/cookie split

## 📄 Documentation

Detailed project documentation is available in the `docs/` directory:
- [docs/README.md](./docs/README.md) - Documentation index and organization map.
- [ARCHITECTURE.md](./docs/ARCHITECTURE.md) - Core project rules.
- [AUTH_ROUTING.md](./docs/AUTH_ROUTING.md) - Wave 3A identity-context and route-ownership doctrine.
- [CMS_MARKETING_ARCHITECTURE.md](./docs/CMS_MARKETING_ARCHITECTURE.md) - CMS-specific logic.
- [EXECUTION_GOVERNANCE.md](./docs/EXECUTION_GOVERNANCE.md) - Governance and rollout rules.
- [OBSERVABILITY.md](./docs/OBSERVABILITY.md) - Observability and telemetry guidance.
- [exception-system.md](./docs/exception-system.md) - Error handling standards.
