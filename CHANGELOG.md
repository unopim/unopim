# v2.0.x

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
