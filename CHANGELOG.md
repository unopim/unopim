# 3.0.1

## Bug fixes

- Restricted the variant uniqueness check to the axis codes the parent's structure declares, closing an unescaped JSON path.
- Fixed the product edit form locking an axis attribute at the level that owns it.
- Fixed configurable product creation returning a server error on a duplicate SKU.
- Fixed the REST locale, currency and channel endpoints answering an unknown code with 400 instead of 404.
- Fixed bulk edit leaving inherited axis cells blank, resolved from placement rows that axes never carry.
- Fixed bulk edit media cells staying empty until clicked, and locked cells keeping their upload and delete controls.
- Fixed bulk edit columns ignoring the order the attributes were selected in.
- Fixed variant structure effective placements returning in an unstable order.
- Fixed product webhooks losing a slow write and reporting a creation in place of an update.
- Fixed product webhooks omitting a SKU rename, which produced an empty difference.
- Fixed variant groups never announcing themselves as created products.
- Fixed the category export failing when a job carries no `with_media` filter.

## New features

- Added attribute family variant structures to the REST API with full CRUD; only the name and placements are writable, and a referenced structure cannot be deleted.
- Added `effective_placements` to the variant structure payload, reporting the tier that governs every attribute of the family.
- Added variant groups to the configurable products REST API, with a `type` parameter for listing either tier.
- Added a variant-level write guard shared by every save path, so an attribute is writable only at the level that owns it and a colliding axis rename is rejected in the transaction that persists it.
- Added variant-level awareness to bulk edit: ancestor-owned cells are locked and show the owner's value, lower-level cells are locked and empty.

# 3.0.0 — July 31st, 2026

## Bug fixes

- Fixed the Docker installation failing on a fresh clone, where the bind-mounted checkout shadowed the vendor directory baked into the image and the entrypoint called Artisan before installing dependencies, leaving PHP-FPM restarting on a missing autoloader and the rest of the stack waiting behind it.
- Fixed the pre-built image stack serving nothing: it fronted the Apache application image with an Nginx container proxying FastCGI to a port nothing listened on, mounted a volume over the application that prevented a new image tag from ever replacing the code, and required files its own quick start never downloaded.
- Removed the committed OAuth signing keys and added automatic generation of a unique 4096-bit key pair for every installation.
- Protected Magic AI connection testing and model discovery with ACL checks and public-address validation, blocking unauthenticated requests and SSRF to private, loopback, link-local, and metadata endpoints.
- Prevented populated installations from rewriting environment or database configuration when installation markers are missing, and made `public/install.php` inert after installation.
- Ensured unauthenticated REST requests return a JSON `401` response even when the client omits the `Accept: application/json` header.
- Sanitized WYSIWYG values on every admin persistence path for products, variants, categories, and attribute defaults, closing stored-XSS paths while retaining the MIT-licensed TinyMCE release.
- Prevented the image-cache endpoint from resolving files outside the configured media roots.
- Removed password hashes, API tokens, remember tokens, and recovery tokens from administrator audit history and scrubbed existing credential-only audit records.
- Enforced missing permissions on existing Magic AI connection/configuration, product quick-export, and import/export profile actions.
- Fixed sidebar fly-outs closing before submenu items could be selected.
- Fixed whitespace-only password acceptance and kept masked configuration passwords unchanged when unrelated settings are saved.
- Fixed user status updates ignoring the submitted value and restricted role assignment to roles the current administrator may assign.
- Fixed role validation and missing User Management ACL labels.
- Fixed Magic AI active-platform selection, setup-banner refresh, target-locale persistence, prompt entity labels, and prompt action permissions.
- Fixed duplicated form-control and select-handler IDs, multiselect overflow, dropdown placement, and case-insensitive option searching.
- Fixed category-field validation rules being coupled incorrectly, create-modal state, editability flags, and name handling.
- Fixed attribute-family drag assignment duplicating unsaved attributes.
- Fixed family saves that removed an attribute still used as a variant axis.
- Fixed family cloning timing out on large mappings.
- Fixed product option values being indexed with inconsistent scalar types.
- Fixed product media clones pointing at the source product's path.
- Fixed copied/imported media resolving to directories when the incoming value is empty.
- Fixed bulk edit omitting file attributes and shared media previews.
- Fixed export/import transactions committing from `finally` after failures.
- Fixed temporary export files creating directories on the wrong storage disk.
- Fixed fileless import jobs failing when re-run.
- Fixed export filenames ignoring the configured date format and administrator timezone.
- Fixed import/export edit actions being visible without permission.
- Fixed PostgreSQL compatibility across installation, boolean values, JSON queries, upserts, search filters, and table prefixes.
- Fixed notification mail jobs receiving the wrong template-data shape.
- Fixed core configuration values becoming stale with file/database cache stores.
- Fixed theme service state leaking between requests under Octane.
- Fixed Passport token TTL values freezing at long-running worker boot.
- Fixed Elasticsearch index prefixes bypassing configuration cache.
- Fixed notification enablement bypassing configuration cache.
- Fixed installer product images being seeded to the wrong location.
- Fixed product import observers remaining disabled for later work in long-running queue/Octane processes.
- Fixed duplicate SKU validation errors being attached to a hidden field instead of the visible product identifier field.
- Fixed REST category creation accepting a missing, invalid, or duplicate category code.
- Fixed the final-administrator self-delete path not returning a response.
- Fixed invalid category-media uploads not returning a JSON validation error.
- Fixed AI Agent history presenters extending a missing base presenter.

