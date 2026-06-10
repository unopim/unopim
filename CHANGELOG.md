# v2.1.x

### Security
- Hardened file uploads in the rich-text (TinyMCE) editor — uploads are now limited to an approved set of image types and saved under randomised filenames ([#476](https://github.com/unopim/unopim/pull/476)).
- Tightened API permission enforcement on the configurable-product endpoints so every request requires the correct access scope ([#477](https://github.com/unopim/unopim/pull/477)).
- Added the required permission checks to the Magic AI platform **update** and **set-default** actions ([#479](https://github.com/unopim/unopim/pull/479)).
- Hardened product-grid sorting to safely handle all sort input ([#488](https://github.com/unopim/unopim/pull/488)).
- Improved how channel names are rendered on the attribute-family **Completeness** screen ([#489](https://github.com/unopim/unopim/pull/489)).

## v2.1.4 - 2026-06-06

### Features
- Added a **Help & Resources** admin page and sidebar menu.
- Added **dismissible cloud-hosting and version-upgrade promo banners** in the admin.

### Improvements
- Revamped the **web installer** — admin-themed UI, a live terminal view of the install, optional add-on packages (DAM, Shopify, Bagisto) and Elasticsearch setup, database auto-create, and shared/FTP-only hosting support.

## v2.1.3 - 2026-06-04

### Security
- Sealed the installer once setup completes — the `CanInstall` middleware now blocks every `/install` request after a `storage/installed` marker exists, and defense-in-depth `abortIfInstalled()` guards were added to each state-changing install endpoint (env-file-setup, run-migration, run-seeder, admin-config-setup, seed-sample-data, smtp-config-setup). The completion marker is written only at the very end of the flow, closing a pre-auth admin-takeover vector on already-installed instances ([#459](https://github.com/unopim/unopim/pull/459)).
- Enforced ACL permission checks on state-changing admin routes that were absent from `acl.php` — including integration management, product bulk-edit, and family-completeness/configuration actions. The `Bouncer` middleware only enforced permissions for mapped routes, so unmapped write routes were reachable by any authenticated admin; restricted users now receive a 403 ([#467](https://github.com/unopim/unopim/pull/467)).
- Fixed **insecure default admin password on install** — the installer no longer creates the admin with a known default password. Set `INSTALLER_ADMIN_EMAIL` / `INSTALLER_ADMIN_PASSWORD`, or leave them blank and a random password is generated and written once to `storage/app/admin-credentials.txt` (read it, log in, then delete). Existing admin users are never overwritten on re-install ([#457](https://github.com/unopim/unopim/pull/457)).
- Fixed **duplicate rows on re-seeding** — admin and role seeders are now idempotent and skip insertion when the record already exists ([#457](https://github.com/unopim/unopim/pull/457)).

### Improvements
- Added a debug-only **`AppUrlGuard`** package — detects when the browser host does not match the configured `APP_URL` (a common cause of broken CSS/JS), shows a guided fix modal, and forces admin logout on a mismatched host. It stays completely inert when `APP_DEBUG=false` and ships translations for 33 locales ([#456](https://github.com/unopim/unopim/pull/456)).

## v2.1.2 - 2026-05-29

### Improvements
- Hardened **webhook URL handling** — webhook target URLs are now validated to ensure they resolve to a publicly reachable host before any request is made, applied both when saving the webhook settings and on each product-save dispatch. Outgoing webhook requests connect only to the validated address and no longer follow HTTP redirects ([#448](https://github.com/unopim/unopim/pull/448), [#450](https://github.com/unopim/unopim/pull/450)).


## v2.1.1 - 2026-05-26

### Security
- Patched an authorization gap on several admin write-verb routes (`*.store` / `*.update`) — they were missing from `packages/Webkul/Admin/src/Config/acl.php`, so the `Bouncer` middleware did not enforce a permission check. Mapped each missing route to the same ACL key as its sibling GET form (`.create` / `.edit`) and added regression coverage.
- Hardened against `Host` / `X-Forwarded-Host` header poisoning. Asset and URL helpers (`url()`, `asset()`, Vite) previously resolved against the request `Host` header, so a crafted header could cause the admin layout to load JavaScript from an attacker origin. URL generation is now pinned to `APP_URL` via `URL::forceRootUrl()` + `URL::forceScheme()`, the four templates that rendered `url()->to('/')` / `asset('/')` were switched to `config('app.url')`, `trustProxies` is restricted via the new `TRUSTED_PROXIES` env variable (defaults to `127.0.0.1`), and `trustHosts` is enabled seeded from `APP_URL` + the new `TRUSTED_HOSTS` env variable.

### Bug Fixes
- Fixed **MagicAI chat-latest model temperature** — newer OpenAI chat-latest models reject the `temperature` parameter; the adapter now omits the field when targeting those models ([#416](https://github.com/unopim/unopim/pull/416)).
- Fixed **styled 405 Method Not Allowed page** — replaced the unstyled framework error with a translated 405 error view consistent with 403/404/500, including translations for all 33 locales ([#417](https://github.com/unopim/unopim/pull/417)).
- Fixed **PostgreSQL demo-data seeding** — `DemoExtrasSeeder` now casts booleans for pgsql, bypasses FK during seed, and resets sequences afterwards so the demo install completes cleanly on PostgreSQL ([#418](https://github.com/unopim/unopim/pull/418)).
- Fixed **installer rejects invalid `DB_DATABASE`** — special characters in the database name are now rejected up front instead of causing a partial install that has to be torn down by hand ([#419](https://github.com/unopim/unopim/pull/419)).
- Fixed **installer prompts ignore leading/trailing whitespace** — applied `transform: trim(...)` across installer prompts so a stray space in DB or Elasticsearch credentials no longer breaks the install ([#420](https://github.com/unopim/unopim/pull/420)).

### DevOps
- Added **multi-architecture Docker images** — the publish workflow now produces both `linux/amd64` and `linux/arm64` images so Apple Silicon and ARM cloud hosts get native binaries ([#426](https://github.com/unopim/unopim/pull/426)).


## v2.1.0 - 2026-05-13

### Features
- Added **ManageAssociations AI Agent Tool** — manage product associations (related, cross-sell, up-sell) via natural language in the AI Agent Chat, with clickable product links in search results for internal navigation.
- Added **Demo Data Seeding** — `php artisan unopim:install --with-demo-data` CLI flag, installer wizard toggle, Docker setup option, and standalone `php artisan unopim:install:demo-data` command to seed sample products during/after installation ([#392](https://github.com/unopim/unopim/pull/392)).
- Added **Production-Ready Docker Setup** — multi-container stack (Nginx + PHP-FPM default, Apache fallback) with Docker Hub images, healthchecks, Redis, Elasticsearch, Mailpit services, OPcache-tuned `php.ini`, and auto-publish workflow ([#334](https://github.com/unopim/unopim/pull/334)).
- Added **`clean_content()` XSS sanitization helper** — uses HTMLPurifier to strip Blade directives, PHP tags, and dangerous HTML from user-generated content.
- Added **IP-based debug filtering** — `APP_DEBUG_ALLOWED_IPS` environment variable restricts debugbar access to specific IP addresses in production.
- Added **MagicAI Custom Provider** — OpenAI-compatible custom provider option in MagicAI platform configuration, with hardened `LaravelAiAdapter` and `MagicAIPlatform` model for custom base URLs ([#344](https://github.com/unopim/unopim/pull/344), [#356](https://github.com/unopim/unopim/pull/356)).
- Added **MagicAI ModelRecommender** — Test Connection now uses a dedicated recommender that skips image-only models, preventing false negatives during platform credential validation ([#344](https://github.com/unopim/unopim/pull/344)).
- Added **PrismErrorResolver** — translates provider/Prism errors into user-friendly messages in the AI Agent Chat ([#344](https://github.com/unopim/unopim/pull/344)).
- Added **Clickable Dashboard Product Stats** — product-stats cards on the dashboard now act as filter chips, deep-linking into the product grid ([#344](https://github.com/unopim/unopim/pull/344)).
- Added **Async Product Webhook Dispatch** — new `SendProductWebhook` queued job triggers webhooks on product creation/update without blocking the request ([#381](https://github.com/unopim/unopim/pull/381), [#387](https://github.com/unopim/unopim/pull/387)).
- Added **PIM-specific test assertions** — `CoreAssertions` trait with `assertProductExists()`, `assertCategoryExists()`, `assertAttributeExists()`, `assertChannelExists()`, `assertLocaleExists()`, and `assertSuccessJsonResponse()` helpers for Pest tests.

### Improvements
- Replaced **deprecated `Request::get()`** with `->input()` across remaining controllers (the underlying `symfony/http-foundation` 7.x dependency deprecates `Request::get()` — Laravel inherits the deprecation).
- Added **return type hints** and adopted **HTTP status constants** (`JsonResponse::HTTP_OK`, etc.) in additional controllers across Admin and AdminApi packages in place of magic numbers.
- Removed **auto-discovered providers** from `bootstrap/providers.php` — third-party packages (DomPDF, Translatable, Concord, Excel) are auto-discovered by Laravel and no longer need explicit registration.
- Standardized **exception handling** — use `wantsJson()` instead of `ajax()` for API detection, added `report($e)` to silent catch blocks.
- Extracted **ImportProducts** into a queued `ImportProductsJob`, enabling reliable imports of 10k+ products via the AI Agent ([#372](https://github.com/unopim/unopim/pull/372)).
- Refactored **dynamic attribute field rendering** on the product edit page to remove dummy extra spaces between fields ([#271](https://github.com/unopim/unopim/pull/271)).
- Improved **`nl_NL` translation quality** across Admin, AiAgent, Completeness, Core, DataTransfer, Installer, Product, and Webhook packages — informal tone, removed stray `kenmerk` prefix, zero-width-space cleanup (thanks to [@TheMazeIsAmazing](https://github.com/TheMazeIsAmazing), [#382](https://github.com/unopim/unopim/pull/382)).
- Rewrote **Playwright E2E tests** for full independence — every test creates its own data, acts, asserts, and cleans up. Shared helpers (`navigateTo()`, `searchInDataGrid()`, `clickSaveAndExpect()`) reduce duplication. Tests support parallel execution with unique identifiers.
- Added **PostgreSQL CI workflow** — separate Pest test workflow against PostgreSQL 16 with and without Elasticsearch.

### Bug Fixes
- Fixed **format validation** applied to empty optional attributes and category fields — validation is now skipped when the value is empty ([#319](https://github.com/unopim/unopim/issues/319)).
- Fixed **dark mode visibility** in job tracker progress bars and AI platform configuration button styling ([#320](https://github.com/unopim/unopim/issues/320)).
- Fixed **double table prefix** in migration — hardcoded `wk_` prefix on table names caused `wk_wk_channels` when `DB_PREFIX` was set. Migrations now use unprefixed names with `Schema::hasTable` guards.
- Fixed **`AdminFactory` FK violations** in parallel test databases — replaced hardcoded `role_id` and `ui_locale_id` with dynamic database lookups.
- Fixed **undefined `$channel` variable** in `CheckForMaintenanceMode` — now reads from `env()` config instead of undefined variable.
- Fixed **missing null check** on `sanitizeData()` return value in `ProductController` — method can return `null` when product data has no attributes to sanitize.
- Fixed **clipboard copy** fallback for non-HTTPS environments in AI Agent Chat.
- Fixed **DB table prefix** issue in `ExportProducts` values column query.
- Fixed **migration rollback** for `add_tone_to_magic_ai_prompts_table` — added the missing `down()` column drop ([#394](https://github.com/unopim/unopim/pull/394)).
- Fixed **product indexing in Docker import jobs** — `Importer` now casts boolean status before bulk Elasticsearch indexing inside the Docker queue worker ([#393](https://github.com/unopim/unopim/pull/393)).
- Fixed **column-label fallback mismatch** between the Manage Columns popup and the product grid on pgsql/Docker — unified via a shared `TranslatableModel` helper and `AttributeColumnTrait` ([#390](https://github.com/unopim/unopim/pull/390)).
- Fixed **empty Docker `APP_KEY`** — fpm/queue/scheduler entrypoints now export the generated `APP_KEY` so Apache/FPM inherit it on first start ([#391](https://github.com/unopim/unopim/pull/391)).
- Fixed **`BulkProductCompletenessJob`** querying the wrong table (`roles` → `admin_roles`) ([#389](https://github.com/unopim/unopim/pull/389)).
- Fixed **webhook trigger on product creation** — `SendProductWebhook` job and `Product` listener wired up; `webhook_logs.user_id` made nullable via new migration ([#387](https://github.com/unopim/unopim/pull/387)).
- Fixed **`DB_PREFIX` validation** — installer command and `InstallerController` now trim, reject internal whitespace, and clear stale prefix values ([#386](https://github.com/unopim/unopim/pull/386), [#335](https://github.com/unopim/unopim/pull/335)).
- Fixed **MagicAI HTML translation paragraph loss** — translate-to-locale endpoints now preserve every `<p>` paragraph by iterating all matches instead of `end()` (thanks to [@bentierny](https://github.com/bentierny), [#378](https://github.com/unopim/unopim/pull/378)).
- Fixed **admin login denied after install with seeded data** — installer writes a proper `APP_KEY` and `AdminsTableSeeder` sets a default timezone for the admin user ([#385](https://github.com/unopim/unopim/pull/385)).
- Fixed **MySQL bulk-mode session vars** crashing PostgreSQL — `unique_checks` and `foreign_key_checks` are now gated by driver in the product Importer ([#368](https://github.com/unopim/unopim/pull/368)).
- Fixed **installer re-run after switching `DB_CONNECTION`** — `database.default` is updated and the previous connection purged at runtime so the new driver is targeted ([#367](https://github.com/unopim/unopim/pull/367)).
- Fixed **PostgreSQL incompatibilities** in AiAgent tools (`BulkEdit`, `SearchProducts`, `ListCategories`, etc.) and Channel/AttributeOption DataGrids — added `PostgresGrammar::jsonContains()`; also fixed profile-image placeholder and TinyMCE copy-paste ([#376](https://github.com/unopim/unopim/pull/376)).
- Fixed **multiple critical issues** — MagicAI ACL for content/image generation, PDF uploads >2 MB via `.user.ini`, translated-locale attribute label consistency, file-upload guards ([#372](https://github.com/unopim/unopim/pull/372)).
- Fixed **PostgreSQL DataGrid filter** (`SkuOrUniversalFilter`), product-edit status toggle save, and empty-label fallback in Vue selectors ([#374](https://github.com/unopim/unopim/pull/374)).
- Fixed **Docker product/category DataGrid 503s** (relaxed ES heap and indexer config) and **AiAgent locale/channel-aware content generation** in `EnrichmentService`/`AutoEnrichProductJob` ([#375](https://github.com/unopim/unopim/pull/375)).
- Fixed **MagicAI custom provider** path, AI Agent chat hardening, media UI, and installer seed ([#356](https://github.com/unopim/unopim/pull/356)).
- Fixed multi-issue batch ([#355](https://github.com/unopim/unopim/pull/355)): prevent self/descendant categories as parent, attribute history tab columns, root category rejection on products, sticky product-edit header, ACL gating in MagicAI grids, 403 (not 401) for authenticated permission denials, AI platform default validation, datetime/numeric bulk-edit validation, configurable products REST `DELETE` endpoint.
- Fixed webhook product comparer, `ProductBulkEditTest`/`ApiProductTest` regressions, and `BulkProductUpdate` job failures ([#348](https://github.com/unopim/unopim/pull/348)).
- Fixed empty-file import validation, broken profile-logo fallback, HTMLPurifier cache path (moved to `storage/`), webhook-logs ACL/tab gating, AI Agent `EditImage` SKU lookup, XLSX export for `ExportProducts` tool, and bulk-import SKU sanitisation ([#353](https://github.com/unopim/unopim/pull/353)).
- Fixed webhook variants, attribute family update with empty groups, `ChatRateFeedback` endpoints, Bouncer 403 message, attribute history translation, Excel rich-text importer ([#347](https://github.com/unopim/unopim/pull/347)).
- Fixed AiAgent chat-widget translations across 33 locales, `SearchProducts`/`ManageUsers` tools, and dashboard product-stats blade ([#346](https://github.com/unopim/unopim/pull/346)).
- Fixed **drag-and-drop file upload** on Category/Product imports — dropped files now populate the native `<input type="file">` via the DataTransfer API so they survive multipart form submit ([#349](https://github.com/unopim/unopim/pull/349)).
- Fixed DataGrid/Dashboard/MagicAI/Notification issues ([#342](https://github.com/unopim/unopim/pull/342)): case-insensitive SKU search, Select-All checkbox sync, redundant `v` version prefix removed, broken MagicAI DataGrid edit modal (`index` → `edit`), PostgreSQL status integer cast in stats, plus 33-locale translation updates.
- Fixed **Elasticsearch 8 boolean status** — `ElasticProductCursor`, `ProductIndexer`, and Product observer now cast status to boolean (ES8 rejects integer 1/0) ([#337](https://github.com/unopim/unopim/pull/337)).
- Fixed **Docker Elasticsearch readiness** — added wait, relaxed disk-watermark thresholds, auto-sync `APP_URL` with `APP_PORT`, and DB-wipe recovery in entrypoints ([#339](https://github.com/unopim/unopim/pull/339)).
- Fixed **Docker web-entrypoint.sh** permissions/script handling ([#236](https://github.com/unopim/unopim/pull/236)).
- Fixed **import file deletion** — `ImportController` now checks file existence before deleting paths, preventing job profile deletion errors ([#276](https://github.com/unopim/unopim/pull/276)).

### Security Fixes
- Patched **5 audit-report vulnerabilities** ([#332](https://github.com/unopim/unopim/pull/332)):
  - **Open Redirect via Referer Header** (Medium) — Login and forgot-password pages accepted spoofed `Referer` headers containing 'admin' in external URLs (e.g., `https://attacker.com/admin`), allowing phishing redirects. Added host validation using `parse_url()` to ensure the intended redirect URL belongs to the same application host.
  - **No Rate Limiting on Admin Login** (Medium) — Added named rate limiters (`admin-login`, `admin-forgot-password`) in `AdminServiceProvider` using `RateLimiter::for()` with per-email+IP segmentation (5 attempts/minute).
  - **No Server-Side Password Validation** (Medium) — `UserForm` accepted passwords with no minimum length. Added `min:6` validation rule to align with `AccountController` and `ResetPasswordController`.
  - **User Enumeration via Forgot Password** (Medium) — Forgot-password endpoint returned different responses for existing vs non-existing emails. Changed to return a single generic message regardless of email existence.
  - **Privilege Escalation via User Edit Endpoint** (High) — `admin.settings.users.update` and `admin.settings.users.destroy` routes were missing from the ACL config (Bouncer skipped auth — replayable via Burp Suite). Also, no guard prevented non-superadmins from assigning `permission_type: all` roles. Added missing ACL entries and controller-level privilege escalation guards in both `store()` and `prepareUserData()`.
- Added **NoCacheMiddleware** — prevents browsers and proxies from caching admin pages (`Cache-Control: no-store`, `Pragma: no-cache`).
- Enhanced **SecureHeaders** middleware with `Permissions-Policy` and `X-Permitted-Cross-Domain-Policies` headers.
- Added **`maintenance_allowed_ips`** and **`debug_allowed_ips`** configuration in `config/app.php` for environment-level access control.

### Performance
- Added **database indexes** on `channels.code`, `locales.status`, `currencies.status`, and a composite index on `core_config(code, channel_code, locale_code)` for faster config lookups and queries.

### Tests
- Added **Pest security tests** in `packages/Webkul/User/tests/Feature/SecurityTest.php` (13 tests) covering all 5 audit vulnerabilities including the Burp replay privilege escalation scenario.
- Added **4 Playwright E2E security tests** in `tests/e2e-pw/tests/08-security/security.spec.js` for login redirect, rate limiting, password validation, and forgot-password enumeration.
- Updated `UserAclTest` to use a custom role instead of all-access `role_id=1` for user creation ACL test (aligned with new privilege escalation guard).
- Added webhook variant, attribute-family empty-groups, Bouncer 403, chat rate feedback, attribute history tooltip, and Excel rich-text test coverage; `ManageUsers` email masking ([#352](https://github.com/unopim/unopim/pull/352)).
- Added `ProductIndexBatchBooleanStatusTest` covering ES8 boolean status casting in bulk indexing ([#393](https://github.com/unopim/unopim/pull/393)).
- Stabilised flaky Playwright AI Assistance modal test (7.3) and fixed duplicate option code collisions in `AttributeTest` via `Str::random` ([#338](https://github.com/unopim/unopim/pull/338)).
- Added missing `code` field to swatch attribute option tests in `AttributeTest` (thanks to [@prismaticoder](https://github.com/prismaticoder), [#252](https://github.com/unopim/unopim/pull/252)).
- Refactored Playwright `magicAI-acl.spec.js` to inline full ACL coverage and tuned CI timeout settings ([#383](https://github.com/unopim/unopim/pull/383)).

### Dependency Updates
- Bumped `phpseclib/phpseclib` from `3.0.50` to `3.0.52` ([#345](https://github.com/unopim/unopim/pull/345), [#384](https://github.com/unopim/unopim/pull/384)).
- Bumped `phpoffice/phpspreadsheet` from `1.30.2` to `1.30.4` ([#364](https://github.com/unopim/unopim/pull/364)).

### Contributors

Thanks to the following community contributors for this release:
- [@bentierny](https://github.com/bentierny) — MagicAI HTML translation paragraph preservation ([#378](https://github.com/unopim/unopim/pull/378))
- [@TheMazeIsAmazing](https://github.com/TheMazeIsAmazing) — `nl_NL` translation quality improvements ([#382](https://github.com/unopim/unopim/pull/382))
- [@prismaticoder](https://github.com/prismaticoder) — swatch attribute option test fixes ([#252](https://github.com/unopim/unopim/pull/252))

---

## v2.0.0

### Features
- Added **AI-Powered Translation Command** — `php artisan unopim:translations:check --translate` uses MagicAI to bulk-translate missing locale keys via AI instead of copying English values. Includes `--fix-untranslated` to re-translate keys identical to `en_US`, smart skip patterns for technical terms and acronyms, and batched API calls (max 100 keys per chunk).
- Auto-translated **~18,000 previously untranslated keys** across all 32 non-English locales in 7 packages (Admin, Completeness, Core, DataTransfer, Installer, Product, Webhook).

### Improvements
- Enhanced **German (de_DE) translations** — fixed incorrect "Kolonne" (convoys) to "Spalten" (columns) in DataGrid manage-columns title and improved overall accuracy.
- Added **Elasticsearch auto-reindex after seeding** — installer now runs `unopim:product:index` automatically when ElasticSearch is enabled, ensuring seeded products are immediately searchable.
- Added **configurable product super attributes** in seeder — `ProductTableSeeder` now creates `product_super_attributes` pivot rows linking configurable products to the `size` attribute.
- Added **DataTransfer model factories** — `JobTrack::factory()` and `JobTrackBatch::factory()` with default states (`completed`, `processed`, `failed`, `export`) for Pest test usage.
- Improved **import job failure handling** — all four import job classes (`Completed`, `ImportBatch`, `IndexBatch`, `LinkBatch`) now properly mark the parent `JobTrack` as failed when their `failed()` method is called, preventing inconsistent job states.
- Added **boolean-to-integer casting** for attribute checkbox fields (`is_required`, `is_unique`, `enable_wysiwyg`, `is_filterable`, `ai_translate`) to prevent PostgreSQL NOT NULL constraint violations when unchecked.
- Improved **cross-database JSON grammar** in product importer — replaced MySQL-specific `JSON_UNQUOTE(JSON_EXTRACT(...))` with `GrammarQueryManager::getGrammar()->jsonExtract()` for PostgreSQL compatibility.
- Improved **media field null safety** in `FieldProcessor` — `handleField()` parameter changed from `string $path` to `?string $path` with null check before `handleMediaField()`.

### Bug Fixes
- Fixed **API returning "Unauthenticated"** despite valid requests — `array_keys(config('auth.providers'))[0]` returned `'users'` instead of `'admins'` because Laravel merges the default auth config. Changed to explicit `config('auth.guards.api.provider', 'admins')` in both `ApiClientCommand` and `OauthClientGenerator`.
- Fixed **channel creation 500 error** when no translations were provided — `ChannelRepository::create()` now strips empty translation data before saving, preventing NOT NULL constraint violations on `channel_translations.name`.
- Fixed **family code label** missing `required` class indicator on both create and edit attribute family pages.
- Fixed **JSON data handling and Elasticsearch indexing** issues causing corrupted search results after product seeding.
- Fixed **completeness DataGrid** double table prefix bug — `channel_required` filter used `$tablePrefix.'channels.code'` which caused `wk_wk_` prefix when the database already applies the prefix.

---

## v2.0.0-beta.1

### Framework Upgrade
- Upgraded from **Laravel 10** to **Laravel 12** with modernized bootstrap architecture.
- Upgraded minimum **PHP** requirement from `8.2` to `8.3`.
- Migrated application bootstrap to Laravel 12's `Application::configure()` fluent API in `bootstrap/app.php`.
- Moved service provider registration to `bootstrap/providers.php`.
- Removed `Kernel.php` classes, individual middleware files, and legacy service providers (replaced by `bootstrap/app.php`).

### Features
- Added **AI Agent Chat** interface for conversational product management with 32+ PIM tool actions accessible via natural language (search, create, update, delete, bulk edit, export, categorize, generate content/images, memory, planning, quality reports, etc.).
- Added **Multi-Platform MagicAI** with support for 10+ AI providers (OpenAI, Anthropic, Gemini, Groq, Ollama, XAI, Mistral, DeepSeek, Azure, OpenRouter) with database-backed credential management and encrypted API key storage.
- Added **AI-Powered Search** with `EmbeddingSimilarityService` and `SemanticRankingService` for intelligent product discovery.
- Added **Configurable Product Support** in AI Agent — create configurable products with variants (color, size) via natural language with `super_attributes` and `variants_json` parameters.
- Added **Auto-Translation** on product create/update — text fields are automatically translated to all configured locales via queued AI translation job.
- Added **Approval Queue** for AI changes — configurable `approval_mode` (auto/review/suggest) with `QueuesForApproval` trait on write tools and `ApprovalController` for approve/reject workflow.
- Added **Agent Memory System** — `RememberFact` and `RecallMemory` tools with keyword-relevant injection into system prompt for persistent catalog knowledge.
- Added **Catalog Quality Monitor** — `php artisan ai-agent:quality-monitor` scheduled command with health scoring and proactive notifications.
- Added **Auto-Enrichment** — event-driven `AutoEnrichProductJob` dispatched on product creation to fill missing descriptions and SEO fields.
- Added **Content Feedback Loop** — `RateContent` tool captures user preferences; style feedback injected into system prompt for improved future generations.
- Added **Bulk Transform** support in `bulk_edit` tool — append, prepend, and replace operations on existing attribute values (e.g., append "-webkul" to all URL keys).
- Added **Data Quality Report** tool — catalog-wide scan for missing names, descriptions, images, categories with health score.
- Added **Product Verification** tool — quality scoring (0-100) for self-checking after create/update operations.
- Added **Task Planning** tool — multi-step goal decomposition for complex catalog operations.
- Added **SSE Streaming** for AI Agent Chat — real-time tool-call progress and text streaming with `StreamedResponse`.
- Added **Token Budget Tracking** — per-user daily token usage tracking with configurable budget limits.
- Added **Conversation Persistence** — database-backed chat sessions with API endpoints and localStorage fallback.
- Added **AI Agent Analytics Dashboard** — token usage analytics, daily breakdown, audit trail, and rollback capability.
- Added **Agentic PIM Configuration** section in admin — enable/disable toggle, max steps, daily token budget, auto-enrichment, quality monitor, confidence threshold, and approval mode settings.
- Added **Swatch Types** for select and multiselect attributes with support for color, image, and text swatches, including datagrid preview, product page display, and API endpoints.
- Added **Enhanced Dashboard** with channel readiness, product trends, recent activity, needs-attention, product stats, and data transfer status widgets.
- Added **Import/Export Tracker UI** with real-time step pipeline visualization, job-specific logging, and ZIP image upload modal with drag-and-drop support.
- Added **Drag-and-Drop File Upload** support in import job file uploader for CSV, XLSX, and XLS files.
- Added **Pause, Resume, and Cancel** controls for both import and export jobs during processing.
- Added **Completeness Queue** as separate queue with dedicated provider support.

### Improvements
- Optimized **Export Pipeline**: eager loading of `super_attributes`/`parent`/`attribute_family`, cached `initialize()` per export ID, increased `BATCH_SIZE` to 200.
- Optimized **Import Pipeline**: deferred indexing, field processor improvements, batch state tracking, configurable batch sizes and bulk chunk sizes for high-volume CSV/XLSX processing.
- Optimized **Category Export** performance by replacing in-memory product loading with direct count queries, preventing timeout on large catalogs.
- Replaced `ImageManager` with new **ImageCache** system featuring deferred execution, closure hashing, and ETag support.
- Replaced individual MagicAI provider service classes with unified `LaravelAiAdapter`.
- Updated **Magic AI** with latest OpenAI and Gemini text/image generation models.
- Improved **Elasticsearch Filters** for SKU, text, and option filters with better array and CONTAINS handling.
- Improved **Swatch Type** validation with dedicated `ValidSwatchValue` rule and attribute option validation.
- Added `$tries` and `$timeout` configuration to export batch jobs for improved queue reliability.
- Added translation strings for all tracker UI elements, removing hardcoded static text.
- Added **CI/CD improvements**: translation auditing workflow, Composer caching, concurrency groups, PHP 8.3 and Node.js 20 across all workflows.

### Bug Fixes
- Fixed **API ACL missing permissions** — 15 API routes (DELETE, PATCH, media uploads, attribute/category field options) had no ACL protection and were accessible without authorization. All 48 API routes now have proper ACL enforcement.
- Fixed **AI Agent status toggle bug** — `(bool) "inactive"` evaluated to `true` in PHP, causing bulk status changes to never actually set products to inactive. Replaced with strict `in_array()` matching.
- Fixed **AI Agent locale-keyed value corruption** — LLM passing `{"ar_AE": "text"}` objects for locale content overwrote English values with arrays, causing 500 errors on product edit page. Added locale-map detection and type guards.
- Fixed **AI Agent image not attached on confirmation** — uploaded images were lost between the analysis request and the "yes, proceed" confirmation because the second HTTP request had no files. Added session-based image persistence across conversation turns.
- Fixed **AI Agent daily token budget misconfiguration** — `default_value` in config is a form hint only, not a runtime default. `core()->getConfigData()` returned `"1"` (from enabled toggle), giving a 1-token budget. Fixed to treat falsy values as unlimited.
- Fixed **AI Agent session locking** — streaming responses held the PHP session lock for 30-120 seconds, blocking all other admin requests. Added `session()->save()` before LLM calls to release the lock immediately.
- Fixed **AI Agent hardcoded `en_US` locale** in 6 search/list tools — replaced with `$context->locale` for proper multi-locale support.
- Fixed **AI Agent LIKE wildcard injection** — LLM-provided search queries with `%` or `_` characters could match unintended patterns. Added wildcard escaping.
- Fixed **PostgreSQL migration compatibility** — MagicAI migrations used MySQL-only `MODIFY COLUMN ... ENUM()` syntax. Added driver detection with PostgreSQL-compatible `ALTER COLUMN ... TYPE` + `CHECK` constraints.
- Fixed category export job failing due to loading all products into memory for count queries.
- Fixed import tracker 500 error caused by incorrect route name.
- Fixed export step pipeline showing "Importing" instead of "Exporting" labels.
- Fixed import completed section showing 0 counts for created/updated/deleted records.
- Fixed `AttributeCompletenessDataGrid` missing table prefix for `channels.code`.
- Fixed #215 - SKU validation rule incorrectly rejecting underscores.
- Fixed #215 - UI validation alignment and additional validation messages.
- Fixed swatch type display issues for product datagrid, multiselect, and select attributes.
- Fixed Magic AI image generation with DALL-E models and mime-type validation.
- Fixed product datagrid `updated_at` timezone display.
- Fixed attribute validation for swatch type fields in translation blocks.
- Fixed pause/resume for import and export jobs — paused batches are now re-dispatched on resume.
- Fixed export pause/cancel not stopping running batch jobs (added `shouldStop()` check).

### Security
- Added **ACL authorization** to all 32 AI Agent tools via `ChecksPermission` trait — tools check user permissions before executing write operations.
- Added **rate limiting** (`throttle:30,1`) on AI Agent chat endpoints.
- Added **input validation** in `ChatContext` — locale and channel codes validated against `[a-zA-Z0-9_-]` pattern to prevent injection in JSON path expressions.
- Fixed **15 unprotected API routes** — DELETE, PATCH, media upload, and option management endpoints now require proper ACL permissions.

### Breaking Changes
- **PHP 8.3 required** — PHP 8.2 is no longer supported.
- **Laravel 12** — removed `Kernel.php`, individual middleware files, and legacy service providers. Custom middleware must be registered in `bootstrap/app.php`.
- **MagicAI provider classes removed** — `Webkul\MagicAI\Services\OpenAI`, `Gemini`, `Groq`, `Ollama` replaced by `Webkul\MagicAI\Services\LaravelAiAdapter`.
- **ImageManager replaced** — `Webkul\Core\ImageCache\ImageManager` replaced by `Webkul\Core\ImageCache\ImageCache`.
- **New database tables** — 8 new tables for AI Agent infrastructure: `ai_agent_token_usage`, `ai_agent_conversations`, `ai_agent_messages`, `ai_agent_memories`, `ai_agent_changesets`, `ai_agent_tasks`, `magic_ai_platforms`.
- **API ACL expanded** — new `api.catalog.products.delete` and `api.catalog.categories.delete` permission nodes added. Existing API integrations with `permission_type=custom` may need updated scopes.

### Dependency Updates
- Upgraded `laravel/framework` to `^12.0`, `laravel/sanctum` to `^4.0`, `diglactic/laravel-breadcrumbs` to `^10.0`.
- Upgraded `pestphp/pest` to `^3.0`, `phpunit/phpunit` to `^11.0`, `nunomaduro/collision` to `^8.0`.
- Added `laravel/ai` `^0.3.2` and `laravel/boost` `^2.1` as new dependencies.
- Added `prism-php/prism` for AI Agent tool calling with multi-provider support.

---

# v1.0.x

## v1.0.0

### Features
- Added **PostgreSQL Support** for improved cross-database compatibility. Fixes issue: [#45](https://github.com/unopim/unopim/issues/45)
- Implemented **System Prompt Management** for configuring AI prompt behavior and max token settings.
- Added **Custom Prompts** for Magic AI content generation and **Product Values Translation** to translate an attribute value in multiple other languages with Magic AI.
- Introduced **Product Completeness**, providing completeness score and evaluation of product data quality based on completeness settings.
- Added **Product Bulk Edit** with advanced multi-product editing capabilities for faster workflows.
- Added **Product Update Webhook**, enabling external systems to react to real-time product updates.
- Added **Video Support** in the gallery attribute. Fixes issue: [#84](https://github.com/unopim/unopim/issues/84)
* Completeness calculation jobs use the system queue. Start queue worker using: `php artisan queue:work --queue=system,default`

### Improvements
- Optimized **Category Tree Rendering**, significantly reducing load times for large catalogs. Fixes issue: [#176](https://github.com/unopim/unopim/issues/176)
- Updated **Import Job Configuration**:
  - Timeout set to `0` for long-running jobs.
  - Batch size increased to `100`.
  - Stats calculation for job progress uses query instead of eager loading all batches for counting completed state.

### Bug Fixes

- Corrected handling of the `@lang` directive to ensure consistent and secure output rendering on the import job page.

### Dependency Updates
- Bump enshrined/svg-sanitize from `0.16.0` to `0.22.0`
- Bump phpoffice/phpspreadsheet from `1.29.9` to `1.30.0`

# v0.3.x

## v0.3.2 - 2025-08-26

### Fixes

* Fixed failure of product export when 'with media' filter is enabled
* Fixed category export job failing due to uninitialized file buffer

## v0.3.1 - 2025-08-22

### Fixes

* Fix: escape CSV formula operators in export files [CVE-2025-55745]
* Fix: added ACL permissions for mass action routes [CVE-2025-55745]

### Chores

* Chore: update security policy for supported version

## v0.3.0 - 2025-07-28

### Features

* Dynamic management of quick product export jobs
* Product datagrid now supports dynamic columns and filters
* Improved Magic AI functionality
* Enhanced product information section UI/UX
* Improved Elasticsearch filters for products and categories
* Improved export functionality using Elasticsearch
* Introduced upgrade guide and automated upgrade script
* Added Playwright tests for end-to-end testing

### Fixes & Enhancements

* Altered `text` column to `string` (`varchar`) and added relevant indexes
* Optimized category datagrid to sort by `id` instead of `_id`
* Improved performance for pages loading bulk data
* Improved file validation rules
* Enhanced product index functionality in Elasticsearch
* Resolved issue with invalid channel-locale change [#122](https://github.com/unopim/unopim/pull/122)
* Fixed unique attribute value duplication when creating variants
* Fixed issue preventing value entry in the Price section when multiple currencies are enabled
* Fixed inconsistent WYSIWYG field behavior during data transfer and API usage
* Updated validation rule to disallow only special characters, spaces, and dashes in the `Code` field
* Fixed error when downloading sample CSV files with non-public default storage
* Updated `X-Frame-Options` header to `SAMEORIGIN` in SecureHeaders middleware [#180](https://github.com/unopim/unopim/pull/180)

### Dependency Updates

* Upgraded `laravel/framework` from `10.48.23` to `10.48.29`
* Updated Finnish translations [#161](https://github.com/unopim/unopim/pull/161)
* Optimized attribute options grid performance for large datasets

# v0.2.x

## v0.2.0 - 2025-03-26
### ✨ **Features**
- Added disk parameter to `sanitizeSVG`. [#58](https://github.com/unopim/unopim/pull/58)
- Introduced dynamic import job filters. [#80](https://github.com/unopim/unopim/pull/80)
- Added in-app and email notifications. [#78](https://github.com/unopim/unopim/pull/78)
- New API endpoints for patching and deleting products/categories. [#98](https://github.com/unopim/unopim/pull/98)
- Implemented GUI installer for easier setup. [#55](https://github.com/unopim/unopim/pull/55)
- Added Magic Image feature. [#100](https://github.com/unopim/unopim/pull/100)
- "Powered by" message added to authentication screens. [#110](https://github.com/unopim/unopim/pull/110)

### 🛠 **Fixes & Enhancements**
- Fixed gallery image removal issue. [#90](https://github.com/unopim/unopim/pull/90)
- Enabled product status by default. [#89](https://github.com/unopim/unopim/pull/89)
- Quick export fix for selected products. [#116](https://github.com/unopim/unopim/pull/116)
- Fixed JSON encoding issues with special characters. [#104](https://github.com/unopim/unopim/pull/104)
- Prevented HTML entities from showing in flash messages. [#114](https://github.com/unopim/unopim/pull/114)
- Improved cumulative filter conditions. [#108](https://github.com/unopim/unopim/pull/108)
- Fixed TypeError with filterable dropdown column. [#106](https://github.com/unopim/unopim/pull/106)
- Improved CSS styling for GUI installer and image previews. [#73](https://github.com/unopim/unopim/pull/73)

### 🔄 **Dependency Updates**
- Upgraded `phpoffice/phpspreadsheet` to `1.29.9`. [#101](https://github.com/unopim/unopim/pull/101)
- Upgraded `league/commonmark` to `2.6.0`. [#74](https://github.com/unopim/unopim/pull/74)
- Upgraded `nesbot/carbon` to `2.72.6`. [#93](https://github.com/unopim/unopim/pull/93)


# v0.1.x

## v0.1.5 - 2024-10-25

### Enhancements
- **New Command**: Introduced the `user:create` command for streamlined user management ([#35](https://github.com/unopim/unopim/pull/35)).

### Bug Fixes
- **Database Compatibility**: Fixed an issue with import job creation due to the `longtext` column type in MariaDB, improving database compatibility and import stability ([#43](https://github.com/unopim/unopim/pull/43)).
- **Data Consistency**: Addressed an issue with merging old and new values during import to ensure accurate data synchronization ([#44](https://github.com/unopim/unopim/pull/44)).

**Full Changelog**: [v0.1.4...v0.1.5](https://github.com/unopim/unopim/compare/v0.1.4...v0.1.5)

## **v0.1.4 (17 October 2024)** - *Release*
* Security Issue #41: fixed Stored XSS

## **v0.1.3 (14 October 2024)** - *Release*

### Bug Fixes
* Issue #21: fix db:seed command throwing error when running after installation

### Changed
* Bump phpoffice/phpspreadsheet from 1.29.1 to 1.29.2
* Docker images for installation through docker

### Added
* #23: Gallery type attribute
* Executing data transfer jobs via terminal through 'php artisan unopim:queue:work {JobId} {userEmailId}'
* Job specific log files for data transfer jobs
* Datagrid Improvement: first and last page buttons (thanks to @helgvor-stoll)
* #26 Account page Improvement: UI locale and timezone field added

## **v0.1.2 (18nd September 2024)** - *Release*

### Changed
- Updated the test cases.
- French translation updated.

### Added
- Added MariaDB compatibility (thanks to @helgvor-stoll).
- Added Docker support (thanks to @jdecode).

## **v0.1.1 (22nd August 2024)** - *Release*

### Bug Fixes

* Fixed date format validation issues in both API and import processes.
* Added validation to ensure unique values during imports, even for empty fields.
* Resolved an issue where error reports were generated in an incorrect file format.
* Fixed an issue where the history tab failed to load for users without necessary permissions.
* Added validation to prevent non-existent options in select, multiselect, and checkbox fields during imports.
* Restricted import and export field separators to ',', ';', or '|'.
* Added a warning message for incorrect separator usage in import files.
* Fixed a bug where the category delete action did not function correctly during imports.
* Added a warning when the export folder lacks read/write permissions.
* Added a navigation button from the job tracking page to the job edit page.
* Fixed random filenames being generated for export files in export jobs.
* Corrected the export of channel-specific attribute values in product export files.
* Hid the field separator option for XLS and XLSX exports.
* Fixed an issue where the product count was not displaying correctly in category exports.
* Specified allowed file formats for category and product imports.
* Fixed a bug that allowed the same product to be added multiple times in the association section via the UI.
* Fixed boolean value history not displaying in category and product history sections.
* Ensured that at least one product image is visible when searching in the association section or viewing variants.
* Fixed a bug where the product count displayed as zero in the category datagrid.
* Corrected an issue where channel filtering by root category label showed no records in the channel datagrid.
* Changed status code to 200 for successful responses in attribute group, simple, and configurable product APIs.
* Fixed an issue where product prices were saved incorrectly when multiple currencies were added and the attribute was not channel-specific.
* Resolved an issue that prevented the generation of the auth token via API without first executing the "passport:keys" command.
* Fixed an issue that prevented multiple filters from being applied simultaneously in any datagrid.
* Fixed ACL permissions that allowed access to the create page of attribute groups and attributes without proper permissions.
* Added missing assigned and unassigned history generation in attribute families.
* Added missing history generation for import and export jobs.
* Fixed the search functionality in datagrids for import and export.
* Corrected an issue where the type column code was displayed instead of the label in import and export datagrids.
* Added a missing translation for the upload icon in the export datagrid.
* Fixed an issue where the variant product create API did not work when the common section lacked the variant attribute.
* Resolved a potential XSS attack vulnerability through imports and API for WYSIWYG text area fields.
* Restricted category fields from being created with the codes 'locale', 'parent', or 'code'.
* Fixed the validation message in the file upload section of import jobs.
* Fixed an issue where category fields did not display in sort order according to position value in the exported category file.
* Fixed media file path functionality not working in category and product imports.
* Fixed the "Download Sample" link being displayed below the import type field on the import edit page.
* Fixed an issue where Magic AI configuration credentials were not being saved.
* Fixed an issue where role history content was not fully visible on small screen sizes.
* Restricted the deletion of the logged-in user.
