# UnoPim - Architecture Document

## Architecture Pattern

**Modular Monolith** with a Service-Oriented Package Architecture built on Laravel 10.x.

The application is a single deployable unit, but internally organized as 19 independent Webkul packages managed through Konekt Concord's module system. Each package owns its domain (models, migrations, routes, controllers, views) and communicates with other packages through well-defined contracts (interfaces) and Laravel's service container.

---

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │ Admin Panel   │  │ REST API     │  │ Installer            │  │
│  │ (Vue 3 +     │  │ (OAuth2)     │  │ (Blade + Vue)        │  │
│  │  Blade +     │  │ /v1/rest/*   │  │ /install             │  │
│  │  Tailwind)   │  │              │  │                      │  │
│  └──────┬───────┘  └──────┬───────┘  └──────────────────────┘  │
│         │                  │                                     │
└─────────┼──────────────────┼─────────────────────────────────────┘
          │                  │
┌─────────┼──────────────────┼─────────────────────────────────────┐
│         │    MIDDLEWARE LAYER                                     │
│  ┌──────┴───────┐  ┌──────┴───────┐                             │
│  │ Bouncer      │  │ Scope        │  SecureHeaders               │
│  │ (Session     │  │ Middleware   │  CheckForMaintenanceMode     │
│  │  Auth+RBAC)  │  │ (OAuth2+ACL)│  EncryptCookies, CSRF       │
│  └──────┬───────┘  └──────┬───────┘                             │
└─────────┼──────────────────┼─────────────────────────────────────┘
          │                  │
┌─────────┼──────────────────┼─────────────────────────────────────┐
│         │    APPLICATION LAYER (Webkul Packages)                 │
│  ┌──────┴──────────────────┴──────────────────────────────────┐  │
│  │                                                             │  │
│  │  ┌─────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────┐  │  │
│  │  │ Admin   │ │ AdminApi │ │ Installer│ │ Completeness  │  │  │
│  │  │ Package │ │ Package  │ │ Package  │ │ Package       │  │  │
│  │  └────┬────┘ └────┬─────┘ └──────────┘ └───────────────┘  │  │
│  │       │            │                                        │  │
│  │  ┌────┴────────────┴──────────────────────────────────┐    │  │
│  │  │              DOMAIN LAYER                           │    │  │
│  │  │  ┌─────────┐ ┌─────────┐ ┌──────────┐ ┌────────┐  │    │  │
│  │  │  │Product  │ │Attribute│ │ Category │ │  User  │  │    │  │
│  │  │  │Package  │ │Package  │ │ Package  │ │Package │  │    │  │
│  │  │  └─────────┘ └─────────┘ └──────────┘ └────────┘  │    │  │
│  │  │  ┌─────────┐ ┌─────────┐ ┌──────────┐ ┌────────┐  │    │  │
│  │  │  │DataTrans│ │MagicAI  │ │Notificat.│ │Webhook │  │    │  │
│  │  │  │Package  │ │Package  │ │ Package  │ │Package │  │    │  │
│  │  │  └─────────┘ └─────────┘ └──────────┘ └────────┘  │    │  │
│  │  └────────────────────────────────────────────────────┘    │  │
│  │                                                             │  │
│  │  ┌────────────────────────────────────────────────────┐    │  │
│  │  │            INFRASTRUCTURE LAYER                     │    │  │
│  │  │  ┌──────┐ ┌──────────┐ ┌─────────┐ ┌───────────┐  │    │  │
│  │  │  │ Core │ │ DataGrid │ │  Theme  │ │ History   │  │    │  │
│  │  │  │      │ │          │ │         │ │ Control   │  │    │  │
│  │  │  └──────┘ └──────────┘ └─────────┘ └───────────┘  │    │  │
│  │  │  ┌──────┐ ┌──────────┐ ┌─────────┐               │    │  │
│  │  │  │ FPC  │ │ElasticSr.│ │DebugBar │               │    │  │
│  │  │  └──────┘ └──────────┘ └─────────┘               │    │  │
│  │  └────────────────────────────────────────────────────┘    │  │
│  └─────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────────┘
          │
┌─────────┴────────────────────────────────────────────────────────┐
│                     DATA / EXTERNAL LAYER                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐    │
│  │ MySQL /  │  │  Redis   │  │Elastic   │  │  OpenAI API  │    │
│  │PostgreSQL│  │ (Queue/  │  │Search    │  │  (Magic AI)  │    │
│  │          │  │  Cache)  │  │          │  │              │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────┘    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                      │
│  │ Storage  │  │  Mail    │  │ Pusher   │                      │
│  │ (Local/  │  │ (SMTP/   │  │(Websocket│                      │
│  │  S3)     │  │  SES)    │  │  Push)   │                      │
│  └──────────┘  └──────────┘  └──────────┘                      │
└──────────────────────────────────────────────────────────────────┘
```

---

## Layer Descriptions

### 1. Client Layer
- **Admin Panel**: Server-rendered Blade templates enhanced with Vue.js 3 Islands for interactivity. Tailwind CSS for styling with dark mode support.
- **REST API**: Versioned RESTful API (v1) using OAuth2 (Laravel Passport) for machine-to-machine integration.
- **Installer**: Self-contained installation wizard with its own frontend build.

### 2. Middleware Layer
- **Bouncer** (Web): Session-based authentication + RBAC authorization with 80+ permissions
- **ScopeMiddleware** (API): OAuth2 token validation + ACL-based permission checking
- **SecureHeaders**: Security response headers
- **Standard Laravel**: CSRF, encrypted cookies, maintenance mode

### 3. Application Layer
Controllers and route handlers organized by package. Admin package handles all web UI routes, AdminApi handles REST endpoints. Each controller delegates to repositories/services.

### 4. Domain Layer
Core business logic organized by bounded context:
- **Product**: Product types (Simple, Configurable), attribute values, images, associations
- **Attribute**: Attribute types (12), families, groups, options, translations
- **Category**: Nested set tree, custom fields, locale-specific data
- **User**: Admin users, roles, permissions
- **DataTransfer**: Import/export engine with batch processing
- **MagicAI**: OpenAI integration for content generation

### 5. Infrastructure Layer
Cross-cutting concerns shared across all packages:
- **Core**: Base models, shared traits, helpers, utilities
- **DataGrid**: Reusable server-side data grid with filtering, sorting, pagination
- **Theme**: View rendering, theme management
- **HistoryControl**: Version tracking, audit trail
- **ElasticSearch**: Search indexing and querying
- **FPC**: Full page cache event management

### 6. Data/External Layer
External services and data stores the application integrates with.

---

## Data Architecture

### Storage Strategy: Hybrid EAV + JSON

Products use a **JSON column** (`values`) to store all attribute values, providing flexibility similar to EAV without the join overhead:

```json
{
  "common": {
    "sku": "PROD-001",
    "status": true
  },
  "locale_specific": {
    "en_US": {
      "name": "Product Name",
      "description": "Description"
    },
    "fr_FR": {
      "name": "Nom du Produit"
    }
  },
  "channel_specific": {
    "default": {
      "price": 29.99
    }
  },
  "channel_locale_specific": {
    "default": {
      "en_US": {
        "meta_title": "SEO Title"
      }
    }
  }
}
```

### Category Structure: Nested Set
Categories use the **nested set model** (kalnoy/nestedset) with `_lft`, `_rgt`, and `parent_id` columns for efficient tree operations. Category field values stored in `additional_data` JSON column.

### Translation Pattern
Multi-locale content uses the **translatable model** pattern (astrotomic/laravel-translatable):
- Separate `*_translations` tables for entities with locale-specific fields
- `TranslatableModel` trait on parent models
- Automatic locale resolution from request context

---

## Authentication Architecture

### Dual-Guard System

```
                    ┌──────────────────┐
                    │   Request        │
                    └────────┬─────────┘
                             │
              ┌──────────────┴──────────────┐
              │                              │
    ┌─────────┴──────────┐      ┌───────────┴──────────┐
    │  Web Request        │      │  API Request          │
    │  (admin guard)      │      │  (api guard)          │
    └─────────┬──────────┘      └───────────┬──────────┘
              │                              │
    ┌─────────┴──────────┐      ┌───────────┴──────────┐
    │  Session Auth       │      │  OAuth2 (Passport)    │
    │  + Bouncer RBAC     │      │  + ScopeMiddleware    │
    └─────────┬──────────┘      └───────────┬──────────┘
              │                              │
              └──────────────┬───────────────┘
                             │
                    ┌────────┴─────────┐
                    │  Admin Model     │
                    │  + Role (RBAC)   │
                    └──────────────────┘
```

### Permission Model
- **Permission Types**: `all` (superadmin) or `custom` (granular)
- **Permission Structure**: Hierarchical keys (e.g., `catalog.products.create`)
- **ACL Registration**: Defined in package config files (`acl.php`, `api-acl.php`)
- **80+ granular permissions** across Dashboard, Catalog, Data Transfer, Settings, Configuration

---

## API Design

### RESTful Conventions (v1)
- **Base URL**: `/v1/rest/`
- **Authentication**: OAuth2 Bearer token (Password Grant)
- **Content Type**: `application/json`
- **Locale**: Request header `Accept-Language` for locale-specific content
- **Pagination**: Offset-based with configurable page size

### API Resource Pattern
```
GET    /v1/rest/{resource}           → List (paginated)
GET    /v1/rest/{resource}/{code}    → Get single by code
POST   /v1/rest/{resource}           → Create
PUT    /v1/rest/{resource}/{code}    → Full update
PATCH  /v1/rest/{resource}/{code}    → Partial update
DELETE /v1/rest/{resource}/{code}    → Delete
```

---

## Queue Architecture

```
┌────────────────┐     ┌──────────────┐     ┌───────────────┐
│  Web Request   │────>│  Job Dispatch│────>│  Redis/DB     │
│  (Import/      │     │              │     │  Queue        │
│   Export/AI)   │     └──────────────┘     └───────┬───────┘
└────────────────┘                                   │
                                          ┌──────────┴──────────┐
                                          │                      │
                                    ┌─────┴─────┐        ┌──────┴──────┐
                                    │  system    │        │  default    │
                                    │  queue     │        │  queue      │
                                    │ (priority) │        │             │
                                    └─────┬─────┘        └──────┬──────┘
                                          │                      │
                                    ┌─────┴─────┐        ┌──────┴──────┐
                                    │ Import/   │        │ Webhooks,  │
                                    │ Export    │        │ Notifs,    │
                                    │ Jobs      │        │ Completenes│
                                    └───────────┘        └─────────────┘
```

**Two priority queues:**
1. `system` (high priority): Import/export operations, data indexing
2. `default` (normal): Webhooks, notifications, completeness scoring

---

## Frontend Architecture

### Vue.js Islands Pattern

The frontend uses a hybrid approach: server-rendered Blade templates for layout and structure, with Vue.js 3 components mounted for interactive regions (islands).

```
┌─────────────────────────────────────────────┐
│  Blade Layout (Server-rendered)              │
│  ┌─────────────────────────────────────────┐│
│  │  Header (Vue: notifications, search)    ││
│  ├─────────────────────────────────────────┤│
│  │  Sidebar (Vue: collapsible menu)        ││
│  ├─────────────────────────────────────────┤│
│  │  Content Area                           ││
│  │  ┌───────────────────────────────────┐  ││
│  │  │  DataGrid (Vue: reactive table)   │  ││
│  │  │  - Server-side pagination         │  ││
│  │  │  - Filtering, sorting             │  ││
│  │  │  - Mass actions                   │  ││
│  │  └───────────────────────────────────┘  ││
│  │  ┌───────────────────────────────────┐  ││
│  │  │  Forms (Vue: validation)          │  ││
│  │  │  - VeeValidate rules             │  ││
│  │  │  - Dynamic field types            │  ││
│  │  └───────────────────────────────────┘  ││
│  └─────────────────────────────────────────┘│
└─────────────────────────────────────────────┘
```

### Component Registration
Components are registered globally via `app.component()` in Blade `@pushOnce('scripts')` blocks, using `<script type="text/x-template">` for templates.

### State Management
- **No centralized store** (Vuex/Pinia)
- Component-level reactive state
- Event emitter for cross-component communication
- LocalStorage for persistent UI state (datagrid filters, column preferences)
- Cookies for preferences (dark mode, sidebar collapse)

---

## Testing Strategy

```
┌──────────────────────────────────────────────┐
│                Testing Pyramid                │
│                                               │
│              ┌──────────┐                     │
│              │   E2E    │  23 Playwright specs │
│              │ (Browser)│                     │
│              └────┬─────┘                     │
│           ┌───────┴────────┐                  │
│           │   Feature      │  55+ test files  │
│           │   (Integration)│                  │
│           └───────┬────────┘                  │
│       ┌───────────┴──────────┐                │
│       │     Unit Tests       │  4+ test files │
│       │     (Isolated)       │                │
│       └──────────────────────┘                │
└──────────────────────────────────────────────┘
```

- **Pest PHP** as primary test framework with **PHPUnit** under the hood
- **9 test suites** across packages
- **Playwright** for E2E browser testing with pre-authenticated state
- **3 CI/CD workflows**: Linting (Pint), Pest tests, Playwright tests

---

## Deployment Architecture

### Docker Compose (4 Services)

```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  unopim-web  │  │ unopim-mysql │  │  unopim-q    │  │unopim-mailpit│
│  (Apache +   │  │ (MySQL 8)    │  │ (Queue       │  │ (Mail test)  │
│   PHP-FPM)   │  │              │  │  Worker)     │  │              │
│  Port: 8000  │  │ Port: 3306   │  │  system,     │  │ Port: 8025   │
│              │  │              │  │  default     │  │              │
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────────────┘
       │                  │                 │
       └──────────────────┴─────────────────┘
                    Docker Network
```

### Production Requirements
- **PHP** 8.2+ with extensions: curl, fileinfo, gd, intl, mbstring, openssl, pdo, pdo_mysql, tokenizer, zip
- **Web Server**: Nginx or Apache2
- **Database**: MySQL 8.0.32+ or PostgreSQL 14+
- **RAM**: 8GB minimum
- **Node.js**: 18.17.1 LTS+ (for asset building)
- **Optional**: Redis (queue/cache), Elasticsearch 8.x (search)

---

## Code Conventions

### PHP
- **PSR-4** autoloading
- **Laravel Pint** code style (enforced via CI)
- **Repository Pattern** for data access
- **Service classes** for business logic
- **Contracts (Interfaces)** for dependency inversion
- **Traits** for shared behavior (TranslatableModel, HistoryTrait, etc.)

### Frontend
- **Vue.js 3** component-based architecture
- **Tailwind CSS** utility-first styling
- **Blade Components** with named slots (`<x-admin::component>`)
- **Event-driven** inter-component communication
- **VeeValidate** for form validation with 33 locale support

### Database
- **Code-based identifiers** (not numeric IDs) for API resources
- **JSON columns** for flexible data (product values, permissions)
- **Soft deletes** not used (hard deletes with history tracking)
- **Timestamps** on most tables
- **Translations** in separate `*_translations` tables