## New features

- Added a dark admin theme with manual light/dark selection and automatic browser-theme detection when no preference has been saved.
- Made the left navigation collapsed by default for users without a saved preference, while preserving each user's later choice.
- Added Remember Me to the redesigned admin login page.
- Added SPA-style AJAX navigation and form submission throughout the admin so users can move between pages and save changes without full-page reloads while retaining browser history.
- Added centralized dirty-state tracking across admin edit forms. A global Save/Discard bar appears only after a change, identifies unsaved sections, restores the original state on discard, and warns before navigation.
- Added locale-aware quick creation for attributes, attribute groups, category fields, attribute families, and association types; the name or label uses the user's catalog locale and generates an editable code automatically.
- Added cross-page “select all matching records” support to DataGrids and mass actions, with bounded server-side ID resolution.
- Added configurable association types with localized labels, status and position controls, custom text/boolean link fields, per-locale values, validation rules, ACL-aware CRUD, DataGrid actions, and protected default association types.
- Added normalized product-association storage with a backward-compatible legacy JSON backfill and dual-write path, preserving existing callers while supporting rich per-link data.
- Added configurable associations to the redesigned product-edit drawer, REST product endpoints, dedicated import/export jobs, and normal product imports/exports.
- Added a responsive product-edit section drawer for categories, associations, passport data, counts, dirty-state tracking, and unsaved-change protection.
- Added two-level attribute-family variant structures with configurable axes, inherited product values, variant-group products, family editors, variant creation, and extensible resolver contracts.
- Added `unopim:variants:strip-redundant` and `unopim:variants:resync` commands for migrating and rebuilding variant trees.
- Added per-user catalog locale and default channel settings, used consistently by account settings, product editing, API behavior, and background work.
- Added product-grid filters for categories, completeness, creation/update dates, product properties, and attribute values, with type-specific operators across database and Elasticsearch grids.
- Added saved product-grid views so users can store and reuse column and filter combinations.
- Added a complete Measurement module with measurement families, units, conversions, precision and decimal strategies, validation, admin pages, ACL, REST endpoints, and `measurement:recalculate`.
- Added a generic Publication framework with config-registered publication types, append-only per-locale versions, dedicated publishing jobs, preview, republish, withdraw, reinstate, redact, public rendering, ETags, tombstones, view analytics, QR carriers, and private document delivery.
- Added enable/disable-controlled Digital Product Passports with product panels, bulk publish actions, admin previews, version history, public cookie-free passport pages, GS1 Digital Link aliases, GTIN validation, auto-publishing, REST lifecycle actions, and feature/channel controls.
- Added admin-editable passport templates that bind to product families and define localized sections, fields, access tiers, required fields, identifier roles, attribute sources, fixed localized values, immutable publication versions, safe private documents, redaction, checksums, concurrency protection, and channel kill switches.
- Added idempotent ESPR and EU Battery Regulation passport presets through `unopim:passport:install-preset`.
- Added a config-driven System Settings hub for appearance, SMTP, IP-restricted debugging, Microsoft SSO, measurement precision, publications, and Product Passport configuration, with live search, ACL-filtered sections, and package extension hooks.
- Added Appearance settings for changing the admin logo and favicon through drag-and-drop media controls.
- Added a System Information page covering the application, server, database, services, and installed packages.
- Added Microsoft SSO for admin authentication with an extensible provider contract, verified tenant domains, allowed-tenant checks, state validation, persisted provider identities, and redirect-URI guidance.
- Added automatic least-privilege robot users for API integrations, blocked their panel login/user-list visibility, migrated existing integrations safely, and added one-time credential reveal, copy controls, and password regeneration.
- Replaced the single webhook configuration with a multi-webhook module supporting multiple URLs, event subscriptions, HMAC delivery, quick creation, scoped delivery logs, ACL controls, legacy-settings migration, and `webhook:logs:prune`.
- Added REST `PATCH` and `DELETE` operations for attributes, attribute groups, attribute families, and category fields, plus `DELETE` operations for attribute and category-field options.
- Added REST create, update, and delete operations for locales, channels, and currencies, including the same in-use and default-record guards as the admin.
- Added REST read/delete operations for scoped product media, category media, and swatch media.
- Added REST Digital Product Passport listing/read, publish, withdraw, reinstate, and redact operations.
- Added Admin API delta synchronization with created/updated date filters, `search_after` cursor pagination, versioned structure caches, ETags, and conditional requests.
- Added import/export jobs for attributes, category fields, configurable associations, attribute groups, attribute families, attribute options, locales, channels, currencies, roles, and users, including localized values and sample files.
- Added product-export filters for channels, locales, currencies, attributes, attribute families, status, completeness, last-N-days/since-last-export/between-date conditions, categories, identifiers, and attribute-value conditions.
- Added product quick export and asynchronous product mass actions for large selections.
- Added a native category tree browser with tree/list presentation, lazy branches, parent reassignment, root promotion, and DataGrid filter integration.
- Added lazy product attribute-group loading with a persistent group sidebar, pagination, unsaved-switch guards, and on-demand field endpoints for large families.
- Added an extensible Resource CRUD kit with base controllers and reusable DataGrid/edit components for package developers.
- Added AJAX password-recovery and reset flows, updated reset-password emails, and modern accessible toast notifications.
- Added an admin Gravatar integration with per-user opt-out and cached avatar proxying.
- Added application-key rotation support through `APP_PREVIOUS_KEYS`.
- Added an AI product-embedding indexing command and channel-aware AI memory/token accounting.
- Added a PostgreSQL Docker profile, shared database wait helper, database-selectable container startup, and production-safe defaults.

