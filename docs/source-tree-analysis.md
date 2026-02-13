# UnoPim - Source Tree Analysis

## Overview

UnoPim follows a **modular monolith** architecture built on Laravel, with all business logic organized into 19 self-contained Webkul packages under `packages/Webkul/`. The root Laravel application provides bootstrapping, configuration, and shared infrastructure.

---

## Root Directory Structure

```
unopim/
├── app/                          # Laravel application scaffold
│   ├── Console/Kernel.php        # Console command registration
│   ├── Exceptions/Handler.php    # Global exception handling
│   ├── Http/
│   │   ├── Kernel.php            # HTTP middleware stack
│   │   └── Middleware/           # Application middleware
│   └── Providers/                # Service providers
│       ├── AppServiceProvider.php
│       ├── AuthServiceProvider.php
│       ├── BroadcastServiceProvider.php
│       ├── EventServiceProvider.php
│       └── RouteServiceProvider.php
│
├── bin/                          # Executable scripts
│   └── unopim-install.sh         # Installation script
│
├── bootstrap/                    # Application bootstrapping
│   ├── app.php                   # Application factory
│   └── cache/                    # Compiled services/packages cache
│
├── config/                       # [30 config files] Application configuration
│   ├── app.php                   # Core app settings (name, URL, timezone, locale)
│   ├── auth.php                  # Guards: admin (session), api (passport)
│   ├── concord.php               # Module system (9 core Webkul modules)
│   ├── database.php              # MySQL/PostgreSQL/SQLite connections
│   ├── elasticsearch.php         # Elasticsearch connection and indexing
│   ├── filesystems.php           # Storage disks (local, public, S3)
│   ├── horizon.php               # Queue dashboard configuration
│   ├── mail.php                  # SMTP/Sendmail/SES configuration
│   ├── openai.php                # OpenAI API integration
│   ├── products.php              # Product copy/relation settings
│   ├── queue.php                 # Queue drivers and connections
│   ├── sanctum.php               # API token authentication
│   ├── session.php               # Session driver and lifetime
│   ├── themes.php                # Theme configuration
│   ├── unopim-vite.php           # Vite asset bundling per package
│   └── ...                       # (15 more standard Laravel configs)
│
├── database/                     # Database infrastructure
│   ├── factories/UserFactory.php # Test data factories
│   ├── migrations/               # Schema migrations (root-level)
│   └── seeders/DatabaseSeeder.php
│
├── dockerfiles/                  # Docker configuration
│   ├── web.Dockerfile            # Web server container
│   ├── web-entrypoint.sh         # Web startup script
│   ├── q.Dockerfile              # Queue worker container
│   └── q-entrypoint.sh           # Queue startup script
│
├── lang/                         # [33 locale dirs] Root language files
│   ├── ar_AE/                    # Arabic
│   ├── en_US/                    # English (US) - default
│   ├── fr_FR/                    # French
│   └── ...                       # (30 more locales)
│
├── packages/                     # ★ CORE: Webkul package modules
│   └── Webkul/                   # [19 packages] - see detailed breakdown below
│
├── public/                       # Web-accessible root
│   ├── build/                    # Vite compiled assets
│   │   ├── manifest.json         # Asset manifest
│   │   └── assets/               # JS/CSS bundles
│   ├── index.php                 # Application entry point
│   └── themes/                   # Theme assets
│
├── resources/                    # Root resource files
│   ├── css/app.css               # Root CSS (minimal)
│   ├── js/app.js                 # Root JS entry (minimal)
│   └── views/                    # (Empty - views in packages)
│
├── routes/                       # Root route definitions
│   ├── api.php                   # (Empty placeholder)
│   ├── breadcrumbs.php           # Breadcrumb definitions
│   ├── channels.php              # Broadcast channels
│   ├── console.php               # Artisan commands
│   └── web.php                   # (Empty - routes in packages)
│
├── storage/                      # Application storage
│   ├── app/public/               # User-uploaded files
│   ├── framework/                # Cache, sessions, views
│   └── logs/                     # Application logs
│
├── tests/                        # Test infrastructure
│   ├── CreatesApplication.php    # Test bootstrap
│   ├── Pest.php                  # Pest test configuration
│   ├── TestCase.php              # Base test case
│   └── e2e-pw/                   # Playwright E2E tests
│       ├── playwright.config.js  # Playwright configuration
│       ├── global-setup.js       # Auth setup for tests
│       └── tests/                # [23 spec files]
│           ├── 01-catalog/       # Catalog feature tests
│           ├── 02-configuration/ # Config tests
│           ├── 03-dashboard/     # Dashboard tests
│           ├── 04-datatransfer/  # Import/export tests
│           ├── 05-settings/      # Settings tests
│           ├── 06-product-completeness/
│           └── 07-ui-loginpage/  # Login page tests
│
├── vendor/                       # Composer dependencies
├── .github/workflows/            # CI/CD pipelines
│   ├── linting_tests.yml         # Laravel Pint linting
│   ├── pest_tests.yml            # Pest PHP tests
│   └── playwright_test.yml       # E2E Playwright tests
│
├── artisan                       # Laravel CLI entry point
├── composer.json                 # PHP dependencies
├── package.json                  # Node.js dependencies
├── docker-compose.yml            # Docker orchestration (4 services)
├── phpunit.xml                   # Test suite configuration (9 suites)
├── vite.config.js                # Vite build configuration
├── pint.json                     # Code style rules
└── upgrade.sh                    # Automated upgrade script
```

