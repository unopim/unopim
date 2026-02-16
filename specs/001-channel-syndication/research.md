# Research: Unified Multi-Channel Product Syndication

**Feature**: 001-channel-syndication
**Date**: 2026-02-14

## Research Summary

All NEEDS CLARIFICATION items have been resolved through API
research and codebase analysis.

---

## Decision 1: Unified Adapter Architecture

**Decision**: Extend the existing Shopify package pattern into
a shared `ChannelConnector` package with per-channel adapters.

**Rationale**: The Shopify package at `packages/Webkul/Shopify/`
is already a production-grade channel connector with GraphQL
API client, importers/exporters extending DataTransfer's
abstract classes, credential management, field mapping, and
batch processing. Building a new abstraction layer on top of
this proven pattern minimizes risk.

**Alternatives considered**:
- Build entirely new package from scratch — rejected because
  the Shopify package patterns are battle-tested and rewriting
  would duplicate effort.
- Keep Shopify as-is and build Salla/EasyOrders as separate
  packages — rejected because it creates maintenance burden
  and inconsistent admin UX across channels.

**Architecture**:
```
packages/Webkul/ChannelConnector/src/  (new shared package)
├── Contracts/
│   └── ChannelAdapterContract.php
├── Adapters/
│   └── AbstractChannelAdapter.php
├── Models/ (shared models: SyncJob, Conflict, ProductMapping)
├── Services/ (SyncEngine, ConflictResolver, MappingService)
└── Providers/

packages/Webkul/Shopify/src/  (refactored to use shared base)
packages/Webkul/Salla/src/    (new)
packages/Webkul/EasyOrders/src/ (new)
```

---

## Decision 2: Salla API Integration

**Decision**: Use Salla's OAuth2 REST API v2 with the official
`salla/laravel-starter-kit` package.

**Rationale**: Salla provides a well-documented REST API at
`https://api.salla.dev/admin/v2` with OAuth2 authentication.
The official Laravel starter kit handles token management.

**API Details**:
- **Base URL**: `https://api.salla.dev/admin/v2`
- **Auth**: OAuth2 (14-day access tokens, 1-month refresh)
- **Scopes**: `offline_access`, `products.read_write`
- **Rate Limits**: Plan-dependent per minute
- **Product Endpoints**:
  - `GET /products` — List products
  - `POST /products` — Create product
  - `PUT /products/{id}` — Update product
  - `DELETE /products/{id}` — Delete product
- **Webhooks**: HMAC-verified, events include
  `product.created`, `product.updated`, `product.deleted`
- **Locale**: `Accept-Language: AR` header for Arabic RTL
- **Currency**: SAR with tax endpoints for VAT handling

**Alternatives considered**:
- Direct HTTP calls without SDK — rejected because the
  official package handles OAuth2 token refresh automatically.

---

## Decision 3: Easy Orders API Integration

**Decision**: Build a custom HTTP client wrapper since no
official SDK exists. Use API key authentication.

**Rationale**: Easy Orders' API documentation is available at
`https://public-api-docs.easy-orders.net/docs/` but requires
authenticated access. Public information confirms API key
authentication and REST endpoints for product management.

**API Details** (confirmed through research):
- **Auth**: API key in request header
- **Endpoints**: Product CRUD, Category management, Order
  tracking with commission fields
- **Rate Limits**: To be confirmed from vendor documentation
- **Commission**: Per-product commission rate and amount fields

**Action Required**: Request API documentation portal access
from Easy Orders before implementation begins. The adapter
can be stubbed with interface compliance while awaiting docs.

**Alternatives considered**:
- Delay Easy Orders until documentation is available —
  partially accepted; the adapter interface will be defined
  now, with implementation gated on API access.

---

## Decision 4: Shopify API Version

**Decision**: Use Shopify GraphQL Admin API version 2025-01
(latest stable at time of development).

**Rationale**: The existing Shopify package already uses
GraphQL with the `GraphQLApiClient`. Upgrading to the latest
stable version provides access to bulk operations and improved
rate limits.

**API Details**:
- **GraphQL**: `/admin/api/2025-01/graphql.json`
- **Rate Limits (cost-based)**:
  - Standard: 50 points/sec
  - Query: 1 point, Mutation: 10 points
- **Bulk Operations**: Up to 5 concurrent, JSONL format
- **Webhooks**: HMAC-SHA256 verification
  - Topics: `PRODUCTS_CREATE`, `PRODUCTS_UPDATE`,
    `PRODUCTS_DELETE`, `INVENTORY_LEVELS_UPDATE`

