# Implementation Plan: Unified Multi-Channel Product Syndication

**Branch**: `001-channel-syndication` | **Date**: 2026-02-14 | **Spec**: `specs/001-channel-syndication/spec.md`
**Input**: Feature specification from `/specs/001-channel-syndication/spec.md`

## Summary

Build a unified channel connector system enabling product syndication from
UnoPim to Shopify, Salla, and Easy Orders. The architecture extends the
existing Shopify package pattern into a shared `ChannelConnector` package
with per-channel adapters. All models use `BelongsToTenant` trait (always
active, transparent no-op in single-tenant mode). All sync operations
extract and push product values per-locale using the established
`Attribute::getValueFromProductValues()` pipeline, with locale mapping
driving which UnoPim locales sync to which channel locales.

## Technical Context

**Language/Version**: PHP 8.2+ (Laravel 10.x)
**Primary Dependencies**: Konekt Concord (modular packages), Laravel Passport (OAuth2 API), Astrotomic Translatable (i18n), salla/laravel-starter-kit (Salla OAuth2)
**Storage**: MySQL 8.0+ / PostgreSQL 14+ (production), SQLite (testing)
**Testing**: Pest PHP (unit/integration), Playwright (E2E)
**Target Platform**: Linux server (web application)
**Project Type**: Modular monolith (Webkul package system)
**Performance Goals**: 10,000 products synced within 2 hours; single-product sync < 30 seconds
**Constraints**: Channel API rate limits (Shopify 2 req/s, Salla 600 req/min); cross-database SQL compatibility; 33 locale support
**Scale/Scope**: Multi-tenant, multi-channel, multi-locale; 5 new database tables; 3 channel adapters; 12 ACL permissions

## Constitution Check

*GATE: All 12 principles verified. Re-checked after tenant and i18n updates.*

| # | Principle | Status | Notes |
|---|-----------|--------|-------|
| I | Modular Package Architecture | PASS | New `ChannelConnector` package + per-channel adapter packages, registered via `config/concord.php` |
| II | Repository Pattern | PASS | All 5 models get repositories extending `Webkul\Core\Eloquent\Repository` |
| III | Cross-Database Compatibility | PASS | No MySQL ENUM; VARCHAR with `Rule::in()`; JSON columns standard; SQLite-compatible index creation |
| IV | Contract-Driven Design | PASS | 5 model contracts + `ChannelAdapterContract` interface; dependencies injected via contracts |
| V | Product Values Integrity | PASS | Sync engine uses `Attribute::getValueFromProductValues()` and `setProductValue()` — never direct JSON access |
| VI | Nested Set Integrity | N/A | No category tree modifications |
| VII | Dual-Guard Security | PASS | Web routes: `Bouncer` middleware + ACL; API routes: `auth:api` + `ScopeMiddleware`; 12 ACL permissions in `acl.php` + `api-acl.php` |
| VIII | Event-Driven Lifecycle | PASS | 14 event pairs: connector CRUD, sync lifecycle, conflict detection/resolution, webhook received |
| IX | Multi-Channel & Multi-Locale First | PASS | Per-locale value extraction via attribute scope flags; locale mapping per field; RTL handling; 33-locale translation files; `@lang()` for all UI strings |
| X | Client Layer Standards | PASS | `<x-admin::*>` components, Vue.js 3 Islands, dark mode via `dark:` variants, VeeValidate, DataGrid |
| XI | History & Auditability | PASS | `ChannelConnector` model uses `HistoryTrait`; sync jobs tracked in `channel_sync_jobs` table |
| XII | Simplicity & YAGNI | PASS | Connector `name` is plain VARCHAR (not TranslatableModel) — internal label, not product content; no speculative abstractions |

## Tenant Isolation Architecture

All channel syndication models use "always-on" tenant isolation via the
existing Tenant package. This is not conditional on a configuration flag.

### Model Layer

All 5 models (`ChannelConnector`, `ChannelFieldMapping`, `ChannelSyncJob`,
`ProductChannelMapping`, `ChannelSyncConflict`) MUST:

- Declare `BelongsToTenant` as the FIRST trait in the `use` list
- Have `tenant_id BIGINT UNSIGNED NOT NULL` column (auto-set by trait)
- Use tenant-scoped composite unique indexes instead of global uniques
- Standard composite index: `(tenant_id, id)` on every table

### Database Layer (Wave 8 Migrations)

Migrations follow the established Wave pattern:

- FK: `tenant_id → tenants.id ON DELETE CASCADE` on all 5 tables
- Unique constraints are tenant-scoped:
  - `(tenant_id, code)` on `channel_connectors`
  - `(tenant_id, job_id)` on `channel_sync_jobs`
- Backfill: `UPDATE table SET tenant_id = 1 WHERE tenant_id IS NULL`
- SQLite-compatible raw SQL for composite unique indexes

### Queue Layer

- `ProcessSyncJob` uses `TenantAwareJob` trait
- Job payload serializes `tenant_id`
- `TenantSandbox` middleware restores/clears tenant context around `handle()`
- Jobs route to per-tenant queues: `tenant-{id}-sync`

### Cache Layer

- All service-layer cache operations use `TenantCache::key()`
- HMAC-based opaque prefixes prevent cross-tenant cache pollution
- Cache keys: `TenantCache::key('channel_connector', $connectorId)`

### API Layer

- Existing `TenantMiddleware` resolves context: Subdomain → Header → Token → Session
- All repository queries automatically scoped by `TenantScope`
- Validation rules use `Rule::unique()->where('tenant_id', ...)`

## Multi-Language Architecture

Per Constitution Principle IX, all sync operations support multi-locale
from inception. This is not a separate "i18n feature" — it is built into
every layer of the sync pipeline.

### Per-Locale Value Extraction

The sync engine uses `Attribute::getValueFromProductValues()` to extract
product values based on each attribute's scope flags:

| `value_per_locale` | `value_per_channel` | JSON path |
|---|---|---|
| false | false | `values.common.{code}` |
| true | false | `values.locale_specific.{locale}.{code}` |
| false | true | `values.channel_specific.{channel}.{code}` |
| true | true | `values.channel_locale_specific.{channel}.{locale}.{code}` |

For each product, the engine iterates ALL mapped locale pairs from
`channel_field_mappings.locale_mapping` and builds a locale-keyed payload:

```text
{
  "locales": {
    "en": {"title": "Product Name", "description": "..."},
    "ar": {"title": "اسم المنتج", "description": "..."}
  },
  "common": {"sku": "PROD-123", "price": 99.99}
}
```

### Channel-Specific Locale Handling

Each adapter transforms the locale-keyed payload into its API format:

- **Shopify**: GraphQL `translationsRegister` mutation per locale
- **Salla**: Separate `PUT` request per locale with `Accept-Language` header
- **Easy Orders**: Per API documentation (TBD)

### RTL Content Handling

- RTL locales (ar_AE, he_IL, etc.) detected via `AbstractChannelAdapter::RTL_LOCALES`
- Channels with native RTL support (Salla): content sent as-is
- Channels without RTL support: Unicode bidi markers stripped as a
  configurable value transformation per field mapping; warning logged
- `isRtlLocale()` method on `ChannelAdapterContract` for RTL detection

### Multi-Locale Hash for Conflict Detection

The `data_hash` in `product_channel_mappings` includes ALL locale variants:

1. Collect mapped values across all locale pairs
2. Sort keys deterministically (attribute code → locale code)
3. JSON-encode and MD5 hash

Any locale-specific change triggers conflict detection on next sync.

### Per-Locale Conflict Diffs

When a conflict is detected on a translatable field, `conflicting_fields`
stores per-locale values:

```json
{
  "field": "title",
  "pim_value": {"en_US": "Name", "ar_AE": "اسم"},
  "channel_value": {"en": "Updated Name", "ar": "اسم محدث"},
  "is_locale_specific": true
}
```

The admin conflict resolution UI shows per-locale diffs for translatable
fields and scalar diffs for non-translatable fields.

### UI Translation Files

The `ChannelConnector` package includes `Resources/lang/{locale}/app.php`
for all 33 UnoPim locales. All Blade templates use
`@lang('channel_connector::app.key')` and PHP code uses
`trans('channel_connector::app.key')`. `en_US` is complete; other locales
start with English fallbacks.

### Connector Name — Not Translatable

`ChannelConnector.name` is a plain VARCHAR(255). Connector names are
internal admin labels, not product content. Per Principle XII (YAGNI),
a `channel_connector_translations` table is not created. If needed later,
it can be added as a standard migration.

## Project Structure

### Documentation (this feature)

```text
specs/001-channel-syndication/
├── plan.md              # This file
├── spec.md              # Feature specification (6 user stories, 25 FRs)
├── research.md          # Phase 0 research (10 architectural decisions)
├── data-model.md        # 5 table schemas + tenant + i18n sections
├── quickstart.md        # 7-step setup guide
├── contracts/
│   ├── adapter-interface.md   # ChannelAdapterContract + value objects
│   └── channel-connector-api.md  # REST API (12+ endpoints)
└── tasks.md             # Phase 2 output (via /speckit.tasks)
```