## Improvements

- Updated Magic AI to the latest Laravel AI package and improved platform validation, model discovery, target-locale handling, prompts, feedback, and setup guidance.
- Updated account and user editing so password confirmation is required only when changing a password, not when updating non-sensitive profile information.
- Improved existing DataGrid filters with case-insensitive search, active-filter chips, and stable Apply/Clear actions.
- Improved product DataGrid and product-edit performance for large catalogs through bounded queries, lazy attribute groups, reduced filter fan-out, and asynchronous mass actions.
- Improved category pages and product category selection with clearer hierarchy summaries, searchable parent selection, and redesigned navigation.
- Improved Webhook logs with endpoint/event context, payload inspection, access control, filtering, and retention cleanup.
- Improved deployment security with configurable OAuth and REST API rate limits, configurable CORS origins and methods, trusted-host protection, and application debugging disabled by default in production Docker environments.

## Technical Improvements

- Upgraded the application to Laravel 13 and modernized the codebase for PHP 8.4.1, typed framework contracts, current middleware conventions, and Octane-safe scoped services.
- Added PHPStan/Larastan, Rector, Pest impact analysis, parallel test databases, MySQL/PostgreSQL/Elasticsearch coverage, and dedicated static-analysis workflows.
- Rebuilt the Admin API and REST test suite with independent Playwright fixtures and shared integration bootstrapping.
- Replaced offset pagination with keyset pagination for large product exports and completeness collection.
- Added hot-path indexes for products, completeness, attribute-family mappings, and lookup-heavy catalog relations.
- Reduced product export complexity at multi-million-product scale and normalized Elasticsearch date bounds to ISO-8601 UTC.
- Queued product mass delete/status changes above a configurable threshold instead of blocking admin requests.
- Bounded Elasticsearch bulk indexing requests and isolated query-builder state to prevent concurrent filter leakage.
- Removed repeated product queries from bulk updates and eliminated N+1 queries in existing family, variant-axis, product, and user workflows.
- Rebuilt completeness dashboard calculations with grouped aggregates.
- Limited export-filter, attribute-option, category-tree, and product-edit payloads with search and pagination.
- Added dashboard cache invalidation for category and configuration changes and made core configuration reads consistent on non-tag cache drivers.
- Added a centralized, configurable password policy shared by server validation and client-side forms.
- Added localized, responsive admin primitives for page headers, breadcrumbs, settings pages, DataGrids, media, modals, tabs, accordions, drawers, and form controls.
- Self-hosted TinyMCE and Inter assets so admin and installer screens work without runtime CDN access.
- Added unique, label-compatible DOM IDs to all form controls and handlers, including repeated controls rendered in modals.
- Improved dark-mode contrast, focus rings, locale chips, validation messages, filter counters, empty states, sticky controls, and responsive drawers.
- Improved product exports with active-currency expansion and per-currency price conditions.
- Improved existing integration, Magic AI, system-prompt, data-transfer, user, role, category, family, and product screens with consistent AJAX navigation and global save behavior.
- Improved installer support for PostgreSQL, database prefixes, Canadian locales, CAD, searchable locale/currency selection, secure generated administrators, and database engine selection.
- Improved Docker entrypoints with a shared readiness probe, PHP development settings, safer production defaults, and native PostgreSQL support.
- Localized every new user-facing capability across all supported locales.
- Added configuration-cache-safe notification, Elasticsearch, trusted-proxy, and token-TTL handling.
- Added public API deprecation headers to the legacy misspelled `configrable-products` alias while keeping it operational.
- Improved channel activation so enabled locales and dependent channel state stay synchronized.
- Improved import-triggered product events so create/update webhooks are delivered consistently for imported records.
- Improved product family cloning by copying attribute mappings in set-based database operations.
- Improved admin theme compilation so package and extension views are scanned for Tailwind classes.
- Removed obsolete generated assets, screenshots, debug scripts, and dead package references from the repository.

