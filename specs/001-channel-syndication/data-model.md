# Data Model: Unified Multi-Channel Product Syndication

**Feature**: 001-channel-syndication
**Date**: 2026-02-14
**Updated**: 2026-02-14 (tenant isolation by default)

---

## Tenant Isolation Strategy

All 5 models use the `BelongsToTenant` trait (MUST be first trait)
which registers `TenantScope` as a global scope and auto-sets
`tenant_id` on creation. This is always active — in single-tenant
deployments the scope is a transparent no-op.

**Model requirements**:

- `BelongsToTenant` trait MUST be declared first in use list
- `tenant_id` is NOT NULL (auto-set from `core()->getCurrentTenantId()`)
- Global unique constraints become tenant-scoped composites
- Uses `TenantAwareBuilder` for scope-bypass audit logging
- Migrations follow existing Wave pattern (Wave 8 for channel tables)
- Backfill: existing data migrated to default tenant (id=1)

**Queue job requirements**:

- `ProcessSyncJob` uses `TenantAwareJob` trait
- Job payload serializes `tenant_id`
- `TenantSandbox` middleware restores/clears context around `handle()`
- Jobs route to per-tenant queues: `tenant-{id}-sync`

**Cache requirements**:

- All service-layer cache operations use `TenantCache::key()`
- Prevents cross-tenant cache pollution via HMAC-based prefixes

---

## Entity Overview

```
ChannelConnector ──→ ChannelFieldMapping (1:many)
ChannelConnector ──→ ChannelSyncJob (1:many)
ChannelConnector ──→ ProductChannelMapping (1:many)

ChannelSyncJob ──→ ChannelSyncJob (retry_of, self-ref)
ChannelSyncJob ──→ ChannelSyncConflict (1:many)

ProductChannelMapping ──→ Product (many:1)
ProductChannelMapping ──→ ChannelConnector (many:1)

ChannelSyncConflict ──→ Product (many:1)
ChannelSyncConflict ──→ ChannelConnector (many:1)
ChannelSyncConflict ──→ ChannelSyncJob (many:1)

All entities scoped by tenant_id (via BelongsToTenant trait)
```

---

## Table: channel_connectors

**Package**: ChannelConnector
**Model**: `Webkul\ChannelConnector\Models\ChannelConnector`
**Contract**: `Webkul\ChannelConnector\Contracts\ChannelConnector`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED PK | Auto-increment |
| tenant_id | BIGINT UNSIGNED FK NOT NULL | References tenants.id (auto-set by BelongsToTenant) |
| code | VARCHAR(255) | Connector identifier (unique per tenant) |
| name | VARCHAR(255) | Display name |
| channel_type | VARCHAR(50) | shopify / salla / easy_orders |
| credentials | TEXT | Encrypted JSON (tokens, keys, URLs) |
| settings | JSON | Channel-specific config (locale mapping, tax, etc.) |
| status | VARCHAR(20) | connected / disconnected / error |
| last_synced_at | TIMESTAMP NULL | Last successful sync |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Traits**: `BelongsToTenant` (first), `HistoryTrait`

**Indexes**:

- `idx_connector_tenant_code (tenant_id, code)` UNIQUE (tenant-scoped uniqueness)
- `idx_connector_tenant_type (tenant_id, channel_type)`
- `idx_connector_tenant_id (tenant_id, id)` (standard tenant composite)
- `idx_connector_status (status)`

**Foreign Keys**:

- `tenant_id` → `tenants.id` ON DELETE CASCADE

**Validation Rules**:

- `code`: required, unique per tenant (`Rule::unique()->where('tenant_id', ...)`), slug format
- `name`: required, max 255
- `channel_type`: required, in:[shopify, salla, easy_orders]
- `credentials`: required, valid JSON
- `status`: in:[connected, disconnected, error]

**Casts**: `credentials` → encrypted array, `settings` → array

---

## Table: channel_field_mappings

**Package**: ChannelConnector
**Model**: `Webkul\ChannelConnector\Models\ChannelFieldMapping`
**Contract**: `Webkul\ChannelConnector\Contracts\ChannelFieldMapping`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED PK | Auto-increment |
| tenant_id | BIGINT UNSIGNED FK NOT NULL | References tenants.id (auto-set by BelongsToTenant) |
| channel_connector_id | BIGINT UNSIGNED FK | References channel_connectors.id |
| unopim_attribute_code | VARCHAR(255) | UnoPim attribute code |
| channel_field | VARCHAR(255) | Target channel field identifier |
| direction | VARCHAR(10) | export / import / both |
| transformation | JSON NULL | Value transformation rules |
| locale_mapping | JSON NULL | Locale code mapping |
| sort_order | INT DEFAULT 0 | Display ordering |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Traits**: `BelongsToTenant` (first)

