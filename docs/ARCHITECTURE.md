# UnoPim — System Architecture Documentation

**Version:** 1.0.0
**Date:** 2026-02-25

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Module Architecture (Concord)](#2-module-architecture-concord)
3. [Database Design](#3-database-design)
4. [API Architecture](#4-api-architecture)
5. [Frontend Architecture](#5-frontend-architecture)
6. [Queue & Job System](#6-queue--job-system)
7. [Search Architecture](#7-search-architecture)
8. [Caching Strategy](#8-caching-strategy)
9. [Authentication & Security Architecture](#9-authentication--security-architecture)
10. [Why UnoPim Scales to 1 Million+ Products](#10-why-unopim-scales-to-1-million-products)

---

## 1. Architecture Overview

UnoPim follows a **layered, modular monolith** architecture built on Laravel 10. The application separates concerns across distinct layers while grouping related features into self-contained modules.

```
┌─────────────────────────────────────────────────────────────────────┐
│                          CLIENT LAYER                               │
│        Browser (Vue 3 SPA-like)  │  REST API Consumers              │
└──────────────────────┬──────────────────────┬───────────────────────┘
                       │ HTTPS                │ OAuth2 / API Key
┌──────────────────────▼──────────────────────▼───────────────────────┐
│                       PRESENTATION LAYER                            │
│         Laravel Blade + Vue 3 Components (Vite-compiled)            │
│         Tailwind CSS │ VeeValidate │ Axios HTTP Client               │
└──────────────────────────────────┬──────────────────────────────────┘
                                   │
┌──────────────────────────────────▼──────────────────────────────────┐
│                        APPLICATION LAYER                            │
│   Controllers  │  Form Requests  │  Middleware  │  Service Providers │
│   Auth: Session (web) │ Passport OAuth2 (API) │ API Keys            │
└───────────────┬──────────────────────────────────────┬─────────────┘
                │                                      │
┌───────────────▼──────────────────┐  ┌───────────────▼─────────────┐
│         DOMAIN LAYER             │  │        JOB / QUEUE LAYER     │
│  Repositories  │  Services       │  │  Import Jobs │ Export Jobs   │
│  Events │ Listeners │ Observers  │  │  Webhook Jobs │ AI Jobs      │
│  Traits: History, Audit, i18n    │  │  Completeness Jobs           │
└───────────────┬──────────────────┘  └───────────────┬─────────────┘
                │                                      │
┌───────────────▼──────────────────────────────────────▼─────────────┐
│                     INFRASTRUCTURE LAYER                            │
│  MySQL / PostgreSQL  │  Redis Cache  │  Redis Queue                 │
│  Elasticsearch       │  File Storage │  Pusher WebSockets           │
│  OpenAI API          │  SMTP         │  AWS S3 (optional)           │
└─────────────────────────────────────────────────────────────────────┘
```

### Key Architectural Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Backend framework | Laravel 10 | Mature ecosystem, strong ORM, queue, cache, auth out-of-box |
| Module system | Konekt Concord | PSR-4 autoloaded packages, independent migrations/routes per module |
| Data abstraction | Repository Pattern | Decouples business logic from Eloquent, enables testability |
| Frontend | Vue 3 + Blade | Reactive components within server-rendered templates |
| Build tool | Vite 4 | Fast HMR, tree-shaking, ES modules |
| Search | Elasticsearch (optional) | Scales search independently from the relational DB |
| Queue | Redis (recommended) | High-throughput async processing, horizontal scalability |
| Auth (web) | Laravel Session | Secure, CSRF-protected for browser clients |
| Auth (API) | Laravel Passport | OAuth2 standard for machine-to-machine and third-party apps |

---

## 2. Module Architecture (Concord)

UnoPim uses **Konekt Concord** — a modular Laravel package system — to split the application into 20 independent, self-registering modules. Each module lives under `packages/Webkul/` and is a standalone PHP package.

### Module Map

```
packages/Webkul/
│
├── Core/              # Shared utilities, Channel, Locale, Currency, CoreConfig
├── User/              # Admin user management, roles, RBAC
│
├── Product/           # Products, types (Simple/Configurable), values
├── Category/          # Category tree (Nested Set), CategoryFields
├── Attribute/         # Attributes, Families, Groups, Options
│
├── Admin/             # Admin UI: routes, controllers, Blade views, Vue components
├── AdminApi/          # API key management, API-specific controllers
│
├── DataTransfer/      # CSV/XLSX import and export jobs
├── DataGrid/          # Reusable server-side datagrid component
│
├── ElasticSearch/     # Search integration, index sync observers
├── MagicAI/           # OpenAI content generation, system prompts
├── Webhook/           # Webhook CRUD, dispatch jobs
├── Notification/      # In-app user notifications
├── HistoryControl/    # Audit/history tracking, presenters
├── Completeness/      # Product completeness score calculation
│
├── FPC/               # Full Page Cache middleware
├── Theme/             # Theme resolution
├── Installer/         # First-run installation wizard
└── DebugBar/          # Development debug toolbar
```

### Module Internal Structure

Every module follows an identical internal layout:

```
packages/Webkul/{Module}/
├── src/
│   ├── Config/            # Module configuration files
│   ├── Console/           # Artisan commands
│   ├── Database/
│   │   ├── Migrations/    # Module-specific migrations
│   │   ├── Factories/     # Model factories for testing
│   │   └── Seeders/       # Data seeders
│   ├── Events/            # Domain events
│   ├── Http/
│   │   ├── Controllers/   # Route controllers
│   │   ├── Middleware/    # Module-specific middleware
│   │   └── Requests/      # Form request validation
│   ├── Jobs/              # Queue jobs
│   ├── Listeners/         # Event listeners
│   ├── Models/            # Eloquent models
│   ├── Observers/         # Model observers
│   ├── Providers/         # Service providers
│   ├── Repositories/      # Repository classes
│   ├── Resources/
│   │   ├── assets/        # CSS, JS, fonts, images
│   │   ├── lang/          # Translations
│   │   └── views/         # Blade templates
│   ├── Routes/            # Route definition files
│   └── Services/          # Business logic services
├── composer.json
└── package.json           # (if frontend assets exist)
```

### Module Registration (`config/concord.php`)

```php
'modules' => [
    Webkul\Core\Providers\ModuleServiceProvider::class,
    Webkul\User\Providers\ModuleServiceProvider::class,
    Webkul\Category\Providers\ModuleServiceProvider::class,
    Webkul\Attribute\Providers\ModuleServiceProvider::class,
    Webkul\Product\Providers\ModuleServiceProvider::class,
    Webkul\DataGrid\Providers\ModuleServiceProvider::class,
    Webkul\DataTransfer\Providers\ModuleServiceProvider::class,
    Webkul\ElasticSearch\Providers\ModuleServiceProvider::class,
    Webkul\HistoryControl\Providers\ModuleServiceProvider::class,
    Webkul\Notification\Providers\ModuleServiceProvider::class,
    Webkul\Webhook\Providers\ModuleServiceProvider::class,
    Webkul\MagicAI\Providers\ModuleServiceProvider::class,
    Webkul\Completeness\Providers\ModuleServiceProvider::class,
    Webkul\Admin\Providers\ModuleServiceProvider::class,
    // ...
]
```

### Repository Pattern Implementation

```
Controller
    └── calls → Repository (e.g., ProductRepository)
                    └── extends → Webkul\Core\Eloquent\Repository
                                     └── wraps → Eloquent Model
```

Repositories provide:
- Standard CRUD via `prettus/l5-repository`
- Criteria-based filtering
- Eager loading declarations
- Custom query builders (e.g., `Product\Database\Eloquent\Builder`)

---

## 3. Database Design

### Entity-Relationship Overview

```
AttributeFamily ──< AttributeGroup ──< Attribute ──< AttributeOption
      │
      └──< Product >── Category (many-to-many)
               │
               └── ProductValues (JSON column per locale/channel)
               └── parent Product (configurable variants)

Channel >── Locale
Channel >── Currency
Channel ──  Category (root)

Admin ──< Role
Admin ──< UserNotification

Webhook (standalone, event-triggered)
```

### Key Schema Patterns

#### 1. JSON-Stored Product Values
Product attribute values are stored as JSON in the `products` table's `values` column. This avoids an EAV (Entity-Attribute-Value) anti-pattern while keeping the schema flexible:

```json
{
  "channel_code": {
    "locale_code": {
      "attribute_code": "value"
    }
  },
  "all_channels": {
    "all_locales": {
      "sku": "PROD-001",
      "price": { "USD": 29.99, "EUR": 27.50 }
    }
  }
}
```

**Why this works at scale:**
- Single row read for all attribute values (no EAV join explosion)
- MySQL 8.0+ JSON functions enable indexed JSON path queries
- PostgreSQL JSONB provides GIN indexing for full JSON search
- Eliminates N+1 queries when loading attribute data

#### 2. Nested Set for Categories
Categories use the **Nested Set Model** (via `kalnoy/nestedset`):

```sql
categories
  id | code | parent_id | lft | rgt | depth
  1  | root |   NULL    |  1  | 14  |   0
  2  | electronics | 1 |  2  | 9  |   1
  3  | phones | 2 |      3  |  6  |   2
```

**Advantages:**
- Fetching an entire subtree: `WHERE lft >= X AND rgt <= Y` (single indexed query)
- Counting descendants: `(rgt - lft - 1) / 2`
- No recursive CTEs needed → O(1) depth queries

#### 3. Audit Tables
Full change history via `owen-it/laravel-auditing`:
```sql
audits
  id | user_type | user_id | event | auditable_type | auditable_id
     | old_values (JSON) | new_values (JSON) | url | ip | created_at
```

#### 4. Queue Tables
```sql
jobs         -- pending queue items (payload LONGTEXT)
failed_jobs  -- failed jobs with exception details
job_batches  -- batch tracking for import/export progress
```

### Indexing Strategy

| Table | Indexed Columns | Purpose |
|-------|----------------|---------|
| products | `sku` (unique), `type`, `attribute_family_id`, `parent_id`, `status` | Fast product lookups |
| categories | `lft`, `rgt`, `parent_id`, `code` (unique) | Tree queries |
| attributes | `code` (unique), `type` | Attribute resolution |
| audits | `auditable_type`, `auditable_id`, `created_at` | History queries |
| jobs | `queue`, `reserved_at`, `available_at` | Queue processing |

---

## 4. API Architecture

### API Authentication Flow

```
Client                     UnoPim API
  │                            │
  │  POST /oauth/token         │
  │  {client_id, secret, ...}  │
  │──────────────────────────► │
  │  ◄── access_token ──────── │
  │                            │
  │  GET /api/v1/products      │
  │  Authorization: Bearer <token>
  │──────────────────────────► │
  │  ◄── JSON response ─────── │
```

### Route Structure

```
/admin/                    ← Session-auth web routes (Blade + Vue)
/api/v1/                   ← Passport OAuth2 API routes
  products/
  categories/
  attributes/
  families/
  channels/
  locales/
  currencies/
```

### DataGrid API Pattern

The DataGrid uses a **two-request pattern** to separate data from structure:

```
1. GET  /admin/datagrid          → returns DataGrid config (columns, filters, actions)
2. POST /admin/datagrid/data     → returns paginated, filtered, sorted rows as JSON
```

This allows the Vue frontend to render the table dynamically without hardcoded column definitions.

---

## 5. Frontend Architecture

### Technology Stack

```
Browser
  └── Vite-compiled bundle
        ├── Vue 3.4 (Composition API)
        │     ├── DataGrid components (filtering, sorting, pagination)
        │     ├── Form components (dynamic attribute rendering)
        │     ├── Media upload components
        │     └── Modal / Drawer / Accordion
        │
        ├── Blade templates (server-rendered layout)
        │     ├── Layout shell (header, sidebar, footer)
        │     ├── Page structure
        │     └── Component registration
        │
        ├── Tailwind CSS 3.3
        │     ├── Custom colors: violet (primary), cherry (dark mode)
        │     ├── Dark mode: class-based
        │     └── Custom font: Inter, icomoon icons
        │
        └── Third-party
              ├── Vee-Validate 4.9 (form validation)
              ├── Vue-Multiselect 3.0 (dropdowns)
              ├── Flatpickr 4.6 (date/time pickers)
              ├── Vuedraggable 4.1 (drag-and-drop ordering)
              └── Axios 1.6 (HTTP, CSRF-aware)
```

### Component Hierarchy

```
layouts/index.blade.php
├── layouts/header/
│     ├── Dark mode toggle
│     ├── Notification bell (real-time via Pusher)
│     └── User dropdown
├── layouts/sidebar/
│     ├── Collapsible nav (270px ↔ 70px)
│     └── Active state highlighting
└── <slot> (page content)
      ├── DataGrid (for list views)
      │     ├── toolbar (search, filters, column management)
      │     ├── table (virtualized rows)
      │     └── pagination
      └── Form (for create/edit views)
            ├── control-group (label + input + error)
            └── Dynamic fields per AttributeFamily
```

### Vue–Blade Integration Pattern

UnoPim uses **Blade as the outer shell** and **Vue 3 for interactive islands**:

```blade
{{-- Blade renders the page skeleton --}}
<x-admin::layouts>
    {{-- Vue component mounted here --}}
    <div id="app">
        <v-product-form
            :attributes="{{ json_encode($attributes) }}"
            :product="{{ json_encode($product) }}"
        />
    </div>
</x-admin::layouts>
```

Data flows from PHP → JSON → Vue props, avoiding a full API call for initial page data.

---

## 6. Queue & Job System

### Architecture

```
User Action (e.g., upload CSV)
       │
       ▼
Controller validates & stores file
       │
       ▼
Job dispatched to Queue (Redis recommended)
       │
       ▼
Queue Worker (php artisan queue:work --queue=system,default)
       │
       ├── ImportProductsJob
       │     ├── Reads file in chunks (100 records/batch)
       │     ├── Validates each row
       │     ├── Upserts product + values
       │     └── Updates JobBatch progress
       │
       ├── DispatchWebhookJob
       │     └── HTTP POST to webhook URL
       │
       ├── ProductCompletenessJob
       │     └── Recalculates scores per channel/locale
       │
       └── MagicAIJob
             └── Calls OpenAI API, updates attribute value
```

### Queue Configuration

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

'connections' => [
    'redis' => [
        'driver'     => 'redis',
        'connection' => 'default',
        'queue'      => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
    ],
    'database' => [ /* fallback for environments without Redis */ ],
    'sync'     => [ /* dev/testing only */ ],
    'sqs'      => [ /* AWS SQS for cloud deployments */ ],
]
```

### Job Batch Processing (Import)

```php
Bus::batch(
    $chunks->map(fn($chunk) => new ImportChunkJob($chunk))
)->then(function (Batch $batch) {
    // All chunks completed
})->catch(function (Batch $batch, Throwable $e) {
    // At least one chunk failed
})->finally(function (Batch $batch) {
    // Cleanup
})->dispatch();
```

- Chunks of 100 records per job
- Progress tracked in `job_batches` table
- UI polls progress endpoint
- Failed chunks logged separately; successful chunks committed

---

## 7. Search Architecture

### Elasticsearch Integration

```
Product Save/Update
      │
      ▼
Eloquent Observer (ProductObserver)
      │
      ▼
ElasticSearch Facade → Index product document
      │
      ▼
Elasticsearch 8.17 cluster
      │
      ▼
Search Query → SearchRepository → filtered results
```

### Dual-Mode Search

```php
// SearchRepository
if (config('elasticsearch.enabled')) {
    return $this->searchViaElasticsearch($query, $filters);
} else {
    return $this->searchViaDatabase($query, $filters);
}
```

| Mode | When used | Capability |
|------|-----------|-----------|
| Elasticsearch | `ELASTICSEARCH_ENABLED=true` | Full-text, faceted, 200ms at 10M products |
| Database (MySQL LIKE) | Default / fallback | Simple text search, suitable up to ~100K products |

### Why Elasticsearch Enables Million-Product Scale

- **Inverted index:** O(1) full-text lookups regardless of catalog size
- **Sharding:** Distribute the index across multiple nodes
- **Async indexing:** Product writes don't block on search index updates
- **Relevance scoring:** TF-IDF/BM25 ranking without DB overhead
- **Faceted filtering:** Aggregations for attribute-value facets in milliseconds

---

## 8. Caching Strategy

### Multi-Layer Cache Architecture

```
Request
   │
   ▼ Layer 1: Full Page Cache (FPC)
   │  spatie/laravel-responsecache
   │  Caches complete HTTP responses for read-heavy pages
   │
   ▼ Layer 2: Application Cache (Redis)
   │  Core utilities (channels, locales, currencies)
   │  Attribute families and groups
   │  Resolved configurations
   │
   ▼ Layer 3: ORM-level Eager Loading
   │  Repository with withCount(), with() declarations
   │  Prevents N+1 queries
   │
   ▼ Layer 4: Database (MySQL with Query Cache)
      Indexed columns for all frequent query patterns
```

### What Gets Cached

| Data | Cache Duration | Invalidation |
|------|---------------|-------------|
| Full page responses | Per-route TTL | On product/category update |
| Core config (channels, locales) | Long TTL | On settings change |
| Attribute families | Medium TTL | On attribute change |
| DataGrid results | Per-request | None (fresh per request) |
| Elasticsearch results | Short TTL | On product update |

### Cache Drivers

```
Development:  file (no Redis required)
Production:   Redis (recommended for shared-cache in multi-server)
Cloud:        Supported via CACHE_DRIVER=redis
```

---

## 9. Authentication & Security Architecture

### Authentication Guards

```php
// config/auth.php
'guards' => [
    'admin' => [               // Web browser sessions
        'driver'   => 'session',
        'provider' => 'admins',
    ],
    'api' => [                  // REST API via OAuth2
        'driver'   => 'passport',
        'provider' => 'admins',
    ],
],
```

### Security Middleware Stack

```
HTTP Request
    │
    ├── TrustProxies          ← Fix IP for reverse proxy / load balancer
    ├── HandleCors            ← CORS headers
    ├── ValidatePostSize      ← Prevent large payload attacks
    ├── SecureHeaders         ← X-Frame-Options, CSP, HSTS, etc.
    ├── CanInstall            ← Redirect to installer if not installed
    │
    ├── [Web group]
    │     ├── EncryptCookies
    │     ├── StartSession
    │     ├── VerifyCsrfToken ← CSRF protection for all mutations
    │     └── SubstituteBindings
    │
    └── [API group]
          ├── ThrottleRequests (60/min per user/IP)
          └── SubstituteBindings
```

### RBAC Permission Model

```
Admin User
    └── Role
          └── permissions[] (JSON array of permission keys)

Example permissions:
  - catalog.products.index
  - catalog.products.create
  - catalog.products.edit
  - catalog.products.delete
  - settings.users.index
  - data-transfer.imports.index
```

---

## 10. Why UnoPim Scales to 1 Million+ Products

This section provides a direct, architectural explanation of every design decision that enables catalog scale.

---

### 10.1 JSON Attribute Storage Eliminates EAV Join Explosion

**The problem:** Traditional EAV (Entity-Attribute-Value) systems store each attribute value as a separate row:
```sql
-- EAV: Fetching 50 attributes for 1 product = 50 rows, 3-way JOIN
SELECT p.*, pav.attribute_id, pav.value
FROM products p
JOIN product_attribute_values pav ON pav.product_id = p.id
JOIN attributes a ON a.id = pav.attribute_id
WHERE p.id = 1;
-- At 1M products × 50 attributes = 50M rows in pav table
```

**UnoPim's solution:** All attribute values are stored as a single JSON column per product row:
```sql
-- Single row read for all attributes
SELECT id, sku, values FROM products WHERE id = 1;
-- At 1M products = 1M rows total, regardless of attribute count
```

**Result:** The `products` table never grows beyond 1 row per product. With MySQL 8.0 JSON path expressions and generated virtual columns, specific attributes can still be indexed.

---

### 10.2 Nested Set Categories — O(1) Tree Queries

**The problem:** Recursive adjacency list queries (`parent_id` trees) require either recursive CTEs or multiple round trips for deep trees.

**UnoPim's solution:** Nested Set Model with `lft`/`rgt` boundaries:
```sql
-- Get ALL descendants of category 2 in a SINGLE query
SELECT * FROM categories WHERE lft > 2 AND rgt < 9;

-- This query uses a single B-tree index scan regardless of tree depth
-- Works identically for 100 or 100,000 categories
```

**Result:** Category page loads remain constant-time even with large hierarchies.

---

### 10.3 Paginated DataGrid — Never Full-Table Scans

The DataGrid component **never loads an entire table**. Every list view uses:

- **Offset + limit pagination** for standard browsing
- **Cursor-based pagination** for large datasets (avoids `OFFSET` performance degradation)
- **Server-side filtering** applied before data retrieval (not post-filter in memory)
- **Column-specific indexes** on all filterable columns (`status`, `type`, `sku`, `created_at`)

```sql
-- DataGrid query pattern
SELECT id, sku, type, status
FROM products
WHERE status = 'enabled'
  AND type = 'simple'
ORDER BY created_at DESC
LIMIT 25 OFFSET 0;
-- Uses (status, type, created_at) composite index
-- Executes in < 10ms even on 1M product table
```

---

### 10.4 Elasticsearch Decouples Search from the Database

At 1 million products, `LIKE '%keyword%'` queries are catastrophic (full-table scan, no index use). UnoPim's Elasticsearch integration:

- **Inverted index:** Each keyword maps directly to matching product IDs — O(1) lookup
- **Async indexing:** Product writes update ES via queue job — zero write latency added
- **Horizontal sharding:** Split the index across nodes as catalog grows
- **Relevance ranking:** BM25 scoring without touching MySQL

**Benchmark comparison:**

| Approach | Query time at 1M products |
|----------|--------------------------|
| MySQL LIKE | 8–15 seconds (full scan) |
| MySQL FULLTEXT | 200ms–2s |
| Elasticsearch | 10–80ms |

---

### 10.5 Async Queue Offloads Expensive Operations

Heavy operations that could block or timeout at scale are **always queued**:

| Operation | Approach | Why |
|-----------|----------|-----|
| CSV import (1M rows) | Job batches of 100 | Avoids PHP memory exhaustion |
| Completeness recalculation | Queued per product | Avoids blocking product saves |
| Webhook dispatch | Queued HTTP call | Avoids HTTP timeout on save |
| AI content generation | Queued API call | OpenAI latency (1–5s) hidden from user |
| Elasticsearch sync | Queued observer | Decouples write speed from index update |

**Horizontal scaling:** Add more queue workers to increase throughput linearly:
```bash
# Scale to 10 parallel workers
php artisan queue:work --queue=system,default &  # x10
```

---

### 10.6 Redis Cache Reduces Database Pressure

At 1M products with concurrent users, the same queries fire repeatedly (channel list, locale list, attribute families). Redis caching ensures:

- **Core config queries** (channels, locales, currencies): cached indefinitely, invalidated on change
- **Attribute family structures**: cached per family ID
- **Full page cache**: entire HTML responses cached for read-heavy pages
- **Session storage**: Redis sessions scale horizontally (no sticky sessions needed)

**Effect:** A page that would require 12 DB queries for 100 concurrent users only fires those queries **once**, with 99 cache hits.

---

### 10.7 Modular Architecture Enables Horizontal Service Extraction

While UnoPim ships as a monolith, its **Concord module system** allows any module to be extracted into a microservice without breaking the rest:

- `ElasticSearch` module → already a separate service
- `MagicAI` module → calls external API asynchronously
- `DataTransfer` module → jobs can run on dedicated workers
- `Webhook` module → dispatcher runs on separate queue

This means traffic spikes on one feature (e.g., bulk imports) don't compete with API traffic.

---

### 10.8 Database-Level Safeguards

| Safeguard | Mechanism |
|-----------|----------|
| No N+1 queries | Repository eager loading declarations |
| Index coverage | Composite indexes on all frequent filter patterns |
| JSON query support | MySQL 8.0 JSON_EXTRACT with virtual generated columns |
| Connection pooling | PHP-FPM process model, PgBouncer-compatible |
| Read replicas | Laravel's `read/write` DB connection split (configurable) |
| Failed job recovery | `failed_jobs` table + `queue:retry` artisan command |

---

### 10.9 Scalability Summary

```
1M Products Scenario:
─────────────────────────────────────────────────────────
Layer               Solution                Benefit
─────────────────────────────────────────────────────────
Storage             JSON values column      1 row/product
Tree queries        Nested Set (lft/rgt)    O(1) depth queries
List queries        Paginated DataGrid      Never full-table scan
Full-text search    Elasticsearch           10–80ms at any scale
Write performance   Queue + batch jobs      Non-blocking saves
Read performance    Redis multi-layer cache 99% cache hit rate
Horizontal scale    Stateless workers       Linear throughput scaling
Schema flexibility  Dynamic attributes      No migrations for new attrs
─────────────────────────────────────────────────────────
```