---

## Package Architecture (packages/Webkul/)

### Package Organization Pattern

Each Webkul package follows a consistent Laravel package structure:

```
packages/Webkul/{PackageName}/
├── composer.json                  # (Some packages)
├── src/
│   ├── Config/                    # Package configuration files
│   ├── Console/                   # Artisan commands
│   ├── Contracts/                 # Interfaces
│   ├── Database/
│   │   ├── Factories/             # Model factories
│   │   ├── Migrations/            # Database migrations
│   │   └── Seeders/               # Data seeders
│   ├── Enums/                     # Enumerations
│   ├── Facades/                   # Facade classes
│   ├── Helpers/                   # Helper functions
│   ├── Http/
│   │   ├── Controllers/           # HTTP controllers
│   │   ├── Middleware/            # Package middleware
│   │   └── Requests/              # Form requests
│   ├── Jobs/                      # Queue jobs
│   ├── Listeners/                 # Event listeners
│   ├── Mail/                      # Mailable classes
│   ├── Models/                    # Eloquent models
│   ├── Observers/                 # Model observers
│   ├── Providers/                 # Service providers
│   │   ├── {Package}ServiceProvider.php
│   │   ├── ModuleServiceProvider.php
│   │   └── EventServiceProvider.php
│   ├── Repositories/              # Data access layer
│   ├── Resources/
│   │   ├── lang/                  # Package translations
│   │   └── views/                 # Blade templates
│   ├── Routes/                    # Package routes
│   ├── Rules/                     # Validation rules
│   ├── Services/                  # Business logic services
│   ├── Traits/                    # Shared traits
│   └── Validators/                # Custom validators
└── tests/                         # Package tests
    ├── {Package}TestCase.php
    ├── Feature/                   # Feature tests
    └── Unit/                      # Unit tests
```

---

### Detailed Package Breakdown

#### Admin (Primary UI Package)
```
Admin/ ★ Largest package - Contains the entire admin panel UI
├── package.json, postcss.config.js, tailwind.config.js, vite.config.js
├── src/
│   ├── Config/               # acl.php (80+ permissions), menu.php, system.php
│   ├── DataGrids/            # DataGrid definitions for all entities
│   ├── Exports/              # Export formatters
│   ├── Helpers/              # Admin helper utilities
│   ├── Http/
│   │   ├── Controllers/      # 20+ controllers (Catalog, Settings, Config, etc.)
│   │   ├── Middleware/        # Admin middleware
│   │   └── Requests/         # Form request validation
│   ├── Listeners/            # Event listeners
│   ├── Mail/                 # Email templates
│   ├── Resources/
│   │   ├── assets/           # CSS, JS (Vue 3 + Tailwind), fonts, images
│   │   ├── lang/ [33 dirs]   # Complete translations
│   │   └── views/ [166 templates]
│   │       ├── catalog/      # Products, Attributes, Categories, Families
│   │       ├── components/   # 95+ reusable UI components
│   │       ├── configuration/# System configuration
│   │       ├── dashboard/    # Admin dashboard
│   │       └── settings/     # Channels, Users, Roles, Data Transfer
│   ├── Routes/ [7 files]     # Auth, Catalog, Settings, Config, Notification, REST
│   ├── Traits/               # Shared admin traits
│   └── Validations/          # Custom validation logic
└── tests/Feature/            # Admin feature + ACL tests
```

#### AdminApi (REST API Package)
```
AdminApi/
├── src/
│   ├── Config/               # api-acl.php, api.php, menu.php
│   ├── Database/Migrations/  # [5 files] OAuth clients, API keys
│   ├── Http/
│   │   ├── Controllers/      # API controllers (Catalog V1, Settings V1)
│   │   └── Middleware/        # API auth middleware (ScopeMiddleware)
│   ├── Models/               # Apikey, Client models
│   ├── Routes/               # admin-api.php, V1/catalog-routes.php, V1/settings-routes.php
│   └── Traits/               # API helper traits
└── tests/                    # API feature + ACL tests
```