**Indexes**:

- `idx_mapping_tenant_id (tenant_id, id)` (standard tenant composite)
- `idx_mapping_connector (channel_connector_id)`
- `idx_mapping_unique (channel_connector_id, unopim_attribute_code, channel_field)` UNIQUE

**Foreign Keys**:

- `tenant_id` → `tenants.id` ON DELETE CASCADE
- `channel_connector_id` → `channel_connectors.id` ON DELETE CASCADE

**Validation Rules**:

- `channel_connector_id`: required, exists
- `unopim_attribute_code`: required
- `channel_field`: required
- `direction`: required, in:[export, import, both]

**Casts**: `transformation` → array, `locale_mapping` → array

---

## Table: channel_sync_jobs

**Package**: ChannelConnector
**Model**: `Webkul\ChannelConnector\Models\ChannelSyncJob`
**Contract**: `Webkul\ChannelConnector\Contracts\ChannelSyncJob`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED PK | Auto-increment |
| tenant_id | BIGINT UNSIGNED FK NOT NULL | References tenants.id (auto-set by BelongsToTenant) |
| channel_connector_id | BIGINT UNSIGNED FK | References channel_connectors.id |
| job_id | VARCHAR(36) | UUID job identifier (unique per tenant) |
| status | VARCHAR(20) | pending / running / completed / failed / retrying |
| sync_type | VARCHAR(20) | full / incremental / single |
| total_products | INT DEFAULT 0 | Total products to sync |
| synced_products | INT DEFAULT 0 | Successfully synced |
| failed_products | INT DEFAULT 0 | Failed to sync |
| error_summary | JSON NULL | Aggregated error details |
| retry_of_id | BIGINT UNSIGNED FK NULL | References channel_sync_jobs.id |
| started_at | TIMESTAMP NULL | Job start time |
| completed_at | TIMESTAMP NULL | Job completion time |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Traits**: `BelongsToTenant` (first)

**Indexes**:

- `idx_syncjob_tenant_id (tenant_id, id)` (standard tenant composite)
- `idx_syncjob_tenant_status (tenant_id, status)`
- `idx_syncjob_connector (channel_connector_id, created_at)`
- `idx_syncjob_tenant_jobid (tenant_id, job_id)` UNIQUE (tenant-scoped uniqueness)
- `idx_syncjob_retry (retry_of_id)`

**Foreign Keys**:

- `tenant_id` → `tenants.id` ON DELETE CASCADE
- `channel_connector_id` → `channel_connectors.id` ON DELETE CASCADE
- `retry_of_id` → `channel_sync_jobs.id` ON DELETE SET NULL

**State Machine**:
```
pending  → running     (job worker picks up)
running  → completed   (all batches done, no fatal errors)
running  → failed      (fatal error or all retries exhausted)
failed   → retrying    (admin initiates retry)
retrying → running     (retry job worker picks up)
```

**Validation Rules**:
- `channel_connector_id`: required, exists
- `sync_type`: required, in:[full, incremental, single]
- `status`: in:[pending, running, completed, failed, retrying]

**Casts**: `error_summary` → array

---

## Table: product_channel_mappings

**Package**: ChannelConnector
**Model**: `Webkul\ChannelConnector\Models\ProductChannelMapping`
**Contract**: `Webkul\ChannelConnector\Contracts\ProductChannelMapping`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED PK | Auto-increment |
| tenant_id | BIGINT UNSIGNED FK NOT NULL | References tenants.id (auto-set by BelongsToTenant) |
| channel_connector_id | BIGINT UNSIGNED FK | References channel_connectors.id |
| product_id | BIGINT UNSIGNED FK | References products.id |
| external_id | VARCHAR(255) | Product ID in the external channel |
| external_variant_id | VARCHAR(255) NULL | Variant ID in external channel |
| entity_type | VARCHAR(20) | product / variant / category / image |
| sync_status | VARCHAR(20) | synced / pending / failed / conflicted |
| last_synced_at | TIMESTAMP NULL | Last successful sync |
| data_hash | VARCHAR(32) NULL | MD5 hash of last synced data |
| meta | JSON NULL | Additional mapping metadata |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Traits**: `BelongsToTenant` (first)

**Indexes**:

- `idx_pcm_tenant_id (tenant_id, id)` (standard tenant composite)
- `idx_pcm_connector_product (channel_connector_id, product_id, entity_type)` UNIQUE
- `idx_pcm_external (channel_connector_id, external_id)`
- `idx_pcm_status (sync_status)`
- `idx_pcm_product (product_id)`

**Foreign Keys**:

- `tenant_id` → `tenants.id` ON DELETE CASCADE
- `channel_connector_id` → `channel_connectors.id` ON DELETE CASCADE
- `product_id` → `products.id` ON DELETE CASCADE

**Validation Rules**:
- `channel_connector_id`: required, exists
- `product_id`: required, exists
- `external_id`: required
- `entity_type`: required, in:[product, variant, category, image]
- `sync_status`: in:[synced, pending, failed, conflicted]

**Casts**: `meta` → array

---

## Table: channel_sync_conflicts

**Package**: ChannelConnector
**Model**: `Webkul\ChannelConnector\Models\ChannelSyncConflict`
**Contract**: `Webkul\ChannelConnector\Contracts\ChannelSyncConflict`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED PK | Auto-increment |
| tenant_id | BIGINT UNSIGNED FK NOT NULL | References tenants.id (auto-set by BelongsToTenant) |
| channel_connector_id | BIGINT UNSIGNED FK | References channel_connectors.id |
| channel_sync_job_id | BIGINT UNSIGNED FK | References channel_sync_jobs.id |
| product_id | BIGINT UNSIGNED FK | References products.id |
| conflict_type | VARCHAR(30) | field_mismatch / deleted_in_pim / deleted_in_channel / new_in_channel |
| conflicting_fields | JSON | Array of {field, pim_value, channel_value} |
| pim_modified_at | TIMESTAMP NULL | When PIM value was last changed |
| channel_modified_at | TIMESTAMP NULL | When channel value was last changed |
| resolution_status | VARCHAR(20) | pending / pim_wins / channel_wins / merged / dismissed |
| resolution_details | JSON NULL | What was resolved and how |
| resolved_by | BIGINT UNSIGNED FK NULL | References admins.id |
| resolved_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Traits**: `BelongsToTenant` (first)

**Indexes**:

- `idx_conflict_tenant_id (tenant_id, id)` (standard tenant composite)
- `idx_conflict_tenant_status (tenant_id, resolution_status)`
- `idx_conflict_connector (channel_connector_id)`
- `idx_conflict_job (channel_sync_job_id)`
- `idx_conflict_product (product_id)`

**Foreign Keys**:

- `tenant_id` → `tenants.id` ON DELETE CASCADE
- `channel_connector_id` → `channel_connectors.id` ON DELETE CASCADE
- `channel_sync_job_id` → `channel_sync_jobs.id` ON DELETE CASCADE
- `product_id` → `products.id` ON DELETE CASCADE
- `resolved_by` → `admins.id` ON DELETE SET NULL

**Validation Rules**:
- `conflict_type`: required, in:[field_mismatch, deleted_in_pim, deleted_in_channel, new_in_channel]
- `resolution_status`: in:[pending, pim_wins, channel_wins, merged, dismissed]

**Casts**: `conflicting_fields` → array, `resolution_details` → array

---

## Multi-Language Sync Architecture

### Per-Locale Value Extraction

The sync engine extracts product values on a per-locale basis
using each attribute's scope flags. The `locale_mapping` JSON
on `channel_field_mappings` drives which UnoPim locales are
synced to which channel locales.

**Extraction logic per mapped attribute**:

| `value_per_locale` | `value_per_channel` | Source path in `values` JSON |
|-|-|-|
| false | false | `common.{attribute_code}` |
| true | false | `locale_specific.{locale}.{attribute_code}` |
| false | true | `channel_specific.{channel}.{attribute_code}` |
| true | true | `channel_locale_specific.{channel}.{locale}.{attribute_code}` |

All extraction MUST use `Attribute::getValueFromProductValues()`
— never direct JSON path access.

### Per-Locale Payload Structure

The sync engine builds a locale-keyed payload for each product:

```json
{
  "locales": {
    "en": {"title": "Product Name", "description": "English desc"},
    "ar": {"title": "اسم المنتج", "description": "وصف المنتج"}
  },
  "common": {"sku": "PROD-123", "price": 99.99}
}
```

Each adapter transforms this into channel-specific format:

- **Shopify**: GraphQL `translationsRegister` mutation per locale
- **Salla**: Separate `PUT` request per locale with `Accept-Language` header
- **Easy Orders**: Per API documentation (TBD)

### Data Hash for Multi-Locale Change Detection

The `data_hash` column in `product_channel_mappings` MUST
include ALL locale variants of mapped values:

1. Collect mapped values across all locale pairs
2. Sort keys deterministically (attribute code → locale code)
3. JSON-encode the sorted structure
4. MD5 hash the JSON string

This ensures any locale-specific change (e.g., French
description updated) triggers conflict detection on the
next sync cycle.

### RTL Content Handling

Locales with RTL scripts are detected via a static list in
`AbstractChannelAdapter::RTL_LOCALES` (ar_AE, he_IL, etc.).

- Channels with native RTL support (Salla): content sent as-is
- Channels without RTL support: Unicode bidi markers stripped
  as a configurable value transformation per field mapping
- Warning logged per product when RTL stripping is applied

### Connector Name — NOT Translatable

`ChannelConnector.name` is a plain VARCHAR(255), not a
translatable field. Connector names are internal admin labels
(e.g., "My Shopify Store"), not customer-facing content.
Per Constitution Principle XII (YAGNI), adding a
`channel_connector_translations` table is deferred unless
a concrete requirement emerges.

### UI Translation Files

The ChannelConnector package MUST include translation files:

```text
packages/Webkul/ChannelConnector/src/Resources/lang/
├── en_US/app.php          (primary — complete)
├── ar_AE/app.php          (Arabic)
├── fr_FR/app.php          (French)
├── de_DE/app.php          (German)
├── ... (33 locales total)
└── zh_CN/app.php          (Chinese Simplified)
```

Translation key structure:

```php
return [
    'connectors' => [
        'title' => 'Channel Connectors',
        'create-btn' => 'Create Connector',
        'edit-title' => 'Edit Connector',
        'delete-success' => 'Connector deleted successfully.',
        'test-success' => 'Connection verified successfully.',
        'test-failed' => 'Connection test failed: :reason',
        'status' => [
            'connected' => 'Connected',
            'disconnected' => 'Disconnected',
            'error' => 'Error',
        ],
        'channel-types' => [
            'shopify' => 'Shopify',
            'salla' => 'Salla',
            'easy_orders' => 'Easy Orders',
        ],
    ],
    'mappings' => [
        'title' => 'Field Mappings',
        'save-success' => 'Mappings saved successfully.',
        'direction' => [
            'export' => 'Export',
            'import' => 'Import',
            'both' => 'Both',
        ],
    ],
    'sync' => [
        'title' => 'Sync Jobs',
        'trigger-success' => 'Sync job queued successfully.',
        'retry-success' => 'Retry job queued successfully.',
        'status' => [...],
        'types' => [...],
    ],
    'conflicts' => [
        'title' => 'Sync Conflicts',
        'resolve-success' => 'Conflict resolved successfully.',
        'resolution' => [
            'pim_wins' => 'PIM Wins',
            'channel_wins' => 'Channel Wins',
            'merged' => 'Manual Merge',
            'dismissed' => 'Dismissed',
        ],
    ],
];
```

All Blade templates MUST use `@lang('channel_connector::app.key')`.
All PHP code MUST use `trans('channel_connector::app.key')`.
`en_US` is the primary source; other locales start with
English fallbacks and are translated incrementally.

---

## Cross-Database Compatibility Notes

All tables follow Constitution Principle III:

- No MySQL `ENUM` types — use `VARCHAR` with app-level validation
- JSON columns use standard `JSON` type (supported by MySQL 8+, PostgreSQL 14+, SQLite 3.38+)
- No database-specific functions in migrations
- Timestamps use `$table->timestamp()` (standard Laravel)
- Boolean-like status columns use `VARCHAR` not `TINYINT`
- UUID generation handled in application code, not database
- Tenant-scoped unique indexes use raw SQL for SQLite compat
  (e.g., `CREATE UNIQUE INDEX ... ON table (tenant_id, code)`)

## Tenant Isolation Implementation Notes

All tables follow the established Tenant package Wave pattern:

- `tenant_id` column: `BIGINT UNSIGNED NOT NULL`, after `id`
- Standard composite index: `(tenant_id, id)` on every table
- FK: `REFERENCES tenants(id) ON DELETE CASCADE`
- Global unique constraints replaced with tenant-scoped composites
- Backfill migration: `UPDATE table SET tenant_id = 1 WHERE tenant_id IS NULL`
- Models use `BelongsToTenant` trait (MUST be first in use list)
- `TenantScope` global scope auto-applies `WHERE tenant_id = ?`
- No conditional tenant checks — trait is always active, no-op when context is null
- Queue jobs use `TenantAwareJob` trait for context serialization
- Cache keys use `TenantCache::key()` for HMAC-based prefixing
