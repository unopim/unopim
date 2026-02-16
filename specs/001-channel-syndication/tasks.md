# Tasks: Unified Multi-Channel Product Syndication

**Input**: Design documents from `/specs/001-channel-syndication/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Included per Constitution requirement — "New features MUST include Pest PHP tests covering the primary success path and critical edge cases."

**Organization**: Tasks grouped by user story (6 stories from spec.md, P1–P6). Each story is independently testable after its phase completes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story (US1–US6)
- All paths relative to `packages/Webkul/ChannelConnector/src/` unless otherwise noted

---

## Phase 1: Setup (Package Scaffolding)

**Purpose**: Create the ChannelConnector package skeleton and register it with Concord

- [ ] T001 Create package directory structure per plan.md at `packages/Webkul/ChannelConnector/src/` with subdirectories: Contracts/, Models/, Repositories/, Adapters/, Services/, Jobs/, Http/Controllers/Admin/, Http/Controllers/Api/, Http/Requests/, Config/, Database/Migrations/, Events/, Listeners/, DataGrids/, Resources/lang/, Resources/views/admin/, Routes/, Providers/
- [ ] T002 Create `Providers/ModuleServiceProvider.php` extending `Webkul\Core\Providers\CoreModuleServiceProvider` with Concord module registration, model bindings, and route loading
- [ ] T003 Register `Webkul\ChannelConnector\Providers\ModuleServiceProvider` in `config/concord.php`
- [ ] T004 [P] Create `Config/acl.php` with 12 web ACL permissions: channel_connector.connectors.(view|create|edit|delete), channel_connector.mappings.(view|edit), channel_connector.sync.(view|create), channel_connector.conflicts.(view|edit), channel_connector.webhooks.(view|manage)
- [ ] T005 [P] Create `Config/api-acl.php` mirroring the same 12 permissions for API guard
- [ ] T006 [P] Create `Config/menu.php` adding "Integrations > Channel Connectors" to admin sidebar under the settings section
- [ ] T007 [P] Create `Resources/lang/en_US/app.php` with complete translation keys for connectors, mappings, sync, conflicts, and webhooks sections per data-model.md translation key structure
- [ ] T008 [P] Scaffold translation files for remaining 32 locales at `Resources/lang/{locale}/app.php` (ar_AE, fr_FR, de_DE, es_ES, ja_JP, zh_CN, etc.) copying en_US as initial fallback content

**Checkpoint**: Package registered in Concord, ACL defined, translation files scaffolded. `php artisan config:clear` succeeds.

---

## Phase 2: Foundational (Models, Migrations, Contracts, Base Infrastructure)

**Purpose**: Core models, migrations, repositories, contracts, events, and base adapter class that ALL user stories depend on

**CRITICAL**: No user story work can begin until this phase is complete

### Contracts (all parallelizable)

- [ ] T009 [P] Create `Contracts/ChannelConnector.php` interface at `packages/Webkul/ChannelConnector/src/Contracts/ChannelConnector.php`
- [ ] T010 [P] Create `Contracts/ChannelFieldMapping.php` interface at `packages/Webkul/ChannelConnector/src/Contracts/ChannelFieldMapping.php`
- [ ] T011 [P] Create `Contracts/ChannelSyncJob.php` interface at `packages/Webkul/ChannelConnector/src/Contracts/ChannelSyncJob.php`
- [ ] T012 [P] Create `Contracts/ProductChannelMapping.php` interface at `packages/Webkul/ChannelConnector/src/Contracts/ProductChannelMapping.php`
- [ ] T013 [P] Create `Contracts/ChannelSyncConflict.php` interface at `packages/Webkul/ChannelConnector/src/Contracts/ChannelSyncConflict.php`
- [ ] T014 [P] Create `Contracts/ChannelAdapterContract.php` interface with 13 methods: testConnection, syncProduct, syncProducts, fetchProduct, deleteProduct, getChannelFields, getSupportedLocales, getSupportedCurrencies, isRtlLocale, registerWebhooks, verifyWebhook, refreshCredentials, getRateLimitConfig per contracts/adapter-interface.md

### Migrations (Wave 8, sequential due to FK dependencies)

- [ ] T015 Create migration `Database/Migrations/2026_02_14_000001_create_channel_connectors_table.php` with tenant_id (BIGINT UNSIGNED NOT NULL FK), code, name, channel_type, credentials (TEXT encrypted), settings (JSON), status, last_synced_at; indexes: (tenant_id, code) UNIQUE, (tenant_id, channel_type), (tenant_id, id), (status)
- [ ] T016 Create migration `Database/Migrations/2026_02_14_000002_create_channel_field_mappings_table.php` with tenant_id FK, channel_connector_id FK, unopim_attribute_code, channel_field, direction, transformation (JSON), locale_mapping (JSON), sort_order; indexes: (tenant_id, id), (channel_connector_id, unopim_attribute_code, channel_field) UNIQUE
- [ ] T017 Create migration `Database/Migrations/2026_02_14_000003_create_channel_sync_jobs_table.php` with tenant_id FK, channel_connector_id FK, job_id (UUID), status, sync_type, total/synced/failed_products, error_summary (JSON), retry_of_id (self-ref FK), started_at, completed_at; indexes: (tenant_id, id), (tenant_id, status), (tenant_id, job_id) UNIQUE, (channel_connector_id, created_at)
- [ ] T018 Create migration `Database/Migrations/2026_02_14_000004_create_product_channel_mappings_table.php` with tenant_id FK, channel_connector_id FK, product_id FK, external_id, external_variant_id, entity_type, sync_status, last_synced_at, data_hash, meta (JSON); indexes: (tenant_id, id), (channel_connector_id, product_id, entity_type) UNIQUE, (channel_connector_id, external_id), (sync_status), (product_id)
- [ ] T019 Create migration `Database/Migrations/2026_02_14_000005_create_channel_sync_conflicts_table.php` with tenant_id FK, channel_connector_id FK, channel_sync_job_id FK, product_id FK, conflict_type, conflicting_fields (JSON), pim_modified_at, channel_modified_at, resolution_status, resolution_details (JSON), resolved_by FK (admins), resolved_at; indexes: (tenant_id, id), (tenant_id, resolution_status), per data-model.md

### Models (all parallelizable — depend on contracts T009–T013)

- [ ] T020 [P] Create `Models/ChannelConnector.php` implementing ChannelConnector contract; traits: BelongsToTenant (FIRST), HistoryTrait, HasFactory; casts: credentials → encrypted array, settings → array; fillable: code, name, channel_type, credentials, settings, status, last_synced_at; relationships: fieldMappings(), syncJobs(), productMappings()
- [ ] T021 [P] Create `Models/ChannelFieldMapping.php` implementing ChannelFieldMapping contract; traits: BelongsToTenant (FIRST); casts: transformation → array, locale_mapping → array; fillable per data-model.md; relationship: connector()
- [ ] T022 [P] Create `Models/ChannelSyncJob.php` implementing ChannelSyncJob contract; traits: BelongsToTenant (FIRST); casts: error_summary → array; fillable per data-model.md; relationships: connector(), retryOf(), retries(), conflicts()
- [ ] T023 [P] Create `Models/ProductChannelMapping.php` implementing ProductChannelMapping contract; traits: BelongsToTenant (FIRST); casts: meta → array; fillable per data-model.md; relationships: connector(), product()
- [ ] T024 [P] Create `Models/ChannelSyncConflict.php` implementing ChannelSyncConflict contract; traits: BelongsToTenant (FIRST); casts: conflicting_fields → array, resolution_details → array; fillable per data-model.md; relationships: connector(), syncJob(), product(), resolvedBy()

### Repositories (all parallelizable — depend on models T020–T024)

- [ ] T025 [P] Create `Repositories/ChannelConnectorRepository.php` extending `Webkul\Core\Eloquent\Repository`, declare model() returning ChannelConnector contract
- [ ] T026 [P] Create `Repositories/ChannelFieldMappingRepository.php` extending `Webkul\Core\Eloquent\Repository`, declare model() returning ChannelFieldMapping contract
- [ ] T027 [P] Create `Repositories/ChannelSyncJobRepository.php` extending `Webkul\Core\Eloquent\Repository`, declare model() returning ChannelSyncJob contract
- [ ] T028 [P] Create `Repositories/ProductChannelMappingRepository.php` extending `Webkul\Core\Eloquent\Repository`, declare model() returning ProductChannelMapping contract
- [ ] T029 [P] Create `Repositories/ChannelSyncConflictRepository.php` extending `Webkul\Core\Eloquent\Repository`, declare model() returning ChannelSyncConflict contract

### Base Adapter & Value Objects

- [ ] T030 Create `Adapters/AbstractChannelAdapter.php` implementing ChannelAdapterContract with: RTL_LOCALES constant array (ar_AE, he_IL, fa_IR, ur_PK), isRtlLocale() method, abstract methods for channel-specific implementations, rate limit throttle helper using Laravel RateLimiter
- [ ] T031 [P] Create value objects in `ValueObjects/`: ConnectionResult.php (success, message, channelInfo, errors), SyncResult.php (success, externalId, action, errors, dataHash), BatchSyncResult.php (totalProcessed, successCount, failedCount, skippedCount, results, errors), RateLimitConfig.php (requestsPerSecond, requestsPerMinute, costPerQuery, costPerMutation, maxCostPerSecond)

### Events

- [ ] T032 [P] Create event classes in `Events/` for 14 event pairs: ConnectorCreating/ConnectorCreated, ConnectorUpdating/ConnectorUpdated, ConnectorDeleting/ConnectorDeleted, SyncStarting/SyncStarted, SyncProductSyncing/SyncProductSynced, SyncCompleting/SyncCompleted, SyncFailing/SyncFailed, ConflictDetected, ConflictResolved, WebhookReceived — each following `channel.{entity}.{action}.{before|after}` naming

### Routes Structure

- [ ] T033 [P] Create `Routes/admin-routes.php` with route group under `admin/integrations/channel-connectors` prefix, middleware: ['web', 'admin'], with placeholder resource routes for connectors, mappings, sync, conflicts
- [ ] T034 [P] Create `Routes/api-routes.php` with route group under `v1/rest/channel-connectors` prefix, middleware: ['api', 'auth:api', 'api.scope', 'accept.json', 'request.locale'], with placeholder resource routes per contracts/channel-connector-api.md

### Register Bindings in ServiceProvider

- [ ] T035 Update `Providers/ModuleServiceProvider.php` to bind all 5 model contracts to implementations via Concord's `$this->app->concord->registerModel()`, register event listeners, load routes, load translations from `Resources/lang`

**Checkpoint**: `php artisan migrate` runs successfully. All 5 tables created with tenant_id columns. `php artisan route:list` shows channel connector routes. Models instantiable and tenant-scoped.

---

## Phase 3: User Story 1 — Connect a Sales Channel (Priority: P1) MVP

**Goal**: Admin can create/edit/delete channel connectors for Shopify, Salla, and Easy Orders with credential validation and connection testing

**Independent Test**: Create a connector with test credentials → test connection succeeds → connector appears in list → edit/delete works

### Tests for User Story 1

- [ ] T036 [P] [US1] Create Pest test `tests/Feature/ChannelConnector/ConnectorCrudTest.php` covering: create connector with valid data, validation errors on invalid data, update connector, delete connector, list connectors with pagination and filters
- [ ] T037 [P] [US1] Create Pest test `tests/Feature/ChannelConnector/ConnectionTestTest.php` covering: test connection success with mock adapter, test connection failure with invalid credentials, OAuth2 flow initiation for Salla
- [ ] T038 [P] [US1] Create Pest test `tests/Feature/ChannelConnector/ConnectorApiTest.php` covering: API CRUD endpoints with auth, ACL permission enforcement, credentials never exposed in responses
- [ ] T039 [P] [US1] Create Pest test `tests/Feature/ChannelConnector/TenantIsolationTest.php` covering: connectors scoped to tenant, cross-tenant access denied, tenant-scoped unique code constraint

### Implementation for User Story 1

- [ ] T040 [P] [US1] Create `Http/Requests/ConnectorRequest.php` with validation rules: code (required, slug, unique per tenant via Rule::unique()->where('tenant_id')), name (required, max:255), channel_type (required, in:shopify,salla,easy_orders), credentials (required, valid JSON), status (in:connected,disconnected,error)
- [ ] T041 [P] [US1] Create Shopify adapter at `packages/Webkul/Shopify/src/Adapters/ShopifyAdapter.php` extending AbstractChannelAdapter — implement testConnection() using existing GraphQL client to query shop info, implement getSupportedLocales() via shop.locales query, getRateLimitConfig() returning cost-based config (50 points/sec)
- [ ] T042 [P] [US1] Create Salla adapter at `packages/Webkul/Salla/src/Adapters/SallaAdapter.php` extending AbstractChannelAdapter — implement testConnection() via GET /products (limit 1) with OAuth2 token, implement refreshCredentials() using salla/laravel-starter-kit token refresh, getRateLimitConfig() returning plan-dependent per-minute config
- [ ] T043 [P] [US1] Create Easy Orders adapter stub at `packages/Webkul/EasyOrders/src/Adapters/EasyOrdersAdapter.php` extending AbstractChannelAdapter — implement testConnection() with API key auth, stub remaining methods with NotImplementedException pending API docs
- [ ] T044 [US1] Create adapter factory/resolver service in `Services/AdapterResolver.php` that resolves ChannelAdapterContract from channel_type string, inject via ServiceProvider binding; decrypts credentials from ChannelConnector model before passing to adapter
- [ ] T045 [US1] Create `Http/Controllers/Admin/ConnectorController.php` with index (list with DataGrid), create, store, edit, update, destroy actions; dispatch before/after events; use ConnectorRequest for validation; use ChannelConnectorRepository for CRUD; credentials encrypted before storage
- [ ] T046 [US1] Create `Http/Controllers/Admin/ConnectionTestController.php` with test() action that resolves adapter via AdapterResolver, calls testConnection(), returns JSON result
- [ ] T047 [US1] Create `Http/Controllers/Api/ConnectorApiController.php` with index, show, store, update, destroy actions per contracts/channel-connector-api.md; credentials NEVER included in responses; ACL checks via middleware
- [ ] T048 [US1] Create `Http/Controllers/Api/ConnectionTestApiController.php` with test() endpoint returning ConnectionResult as JSON per API contract
- [ ] T049 [US1] Create `DataGrids/ConnectorDataGrid.php` extending `Webkul\DataGrid\DataGrid` with columns: code, name, channel_type, status, last_synced_at; filters: channel_type, status; mass actions: delete
- [ ] T050 [US1] Create admin Blade views at `Resources/views/admin/connectors/`: index.blade.php (DataGrid with create button), create.blade.php (form with channel type selector, credential fields per type, test connection button), edit.blade.php (same form pre-filled); use `<x-admin::*>` components, `@lang()` for all strings, dark mode variants
- [ ] T051 [US1] Create Vue.js 3 component for connection test button at `Resources/views/admin/connectors/components/test-connection.blade.php` — async POST to test endpoint, show success/failure toast, display channel info (store name, product count, supported locales) on success
- [ ] T052 [US1] Create Salla OAuth2 callback routes and controller at `Http/Controllers/Admin/SallaOAuthController.php` with redirect() and callback() methods; store access/refresh tokens encrypted in connector credentials; handle token refresh failures gracefully
- [ ] T053 [US1] Register Shopify, Salla, EasyOrders adapter packages: create minimal `ModuleServiceProvider` for each at `packages/Webkul/Salla/src/Providers/ModuleServiceProvider.php` and `packages/Webkul/EasyOrders/src/Providers/ModuleServiceProvider.php`, register in `config/concord.php`

**Checkpoint**: Admin can create connectors for all 3 channel types, test connections, view connector list. API CRUD works with proper ACL. Tenant isolation verified. Credentials encrypted and never exposed.

---

## Phase 4: User Story 2 — Map Product Fields to Channel (Priority: P2)

**Goal**: Admin can define how UnoPim attributes map to channel fields, including locale mapping for multi-language sync

**Independent Test**: Open a connected connector → configure field mappings with locale pairs → save → mappings persist correctly → preview shows correct field translation

### Tests for User Story 2

- [ ] T054 [P] [US2] Create Pest test `tests/Feature/ChannelConnector/FieldMappingCrudTest.php` covering: create mappings, update mappings (bulk save), delete mapping, validation of duplicate mapping (same attribute+field combo), locale_mapping JSON validation
- [ ] T055 [P] [US2] Create Pest test `tests/Feature/ChannelConnector/MappingApiTest.php` covering: GET /mappings returns list, PUT /mappings bulk save, ACL enforcement, mapping with locale_mapping JSON persists correctly
- [ ] T056 [P] [US2] Create Pest test `tests/Feature/ChannelConnector/AutoSuggestMappingTest.php` covering: auto-suggest returns common field pairs (sku→sku, name→title, description→descriptionHtml, price→price), type compatibility warnings

### Implementation for User Story 2

- [ ] T057 [US2] Create `Services/MappingService.php` with methods: getAutoSuggestedMappings(ChannelConnector) — queries adapter's getChannelFields() and matches against UnoPim attribute codes by name similarity and type; validateMappings(array) — checks attribute codes exist, field types compatible; getLocaleMapping(ChannelConnector) — returns configured locale pairs from connector settings
- [ ] T058 [US2] Create `Http/Requests/MappingRequest.php` with validation: mappings array required, each mapping has unopim_attribute_code (required, exists in attributes), channel_field (required), direction (required, in:export,import,both), locale_mapping (nullable, valid JSON with valid locale codes checked against locales table)
- [ ] T059 [US2] Create `Http/Controllers/Admin/MappingController.php` with index (show mapping editor), store (bulk save mappings — delete old, insert new), preview (sample product mapped through current config); use MappingService for auto-suggestions
- [ ] T060 [US2] Create `Http/Controllers/Api/MappingApiController.php` with index and bulkStore per API contract; ACL: channel_connector.mappings.view and .edit
- [ ] T061 [US2] Create admin Blade views at `Resources/views/admin/mappings/`: index.blade.php — split view with UnoPim attributes on left, channel fields on right, drag-and-drop or dropdown mapping; locale mapping section showing UnoPim locales mapped to channel locales; "Translatable" badge on locale-specific attributes; direction selector per mapping
- [ ] T062 [US2] Create Vue.js 3 component for mapping editor at `Resources/views/admin/mappings/components/mapping-editor.blade.php` — fetches channel fields via adapter, fetches UnoPim attributes via API, enables pairing with auto-suggest pre-fill, locale mapping configuration with channel's supported locales from testConnection result
- [ ] T063 [US2] Create Vue.js 3 component for mapping preview at `Resources/views/admin/mappings/components/mapping-preview.blade.php` — select a sample product, show side-by-side: UnoPim values (per locale) vs mapped channel values (per locale), highlighting any type mismatches or missing required fields

**Checkpoint**: Admin can configure field mappings with locale mapping for any connector. Auto-suggest works for common fields. Preview shows correct per-locale mapped values.

---

## Phase 5: User Story 3 — Sync Products to a Channel (Priority: P3)

**Goal**: Admin can trigger full, incremental, or single-product sync jobs that push product data per-locale to the connected channel via background queue

**Independent Test**: Trigger incremental sync → job appears as pending → progresses to running → products appear in channel → job completes with correct counts

### Tests for User Story 3

- [ ] T064 [P] [US3] Create Pest test `tests/Feature/ChannelConnector/SyncTriggerTest.php` covering: trigger full/incremental/single sync via admin and API, validation of sync_type, duplicate running job prevention (CHN-090), optional locale filter on sync trigger
- [ ] T065 [P] [US3] Create Pest test `tests/Feature/ChannelConnector/SyncEngineTest.php` covering: per-locale value extraction using Attribute::getValueFromProductValues(), locale-keyed payload construction, common vs locale_specific vs channel_specific vs channel_locale_specific paths, hash computation across all locale variants
- [ ] T066 [P] [US3] Create Pest test `tests/Feature/ChannelConnector/ProcessSyncJobTest.php` covering: job dispatches to correct tenant queue (tenant-{id}-sync), TenantAwareJob trait serializes tenant_id, state transitions (pending→running→completed, pending→running→failed), progress counter updates, rate limit compliance
- [ ] T067 [P] [US3] Create Pest test `tests/Feature/ChannelConnector/ProductChannelMappingTest.php` covering: mapping created on first sync, external_id stored, data_hash updated, sync_status transitions, hash change detection for incremental sync

### Implementation for User Story 3

- [ ] T068 [US3] Create `Services/SyncEngine.php` with methods: prepareSyncPayload(Product, Collection $mappings) — iterates locale pairs from each mapping's locale_mapping, calls Attribute::getValueFromProductValues() per attribute per locale, builds locale-keyed payload structure {"locales": {"en": {...}, "ar": {...}}, "common": {...}}; computeDataHash(array $payload) — sorts keys deterministically, JSON-encodes, MD5; detectChanges(Product, ProductChannelMapping) — compares current hash vs stored hash; applyRtlTransformation(string $value, string $locale, ChannelAdapterContract $adapter) — strips bidi markers for non-RTL channels
- [ ] T069 [US3] Create `Jobs/ProcessSyncJob.php` implementing ShouldQueue with TenantAwareJob trait (FIRST); handle() method: load connector + mappings + adapter, iterate products in batches (100), for each product call SyncEngine::prepareSyncPayload(), call adapter::syncProduct(), create/update ProductChannelMapping with external_id and data_hash, update ChannelSyncJob progress counters (synced_products, failed_products), handle rate limits via adapter::getRateLimitConfig() with sleep/throttle, catch exceptions per product (continue batch, log to error_summary), transition job status via state machine
- [ ] T070 [US3] Create `Services/SyncJobManager.php` with methods: triggerSync(ChannelConnector, string $syncType, array $productCodes = [], array $locales = []) — validates no duplicate running job, creates ChannelSyncJob record (status=pending), resolves product list (full=all, incremental=changed since last_synced_at, single=by codes), dispatches ProcessSyncJob to tenant queue; getJobStatus(string $jobId) — returns current state with progress
- [ ] T071 [US3] Create `Http/Controllers/Admin/SyncController.php` with trigger() action (form POST with sync_type, optional product_codes), show() action (job detail with progress), dispatches via SyncJobManager; fires channel.sync.start.before/after events
- [ ] T072 [US3] Create `Http/Controllers/Api/SyncApiController.php` with trigger(), show(), index() per API contract; ACL: channel_connector.sync.create and .view; response includes job_id, status, estimated_products, queue_position; optional locales array in request body
- [ ] T073 [US3] Create admin Blade views at `Resources/views/admin/sync/`: trigger.blade.php — sync type selector (full/incremental/single), locale filter checkboxes for mapped locales, start sync button with confirmation for full sync; show.blade.php — progress bar, counters (total/synced/failed), status badge, error list, real-time updates via polling
- [ ] T074 [US3] Create product edit page integration — add "Sync to Channel" button in product edit view via event listener on `catalog.product.edit.form.after` that renders a Vue component showing connected channels with sync status; clicking triggers single-product sync via API

**Checkpoint**: Admin can trigger all 3 sync types. Jobs process in background via tenant-scoped queues. Products synced per-locale with correct data extraction. Progress tracked in real time. ProductChannelMapping records created with data hashes.

---

## Phase 6: User Story 4 — Monitor and Manage Sync Jobs (Priority: P4)

**Goal**: Admin has a centralized dashboard showing all sync jobs across channels with filtering, detail views, and retry capability

**Independent Test**: Trigger several syncs → dashboard shows all jobs → filter by channel/status → click failed job → see per-product errors → retry works

### Tests for User Story 4

- [ ] T075 [P] [US4] Create Pest test `tests/Feature/ChannelConnector/SyncDashboardTest.php` covering: dashboard loads with paginated jobs, filtering by channel_type/status/date range, job detail shows progress and error summary
- [ ] T076 [P] [US4] Create Pest test `tests/Feature/ChannelConnector/RetryJobTest.php` covering: retry creates new job linked to original (retry_of_id), original transitions to retrying, retry job contains only failed products, retry API endpoint works with ACL

### Implementation for User Story 4

- [ ] T077 [US4] Create `DataGrids/SyncJobDataGrid.php` extending DataGrid with columns: connector name (joined), sync_type, status (with colored badges), total_products, synced_products, failed_products, started_at, duration (computed); filters: connector, status, sync_type, date range; sortable by started_at
- [ ] T078 [US4] Extend `Services/SyncJobManager.php` with retryFailedProducts(ChannelSyncJob $originalJob) — creates new ChannelSyncJob with retry_of_id pointing to original, collects failed product IDs from original's error_summary, transitions original to retrying status, dispatches ProcessSyncJob for failed products only
- [ ] T079 [US4] Create `Http/Controllers/Admin/SyncDashboardController.php` with index() — SyncJobDataGrid across all connectors, show() — single job detail with error_summary expanded, retry() — calls SyncJobManager::retryFailedProducts()
- [ ] T080 [US4] Create `Http/Controllers/Api/SyncJobApiController.php` extending existing SyncApiController with listAllJobs() across all connectors (for dashboard API), retry endpoint per API contract
- [ ] T081 [US4] Create admin Blade views at `Resources/views/admin/dashboard/`: index.blade.php — DataGrid with sync job list, status badges (pending=gray, running=blue, completed=green, failed=red, retrying=yellow), channel name column, filter controls; show.blade.php — job detail with progress section, error table with per-product SKU/error_code/message, retry button (visible only for failed jobs)
- [ ] T082 [US4] Create Vue.js 3 component for real-time sync progress at `Resources/views/admin/dashboard/components/sync-progress.blade.php` — polls job status every 3 seconds while running, updates progress bar and counters, stops polling on completed/failed, shows estimated time remaining based on current rate

**Checkpoint**: Dashboard shows all sync jobs across channels. Filtering works by channel, status, and date. Failed job detail shows per-product errors. Retry creates new job for failed products only. Real-time progress updates during active syncs.

---

## Phase 7: User Story 5 — Resolve Sync Conflicts (Priority: P5)

**Goal**: System detects conflicts when product modified in both PIM and channel, presents per-locale field diffs, admin resolves via PIM Wins / Channel Wins / Manual Merge

**Independent Test**: Modify product in PIM and channel → trigger sync → conflict detected → view per-locale diff → resolve → resolution applied correctly

### Tests for User Story 5

- [ ] T083 [P] [US5] Create Pest test `tests/Feature/ChannelConnector/ConflictDetectionTest.php` covering: conflict detected when both PIM hash and channel hash differ from stored hash, no conflict when only PIM changed (normal sync), no conflict when hashes match, per-locale hash includes all locale variants
- [ ] T084 [P] [US5] Create Pest test `tests/Feature/ChannelConnector/ConflictResolutionTest.php` covering: pim_wins pushes PIM values to channel, channel_wins pulls channel values to PIM via Attribute::setProductValue(), merged applies per-field overrides, dismissed skips without changes, resolved_by and resolved_at set correctly
- [ ] T085 [P] [US5] Create Pest test `tests/Feature/ChannelConnector/ConflictApiTest.php` covering: list conflicts with filters, get conflict detail with per-locale diff structure, resolve conflict via API, ACL enforcement

### Implementation for User Story 5

- [ ] T086 [US5] Create `Services/ConflictResolver.php` with methods: detectConflict(Product, ProductChannelMapping, ChannelAdapterContract) — fetches current channel data via adapter::fetchProduct(), computes channel hash, compares against stored data_hash, if both PIM and channel differ → creates ChannelSyncConflict with per-locale conflicting_fields structure; resolveConflict(ChannelSyncConflict, string $resolution, ?array $fieldOverrides) — applies resolution: pim_wins → re-sync PIM values to channel, channel_wins → update PIM product values via Attribute::setProductValue() per locale, merged → apply per-field overrides; buildConflictingFields(array $pimValues, array $channelValues, Collection $mappings) — generates per-locale diff structure with is_locale_specific flag per field
- [ ] T087 [US5] Integrate conflict detection into `Jobs/ProcessSyncJob.php` — before syncing each product, if ProductChannelMapping exists with data_hash, call ConflictResolver::detectConflict(); if conflict found, mark product as conflicted in sync results, create ChannelSyncConflict record, skip sync for that product, fire channel.conflict.detected.after event
- [ ] T088 [US5] Create `DataGrids/ConflictDataGrid.php` extending DataGrid with columns: product SKU (joined), connector name (joined), conflict_type, resolution_status (with badges), created_at; filters: resolution_status, connector, conflict_type
- [ ] T089 [US5] Create `Http/Controllers/Admin/ConflictController.php` with index() — ConflictDataGrid, show() — conflict detail with per-locale field diff, resolve() — calls ConflictResolver::resolveConflict(); fires channel.conflict.resolved.after event
- [ ] T090 [US5] Create `Http/Controllers/Api/ConflictApiController.php` with index, show, resolve per API contract; show response includes per-locale conflicting_fields with pim_value/channel_value as locale-keyed objects for translatable fields, scalar for non-translatable
- [ ] T091 [US5] Create admin Blade views at `Resources/views/admin/conflicts/`: index.blade.php — ConflictDataGrid; show.blade.php — side-by-side diff view: left = PIM values (per locale tabs), right = Channel values (per locale tabs), per-field radio: PIM Wins / Channel Wins, bulk resolution buttons (All PIM Wins, All Channel Wins), submit resolve button
- [ ] T092 [US5] Add default conflict strategy to ChannelConnector settings — update connector create/edit forms to include conflict_strategy dropdown (always_ask, pim_always_wins, channel_always_wins); integrate into ProcessSyncJob to auto-resolve conflicts when strategy is not always_ask

**Checkpoint**: Conflicts detected during sync when both PIM and channel changed. Per-locale diffs displayed correctly. Resolution applies changes to both PIM and channel. Default strategy auto-resolves when configured.

---

## Phase 8: User Story 6 — Receive Inbound Channel Webhooks (Priority: P6)

**Goal**: System receives and processes inbound webhooks from channels (product CRUD events), applying configured strategy (auto-update PIM, flag for review, ignore)

**Independent Test**: Configure webhook subscription → trigger product update in channel → webhook received → HMAC verified → processed according to strategy

### Tests for User Story 6

- [ ] T093 [P] [US6] Create Pest test `tests/Feature/ChannelConnector/WebhookVerificationTest.php` covering: valid HMAC signature accepted, invalid signature rejected (CHN-050), missing signature rejected, payload parse errors handled (CHN-051)
- [ ] T094 [P] [US6] Create Pest test `tests/Feature/ChannelConnector/WebhookProcessingTest.php` covering: product.updated event with auto-update strategy updates PIM product, product.updated event with flag-for-review strategy creates conflict record, product.deleted event handled, unsupported event type returns CHN-052, webhook acknowledged within 2 seconds

### Implementation for User Story 6

- [ ] T095 [US6] Create `Http/Controllers/WebhookController.php` (public route, no auth middleware) with receive() method — resolves connector from webhook URL token, calls adapter::verifyWebhook() for HMAC validation, parses payload, dispatches ProcessWebhookJob to queue, returns 200 immediately (within 2 seconds per SC-008); route: POST /webhooks/channel-connectors/{token}
- [ ] T096 [US6] Create `Jobs/ProcessWebhookJob.php` implementing ShouldQueue with TenantAwareJob trait — handle() method: resolve event type (product.created/updated/deleted), map channel fields back to UnoPim attributes using reverse field mappings per locale, apply inbound strategy from connector settings (auto_update → update PIM product via Attribute::setProductValue() per locale, flag_for_review → create ChannelSyncConflict, ignore → log and skip); fire channel.webhook.received.after event; record change source as "channel_sync" in history
- [ ] T097 [US6] Add webhook registration to connector setup — extend ConnectorController store/update to call adapter::registerWebhooks() with configured events and callback URL; add webhook_token (random UUID) to connector settings for URL-based connector identification
- [ ] T098 [US6] Create `Services/WebhookService.php` with methods: generateWebhookToken() — creates unique token per connector, registerWebhooks(ChannelConnector) — calls adapter with events list and callback URL, unregisterWebhooks(ChannelConnector) — calls adapter to remove webhook subscriptions on connector delete
- [ ] T099 [US6] Add inbound strategy config to connector settings — update connector create/edit forms to include inbound_strategy dropdown (auto_update, flag_for_review, ignore) and webhook events checklist (product.created, product.updated, product.deleted); update Blade views and API contract
- [ ] T100 [US6] Create admin Blade view at `Resources/views/admin/webhooks/`: index.blade.php — show registered webhooks per connector, webhook URL with copy button, event subscription toggles, recent webhook log (last 50 received); accessible from connector detail page "Webhooks" tab

**Checkpoint**: Webhooks registered on connector creation. Inbound events verified (HMAC) and processed per strategy. Auto-update modifies PIM per-locale. Flag-for-review creates conflict records. Webhook acknowledged quickly.

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Cross-story improvements, security hardening, and final validation

- [ ] T101 [P] Add comprehensive logging throughout SyncEngine, ConflictResolver, and all adapters using Laravel Log facade with channel-specific context (connector_id, job_id, product_id); sanitize credentials from log payloads per FR-023
- [ ] T102 [P] Implement cache layer in SyncJobManager and MappingService using TenantCache::key() for: connector field cache (5 min TTL), mapping configuration cache (invalidated on save), sync job progress cache (for dashboard polling)
- [ ] T103 [P] Add history tracking — ensure ChannelConnector model's HistoryTrait records create/update/delete with proper presenters for status changes and settings modifications; create `Presenters/ConnectorHistoryPresenter.php`
- [ ] T104 [P] Security audit — verify: credentials encrypted at rest (cast: encrypted array), credentials never in API responses or logs, webhook HMAC validation on all inbound requests, ACL enforced on all 12 permissions (web + API), rate limiting active on API endpoints, SecureHeaders middleware not bypassed, XSS prevention on all user inputs via Blade escaping and HtmlPurifier on API
- [ ] T105 [P] Run Laravel Pint on all new PHP files in packages/Webkul/ChannelConnector/, packages/Webkul/Salla/, packages/Webkul/EasyOrders/ to ensure PSR-12 compliance per Constitution Development Workflow
- [ ] T106 [P] Validate dark mode support on all admin Blade views — ensure every UI element uses `dark:` Tailwind variants per Principle X; test with dark mode toggle
- [ ] T107 Run full Pest test suite: `./vendor/bin/pest --parallel` — ensure all new tests pass and no existing tests regressed
- [ ] T108 Run quickstart.md validation — follow all 7 steps end-to-end with a test Shopify store to verify the documented workflow matches actual behavior; document any deviations

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational — FIRST story to implement (MVP)
- **User Story 2 (Phase 4)**: Depends on Foundational + US1 connectors (needs a connected channel)
- **User Story 3 (Phase 5)**: Depends on Foundational + US1 (connector) + US2 (mappings)
- **User Story 4 (Phase 6)**: Depends on Foundational + US3 (needs sync jobs to exist)
- **User Story 5 (Phase 7)**: Depends on Foundational + US3 (conflict detection integrated into sync)
- **User Story 6 (Phase 8)**: Depends on Foundational + US1 (connector) + US2 (reverse mappings)
- **Polish (Phase 9)**: Depends on all desired user stories being complete

### User Story Dependencies

```text
Phase 1: Setup
    ↓