### Source Code (repository root)

```text
packages/Webkul/ChannelConnector/src/           # Shared package
├── Contracts/
│   ├── ChannelConnector.php
│   ├── ChannelFieldMapping.php
│   ├── ChannelSyncJob.php
│   ├── ProductChannelMapping.php
│   ├── ChannelSyncConflict.php
│   └── ChannelAdapterContract.php
├── Models/
│   ├── ChannelConnector.php                    # BelongsToTenant, HistoryTrait
│   ├── ChannelFieldMapping.php                 # BelongsToTenant
│   ├── ChannelSyncJob.php                      # BelongsToTenant
│   ├── ProductChannelMapping.php               # BelongsToTenant
│   └── ChannelSyncConflict.php                 # BelongsToTenant
├── Repositories/
│   ├── ChannelConnectorRepository.php
│   ├── ChannelFieldMappingRepository.php
│   ├── ChannelSyncJobRepository.php
│   ├── ProductChannelMappingRepository.php
│   └── ChannelSyncConflictRepository.php
├── Adapters/
│   └── AbstractChannelAdapter.php              # RTL_LOCALES, isRtlLocale()
├── Services/
│   ├── SyncEngine.php                          # Per-locale extraction loop
│   ├── ConflictResolver.php                    # Per-locale diff generation
│   └── MappingService.php                      # Locale mapping validation
├── Jobs/
│   └── ProcessSyncJob.php                      # TenantAwareJob trait
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                              # Web controllers (Bouncer)
│   │   └── Api/                                # API controllers (auth:api)
│   └── Requests/                               # Form Requests
├── Config/
│   ├── acl.php                                 # 12 web ACL permissions
│   └── api-acl.php                             # 12 API ACL permissions
├── Database/
│   └── Migrations/                             # Wave 8 (tenant-scoped)
├── Events/                                     # 14 event pairs
├── Listeners/
├── DataGrids/
│   ├── ConnectorDataGrid.php
│   ├── SyncJobDataGrid.php
│   └── ConflictDataGrid.php
├── Resources/
│   ├── lang/
│   │   ├── en_US/app.php                       # Primary (complete)
│   │   ├── ar_AE/app.php                       # Arabic
│   │   ├── fr_FR/app.php                       # French
│   │   └── ... (33 locales)
│   └── views/
│       └── admin/                              # Blade + Vue.js 3 Islands
├── Routes/
│   ├── admin-routes.php                        # Web routes
│   └── api-routes.php                          # API routes (v1/rest/)
└── Providers/
    └── ModuleServiceProvider.php               # Concord registration

packages/Webkul/Shopify/src/                    # Refactored to extend shared
├── Adapters/
│   └── ShopifyAdapter.php                      # GraphQL translationsRegister
└── ...

packages/Webkul/Salla/src/                      # New channel adapter
├── Adapters/
│   └── SallaAdapter.php                        # Accept-Language per locale
└── ...

packages/Webkul/EasyOrders/src/                 # New channel adapter (stubbed)
├── Adapters/
│   └── EasyOrdersAdapter.php
└── ...
```

**Structure Decision**: Modular package architecture following
Constitution Principle I. One shared package (`ChannelConnector`) owns
models, migrations, services, and admin UI. Per-channel packages own
only their adapter implementations. All registered via `config/concord.php`.

## Complexity Tracking

| Decision | Why Needed | Simpler Alternative Rejected Because |
|----------|------------|--------------------------------------|
| Shared ChannelConnector package + per-channel adapters | Unified admin UX, shared sync engine, DRY models | Separate packages per channel → inconsistent UI, duplicated sync logic |
| 5-state sync job machine | Tracks retry workflow clearly | 3-state (pending/running/done) → no retry tracking, lost failure context |
| Hash-based multi-locale conflict detection | Catches per-locale changes across all mapped locales | Timestamp-based → channels may not expose per-field timestamps; per-locale hashes → unnecessary complexity |
| Always-on BelongsToTenant | Zero overhead in single-tenant (TenantScope is no-op); prevents data leakage bug class | Conditional tenant checks → increases attack surface, more code paths |
| Per-locale extraction via Attribute scope flags | Reuses proven getValueFromProductValues() pipeline | Custom locale extractor → bypasses established product values integrity (Principle V) |