---

## Decision 5: Sync Job State Machine

**Decision**: Use a 5-state machine: `pending → running →
completed | failed → retrying → running`.

**Rationale**: Aligns with the existing DataTransfer
`JobTrack` states while adding `retrying` for the retry
workflow. The existing states are:
`pending → validated → processing → processed → linking →
linked → indexing → indexed → completed`.

For channel sync, the granular DataTransfer states are
unnecessary because there's no file validation or indexing
phase. A simplified state machine is clearer.

**State Transitions**:
```
pending  → running     (job dispatched to queue)
running  → completed   (all products synced successfully)
running  → failed      (unrecoverable error or max retries)
failed   → retrying    (admin triggers retry)
retrying → running     (retry job dispatched)
```

---

## Decision 6: Conflict Detection Strategy

**Decision**: Hash-based change detection using MD5 of synced
product values stored in `product_channel_mappings`.

**Rationale**: On each successful sync, the system stores an
MD5 hash of the product's mapped values. On the next sync,
the system:
1. Computes the current hash from UnoPim product values
2. Fetches the current product data from the channel API
3. Computes the channel hash
4. Compares both against the stored hash
5. If both differ from stored → conflict detected

**Alternatives considered**:
- Timestamp-based detection — rejected because external
  channels may not expose modification timestamps for
  individual fields.
- Webhook-only detection — rejected because not all channels
  support webhooks and webhook delivery is not guaranteed.

---

## Decision 7: Database Schema Strategy

**Decision**: Use ENUM-like string columns (not MySQL ENUM
type) for status/type fields to maintain cross-database
compatibility per Constitution Principle III.

**Rationale**: MySQL ENUM is not portable to PostgreSQL or
SQLite. Using `VARCHAR` with application-level validation
via Laravel's `Rule::in()` provides the same data integrity
while remaining database-agnostic.

---

## Decision 8: Package Structure

**Decision**: Create one shared package (`ChannelConnector`)
and keep channel-specific packages separate.

**Rationale**: Follows Constitution Principle I (Modular
Package Architecture). Each channel adapter is its own Concord
module with independent models, migrations, routes, and views.
The shared package provides base classes and contracts only.

**Registration**: Each channel package registers via
`config/concord.php` independently, allowing selective
installation (e.g., install Salla without Shopify).

---

## Decision 9: Tenant Isolation by Default

**Decision**: All channel syndication models MUST use the
`BelongsToTenant` trait and all queue jobs MUST use the
`TenantAwareJob` trait. Tenant isolation is always active,
not conditional on a configuration flag.

**Rationale**: The Tenant package provides a complete isolation
framework that works transparently:

- **`BelongsToTenant` trait**: Registers `TenantScope` global
  scope on all queries and auto-sets `tenant_id` on model
  creation via the `creating` event. When `core()->getCurrentTenantId()`
  is null (single-tenant or platform context), no filtering is
  applied. When set, all queries are scoped automatically.
- **`TenantAwareJob` trait**: Serializes `tenant_id` into job
  payload, restores context via `TenantSandbox` middleware
  before `handle()`, and routes jobs to per-tenant queues
  (`tenant-{id}-{base}`) for fairness.
- **`TenantCache`**: HMAC-based opaque cache key prefixing
  prevents cross-tenant cache pollution.
- **`TenantAwareBuilder`**: Logs any attempt to bypass
  `TenantScope` to the security channel for audit.

**Key Implementation Rules**:
1. `BelongsToTenant` MUST be the first trait on all 5 models.
2. `tenant_id` column is NOT NULL (auto-set by the trait
   from `core()->getCurrentTenantId()`; backfilled to default
   tenant ID=1 for existing deployments).
3. Unique constraints MUST be tenant-scoped composites:
   `(tenant_id, code)` instead of global `(code)`.
4. `ProcessSyncJob` MUST use `TenantAwareJob` trait.
5. Cache keys in services MUST use `TenantCache::key()`.
6. Migrations follow the existing Wave pattern (Wave 8).

**Why "always on" instead of conditional**:
- The `TenantScope` is a no-op when tenant context is null,
  so it adds zero overhead in single-tenant deployments.
- "Always on" prevents the bug class where a developer forgets
  the conditional check and leaks data across tenants.
- Aligns with Constitution Security Constraint: "Cross-tenant
  data leakage is a P0 security incident."

**Alternatives considered**:
- Conditional tenant isolation (check config flag) — rejected
  because the `BelongsToTenant` trait already handles both
  modes transparently, and conditional code paths increase
  the attack surface for data leakage.