### Classes

- Added `Webkul\Admin\Contracts\SsoProvider` for pluggable admin SSO drivers.
- Added `Webkul\Product\Contracts\VariantValueResolver`, `VariantStructurePlanner`, and `VariantPlacementSuggester` for extensible variant inheritance and placement.
- Added `Webkul\Publication\Contracts\PayloadBuilder`, `Webkul\Publication\Contracts\PublicationGate`, and `Webkul\Publication\Registry\PublicationTypeRegistry` for custom publication types.
- Added `Webkul\Resource\Http\Controllers\AbstractResourceController` and `Webkul\Resource\Contracts\ResourceInterface` for low-code package CRUD.
- Added `Webkul\Admin\SystemSettings` as the config-driven extension point for package-owned settings.
- Added contracts, repositories, proxies, and events for configurable associations, measurements, publications, passport templates, saved grid views, and webhooks.

## BC Breaks

> Upgrading an existing installation? Follow [UPGRADE.md](UPGRADE.md). It covers
> what to do about each break below, and `php artisan unopim:upgrade --dry-run`
> reports which of them apply to your installation before you book downtime.

- **Runtime requirement:** PHP `8.4.1` or newer is required. A `v2.1.6` server running PHP 8.3 must be upgraded before installing this release.
- **Framework compatibility:** Laravel 12 was replaced by Laravel 13, upgrading its framework dependencies, including Symfony 8 components. Custom packages must update overridden method signatures, middleware references, service-provider behavior, and typed contracts for the new framework.
- **Dependency compatibility:** major upgrades include `intervention/image` 4, `kalnoy/nestedset` 7, `laravel/passport` 13, `laravel/tinker` 3, `predis/predis` 3, `prettus/l5-repository` 4, Pest 5, and PHPUnit 13. Custom package constraints and direct use of those libraries must be updated.
- **Required database upgrade:** run the release migrations before serving traffic. They add the association, variant-structure, measurement, publication, passport-template, saved-view, webhook, catalog-scope, SSO, and robot-user schemas, migrate existing associations/integrations, and create indexes on large catalog tables. Plan maintenance time for large databases.
- **OAuth tokens and signing keys:** the shared signing keys from `v2.1.6` are no longer distributed. Each installation generates its own key pair when none exists. Replacing the old pair invalidates existing access and refresh tokens, so API clients must authenticate again.
- **API integration ownership:** integration creation no longer accepts `admin_id`. Existing integrations are migrated to dedicated robot users; custom clients or extensions that submit/read the assigned administrator must adopt the robot credentials workflow.
- **Variant value storage:** newly created variant children no longer copy inherited parent values into their raw `products.values` JSON. Extensions that read that column directly must use `Product::resolvedValues()` or the `VariantValueResolver` contract to obtain effective values.