#### Product (Core Domain Package)
```
Product/
├── src/
│   ├── Builders/             # Query builders
│   ├── Config/               # product_types.php
│   ├── Database/Migrations/  # [3 files] products, super_attributes, relations
│   ├── Factories/            # Product type factories
│   ├── Filter/               # Product filtering
│   ├── Helpers/              # Product helper utilities
│   ├── Models/               # Product, ProductImage models
│   ├── Normalizer/           # Data normalizers
│   ├── Observers/            # Model observers
│   ├── Repositories/         # ProductRepository
│   ├── Services/             # Business logic
│   ├── Type/                 # Product type handlers (Simple, Configurable)
│   ├── Validator/            # Product validation
│   └── ValueSetter.php       # Attribute value management
```

#### Attribute (Attribute System)
```
Attribute/
├── src/
│   ├── Config/               # attribute_types.php (12 types)
│   ├── Database/Migrations/  # [15 files] ★ Most migrations
│   ├── Enums/                # Attribute type enums
│   ├── Models/               # Attribute, AttributeOption, AttributeFamily, etc.
│   ├── Repositories/         # Data access
│   ├── Rules/                # Validation rules
│   └── Services/             # Attribute services
```

#### Category (Category System)
```
Category/
├── src/
│   ├── Config/               # category_field_types.php
│   ├── Database/Migrations/  # [8 files] categories, fields, options
│   ├── Facades/              # CategoryHelper facade
│   ├── Models/               # Category (Nested Set), CategoryField
│   ├── Observers/            # Category observers
│   ├── Repositories/         # CategoryRepository
│   ├── Services/             # Category services
│   └── Validator/            # Category validation
```

#### Core (Shared Infrastructure)
```
Core/
├── src/
│   ├── Config/               # concord.php, repository.php, visitor.php
│   ├── Console/              # Artisan commands
│   ├── Database/Migrations/  # [5 files] locales, currencies, channels, config
│   ├── Eloquent/             # Base model classes
│   ├── Helpers/Database/     # Database grammar helpers (MySQL, PostgreSQL, SQLite)
│   ├── Http/Middleware/       # CheckForMaintenanceMode, SecureHeaders
│   ├── Models/               # Locale, Currency, Channel, CoreConfig
│   ├── Repositories/         # Core repositories
│   └── Traits/               # TranslatableModel, HistoryTrait
```

#### DataTransfer (Import/Export Engine)
```
DataTransfer/
├── src/
│   ├── Buffer/               # Data buffering
│   ├── Config/               # importers.php, exporters.php, actions.php
│   ├── Console/              # Import/export CLI commands
│   ├── Cursor/               # Cursor-based iteration
│   ├── Database/Migrations/  # [7 files] job_instances, job_track, batches
│   ├── Helpers/              # Import/export helpers
│   ├── Jobs/                 # Queue jobs for processing
│   ├── Models/               # JobInstances, JobTrack, JobTrackBatch
│   ├── Queue/                # Custom queue handling
│   ├── Services/             # Import/export services
│   └── Validators/           # Data validation
```

#### Other Notable Packages
```
Completeness/    # Product completeness scoring per channel/locale
ElasticSearch/   # Elasticsearch indexing, querying, filtering
HistoryControl/  # Version control, change tracking, audit
Installer/       # GUI installation wizard (6 blade views)
MagicAI/         # OpenAI integration for content generation
Notification/    # In-app + email notifications
User/            # Authentication, roles, permissions (Bouncer)
Webhook/         # Event webhook dispatching
Theme/           # Theme management, view rendering
FPC/             # Full Page Cache events
DataGrid/        # Reusable datagrid component
DebugBar/        # Development debugging
Inventory/       # Inventory management
```

---

## Critical Directories Summary

| Directory | Purpose | Entry Points |
|-----------|---------|-------------|
| `packages/Webkul/Admin/src/Routes/` | All admin web routes | `web.php`, `catalog-routes.php`, `settings-routes.php` |
| `packages/Webkul/AdminApi/src/Routes/` | REST API routes | `admin-api.php`, `V1/*.php` |
| `packages/Webkul/Admin/src/Resources/views/` | 166 Blade templates | Layout, catalog, settings, components |
| `packages/Webkul/Admin/src/Resources/assets/` | Frontend (Vue + CSS) | `js/app.js`, `css/app.css` |
| `packages/Webkul/*/src/Models/` | Eloquent models | All domain models |
| `packages/Webkul/*/src/Repositories/` | Data access layer | Repository pattern |
| `packages/Webkul/*/src/Database/Migrations/` | Schema migrations | ~61 migration files total |
| `config/` | Application configuration | 30 config files |
| `tests/` | Test infrastructure | `Pest.php`, 9 test suites |
| `tests/e2e-pw/` | Playwright E2E tests | 23 spec files |

---

## Migration Count by Package

| Package | Migrations |
|---------|-----------|
| Attribute | 15 |
| Category | 8 |
| DataTransfer | 7 |
| Core | 5 |
| AdminApi | 5 |
| HistoryControl | 4 |
| Completeness | 3 |
| MagicAI | 3 |
| Notification | 3 |
| Product | 3 |
| User | 3 |
| Webhook | 2 |
| **Total** | **~61** |