Phase 2: Foundational (BLOCKS ALL)
    ↓
Phase 3: US1 — Connect Channel (MVP) ←── start here
    ↓
Phase 4: US2 — Map Fields (needs US1 connector)
    ↓                          ↘
Phase 5: US3 — Sync Products     Phase 8: US6 — Webhooks
    ↓           (needs US1+US2)       (needs US1+US2)
Phase 6: US4 — Monitor Dashboard
    ↓           (needs US3 jobs)
Phase 7: US5 — Resolve Conflicts
                (needs US3 sync)
    ↓
Phase 9: Polish (all stories)
```

### Within Each User Story

1. Tests written FIRST — verify they FAIL before implementation
2. Models/contracts before services
3. Services before controllers
4. Controllers before views
5. Core logic before integrations

### Parallel Opportunities

**Phase 1**: T004, T005, T006, T007, T008 all in parallel
**Phase 2**: T009–T014 contracts in parallel → T015–T019 migrations sequential → T020–T024 models in parallel → T025–T029 repositories in parallel → T030–T034 base infra in parallel
**Phase 3 (US1)**: T036–T039 tests in parallel → T040–T043 adapters in parallel → T045–T053 sequential (controller → views → OAuth)
**Phase 4 (US2)**: T054–T056 tests in parallel → T061–T063 views in parallel
**Phase 5 (US3)**: T064–T067 tests in parallel
**Phase 9**: T101–T106 all in parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests in parallel:
Task: "Pest test ConnectorCrudTest.php" [T036]
Task: "Pest test ConnectionTestTest.php" [T037]
Task: "Pest test ConnectorApiTest.php" [T038]
Task: "Pest test TenantIsolationTest.php" [T039]

# Launch all adapters in parallel (different packages):
Task: "ShopifyAdapter in packages/Webkul/Shopify/" [T041]
Task: "SallaAdapter in packages/Webkul/Salla/" [T042]
Task: "EasyOrdersAdapter in packages/Webkul/EasyOrders/" [T043]

# Launch form request + adapter resolver in parallel:
Task: "ConnectorRequest.php" [T040]
Task: "AdapterResolver.php" [T044]
```