### Codebase

- **Attribute-group URLs:** hardcoded admin URLs must change from `catalog/attributegroups/*` to `catalog/attribute-groups/*`.
- **Attribute-family URLs and screens:** hardcoded admin URLs must change from `catalog/families/*` to `catalog/attribute-families/*`. The standalone `admin.catalog.families.create` and `admin.catalog.families.copy` pages/routes were removed in favor of the modal/scaffold workflow.
- **Data Transfer URLs:** hardcoded URLs must change from `settings/data-transfer/*` to `data-transfer/*`; tracker URLs also move from `tracker/*` to `job-tracker/*`.
- **Integration URLs:** hardcoded integration URLs must change from `integrations/api-keys/*` to `configuration/integrations/*`.
- **System Settings URLs:** the legacy combined settings page was replaced by the hub at `configuration/system-settings`, with editors under `configuration/system/{key}`. Extensions linking to the old combined page must update their routes.
- **Admin UI extensions and E2E tests:** edit forms now use AJAX navigation and a global unsaved-changes save bar. Extensions or browser tests that depend on full-page reloads, old per-form save buttons, or previous DOM IDs/selectors must be updated.
- **Docker database default:** new Docker environments default to PostgreSQL. Existing `v2.1.6` Docker installations must keep their current `COMPOSE_PROFILES`, `DB_CONNECTION`, host, and port values; changing the profile does not migrate MySQL data to PostgreSQL.
- **Docker Compose file layout:** `compose.yaml` now runs UnoPim from the published images and needs no `.env`, while the stack that builds from a checkout moved to `compose.dev.yaml` (`compose.dev.apache.yaml` for Apache, `compose.mysql.yaml` for MySQL on the image stack). A bare `docker compose up` in a clone therefore resolves the image stack rather than building. `docker-compose.yml` and `docker-compose.hub.yml` remain as thin includes of the new files. Service topology in `compose.yaml` is pinned rather than read from the environment, so a Laravel `.env` in the project directory can no longer redirect the containers at a host database.
- **Deprecated REST alias:** `configrable-products` still works for this release but now returns deprecation and successor headers. Clients should move to `configurable-products` before the alias is removed in a future release.

### Classes

- Removed `Webkul\Admin\Helpers\Reporting`, `Webkul\Admin\Http\Resources\AttributeResource`, `Webkul\Admin\Http\Resources\AttributeOptionResource`, and `Webkul\Admin\Listeners\Base`.
- Removed `Webkul\Theme\Providers\ModuleServiceProvider` and `Webkul\Theme\Repositories\ThemeCustomizationRepository`.
- Removed the old webhook `WebhookSettingsController` and `SettingsRepository`; extensions must use the multi-webhook model and repository.
- Removed obsolete Admin Blade components including `media.file`, `media.videos`, and `select.multiselect`.

### Services

- Removed `spatie/laravel-responsecache`, `config/responsecache.php`, and the obsolete `Webkul\FPC` provider, listeners, and hasher. `RESPONSE_CACHE_ENABLED` no longer has an effect.
- Removed the nonexistent `Webkul\Inventory` Composer namespace and remaining configurable-product inventory wiring; extensions must not reference them.