- Separate tenant-aware model subclasses — rejected because
  the trait pattern is the established UnoPim standard and
  avoids class hierarchy complexity.

---

## Decision 10: Multi-Language Sync Architecture

**Decision**: The sync engine MUST extract and push product
values on a per-locale basis using the established
`Attribute::getValueFromProductValues()` pipeline. Locale
mapping is configured per field mapping and drives which
UnoPim locales are synced to which channel locales.

**Rationale**: Constitution Principle IX mandates Multi-Channel
& Multi-Locale First. Product values are stored in the JSON
`values` column with four scope keys: `common`,
`locale_specific`, `channel_specific`, and
`channel_locale_specific`. The sync engine must iterate
through all mapped locale pairs and extract values per-locale,
per-channel using the attribute's scope flags
(`value_per_locale`, `value_per_channel`).

**Per-Locale Sync Flow**:
1. Load field mappings with their `locale_mapping` JSON
   (e.g., `{"en_US": "en", "ar_AE": "ar", "fr_FR": "fr"}`).
2. For each mapped attribute, check its scope flags:
   - `value_per_locale=false, value_per_channel=false` →
     read from `values.common` (same value for all locales).
   - `value_per_locale=true` → read from
     `values.locale_specific.{locale_code}`.
   - `value_per_channel=true` → read from
     `values.channel_specific.{channel_code}`.
   - Both true → read from
     `values.channel_locale_specific.{channel_code}.{locale_code}`.
3. Build per-locale payload for the channel API:
   ```
   {
     "en": {"title": "Product Name", "description": "..."},
     "ar": {"title": "اسم المنتج", "description": "..."}
   }
   ```
4. Adapter formats this into channel-specific structure
   (e.g., Shopify `translations` mutations, Salla
   `Accept-Language` per request).

**RTL Content Handling**:
- Locale codes with RTL scripts (ar_AE, he_IL) are detected
  via a static RTL locale list in `AbstractChannelAdapter`.
- For channels that support RTL natively (Salla): content is
  sent as-is with proper `Accept-Language` header.
- For channels that do NOT support RTL (some Shopify themes):
  Unicode bidi markers (U+200F, U+200E, U+202B, U+202C) are
  stripped from text values but the content direction is
  preserved. A warning is logged per product.
- RTL stripping is a value transformation configured per
  field mapping, not global behavior.

**Hash Computation for Multi-Locale**:
- The data hash in `product_channel_mappings` MUST include
  ALL locale variants of mapped values, not just the default.
- Hash input: JSON-encode the sorted mapped values across
  all locale pairs, then MD5. This ensures locale-specific
  changes trigger conflict detection.

**Connector Name Translation**:
- `ChannelConnector.name` remains a plain VARCHAR(255), NOT
  a translatable field. Rationale: connector names are
  internal admin labels (e.g., "My Shopify Store"), not
  customer-facing content. Adding `TranslatableModel` for a
  single internal label violates Principle XII (YAGNI).
- If multi-language connector names become needed, a future
  migration can add a `channel_connector_translations` table.

**UI Translation Files**:
- The `ChannelConnector` package MUST include translation
  files for all 33 UnoPim locales at:
  `Resources/lang/{locale}/app.php`.
- All admin UI strings (labels, buttons, messages, errors,
  status badges) MUST use `@lang('channel_connector::app.key')`
  in Blade and `trans('channel_connector::app.key')` in PHP.
- Translation keys MUST follow UnoPim's nested array
  convention organized by section (connectors, mappings,
  sync, conflicts, webhooks).
- `en_US` is the primary translation; other locales can start
  with English fallbacks and be translated incrementally.

**API Locale Resolution**:
- The existing `request.locale` middleware validates locales
  in API requests. No changes needed.
- API response messages (success/error) MUST use `trans()`
  for localization based on the request locale.
- The `LocaleMiddleware` validates that locale codes in
  request payloads (field mappings, sync triggers) exist in
  the `locales` table.

**Alternatives considered**:
- Make `ChannelConnector.name` translatable via
  `TranslatableModel` — rejected per Principle XII (YAGNI).
  Connector names are admin-internal, not product content.
- Store per-locale sync hashes separately — rejected because
  a single hash across all locales is simpler and catches
  any locale-specific change. Separate hashes would only
  benefit partial-locale sync which is not a requirement.
- Build a custom locale resolver for channel sync — rejected
  because the existing `Attribute::getValueFromProductValues()`
  already handles scope-aware extraction. The sync engine
  iterates locale pairs and calls the existing method.