## Parallel Example: Foundational Phase

```bash
# Launch ALL 6 contracts in parallel:
Task: "ChannelConnector contract" [T009]
Task: "ChannelFieldMapping contract" [T010]
Task: "ChannelSyncJob contract" [T011]
Task: "ProductChannelMapping contract" [T012]
Task: "ChannelSyncConflict contract" [T013]
Task: "ChannelAdapterContract" [T014]

# After contracts, launch ALL 5 models in parallel:
Task: "ChannelConnector model" [T020]
Task: "ChannelFieldMapping model" [T021]
Task: "ChannelSyncJob model" [T022]
Task: "ProductChannelMapping model" [T023]
Task: "ChannelSyncConflict model" [T024]

# After models, launch ALL 5 repositories in parallel:
Task: "ChannelConnectorRepository" [T025]
Task: "ChannelFieldMappingRepository" [T026]
Task: "ChannelSyncJobRepository" [T027]
Task: "ProductChannelMappingRepository" [T028]
Task: "ChannelSyncConflictRepository" [T029]
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T008)
2. Complete Phase 2: Foundational (T009–T035)
3. Complete Phase 3: User Story 1 (T036–T053)
4. **STOP and VALIDATE**: Admin can connect Shopify/Salla/EasyOrders, test connections, manage connectors via web + API
5. Deploy/demo if ready — this alone delivers channel connectivity

### Incremental Delivery

1. Setup + Foundational → Package scaffold ready
2. Add US1 → Channel connection works → Deploy (MVP!)
3. Add US2 → Field mapping + locale mapping works → Deploy
4. Add US3 → Product sync works end-to-end → Deploy (core value!)
5. Add US4 → Monitoring dashboard → Deploy
6. Add US5 → Conflict resolution → Deploy
7. Add US6 → Webhooks for real-time sync → Deploy
8. Polish → Production-ready

### Parallel Team Strategy

With 3 developers after Foundational complete:

1. **Developer A**: US1 (P1 — MVP connector) → US4 (P4 — dashboard)
2. **Developer B**: US2 (P2 — mappings) → US5 (P5 — conflicts)
3. **Developer C**: US3 (P3 — sync engine, after US1+US2 done) → US6 (P6 — webhooks)

---

## Notes

- [P] tasks = different files, no dependencies within that phase
- [Story] label maps task to specific user story for traceability
- All file paths assume `packages/Webkul/ChannelConnector/src/` base unless noted
- Tests use Pest PHP; run with `./vendor/bin/pest --parallel`
- All models use `BelongsToTenant` as FIRST trait (tenant isolation always active)
- All UI strings via `@lang('channel_connector::app.key')` (33 locales)
- All product value access via `Attribute::getValueFromProductValues()` (never direct JSON)
- Credentials encrypted via `encrypted:array` cast (never in responses or logs)
- Commit after each task or logical group
