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

## 📄 Documentation

Detailed architectural documentation is available in the `docs/` directory:
- [ARCHITECTURE.md](./docs/ARCHITECTURE.md) - Core project rules.
- [CMS_MARKETING_ARCHITECTURE.md](./docs/CMS_MARKETING_ARCHITECTURE.md) - CMS-specific logic.
- [exception-system.md](./docs/exception-system.md) - Error handling standards.
