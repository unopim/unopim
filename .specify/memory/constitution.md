<!--
=== SYNC IMPACT REPORT ===
Version change: (none) → 1.0.0 (MAJOR - initial ratification)

Modified principles: N/A (first version)

Added sections:
  - 12 Core Principles (I through XII)
  - Security & Compliance Constraints
  - Development Workflow & Quality Gates
  - Governance

Removed sections: N/A (first version)

Templates requiring updates:
  ✅ .specify/templates/plan-template.md
     - "Constitution Check" section aligns with all 12 principles
  ✅ .specify/templates/spec-template.md
     - Functional requirements section compatible; no updates needed
  ✅ .specify/templates/tasks-template.md
     - Phase structure supports principle-driven task types
  ✅ .specify/templates/checklist-template.md
     - Generic template; no principle-specific references to update

Follow-up TODOs: None. All placeholders resolved.
=== END SYNC IMPACT REPORT ===
-->

# UnoPim Constitution

## Core Principles

### I. Modular Package Architecture

Every domain MUST be encapsulated in its own Webkul package managed
through Konekt Concord. Each package owns its models, migrations,
routes, controllers, views, and configuration. Cross-package
communication MUST occur through contracts (interfaces) resolved
via Laravel's service container — never through direct class
instantiation of another package's internals.

- Packages are registered in `config/concord.php` via their
  `ModuleServiceProvider`.
- Each `ModuleServiceProvider` extends
  `Webkul\Core\Providers\CoreModuleServiceProvider`.
- New domains MUST follow the existing package directory
  convention: `packages/Webkul/{PackageName}/src/`.
- A package MUST NOT reference another package's concrete
  models directly; it MUST use the corresponding Contract.

**Rationale:** Maintains bounded contexts within the monolith,
enabling independent development, testing, and future extraction
of packages into standalone services.

### II. Repository Pattern (NON-NEGOTIABLE)

All data access MUST go through repository classes that extend
`Webkul\Core\Eloquent\Repository`. Controllers and services MUST
NOT call Eloquent methods directly on models for CRUD operations.

- Every model that supports CRUD MUST have a corresponding
  repository.
- Repositories MUST declare the model contract via `model()`.
- Complex queries SHOULD use custom builder classes or scope
  methods on the repository, not inline raw SQL in controllers.
- Product creation/update MUST delegate to the product type
  instance's `create()` / `update()` methods via the repository.

**Rationale:** Centralizes data-access logic, enables consistent
event dispatching, and provides a single interception point for
cross-cutting concerns (caching, auditing, validation).

### III. Cross-Database Compatibility (NON-NEGOTIABLE)

All raw SQL and database-specific expressions MUST use
`GrammarQueryManager::getGrammar()` to obtain driver-appropriate
SQL fragments. Writing MySQL-specific, PostgreSQL-specific, or
SQLite-specific raw SQL outside of a Grammar implementation is
strictly prohibited.

- JSON operations MUST use `$grammar->jsonExtract()`.
- Aggregation MUST use `$grammar->groupConcat()`.
- Custom ordering MUST use `$grammar->orderByField()`.
- Boolean comparisons MUST use `$grammar->getBooleanValue()`.
- PostgreSQL sequence resets after bulk operations MUST use
  `DatabaseSequenceHelper::fixSequences()`.
- Supported drivers: MySQL 8.0+, PostgreSQL 14+, SQLite (test).

**Rationale:** UnoPim officially supports MySQL and PostgreSQL in
production and SQLite for testing. Grammar abstraction prevents
runtime failures when switching drivers and ensures CI parity.

### IV. Contract-Driven Design

Every Eloquent model MUST implement a corresponding Contract
(interface) defined in its package's `Contracts/` directory.
Services and repositories MUST type-hint against contracts, not
concrete classes. Concord's model binding resolves contracts to
implementations at runtime.

- Contract naming: `Webkul\{Package}\Contracts\{ModelName}`.
- Dependencies injected via constructor MUST use contract types
  or repository classes — never concrete model classes.
- New traits shared across packages MUST be placed in
  `Webkul\Core` with a clear single-responsibility purpose.

**Rationale:** Enables Concord's model-proxy system, supports
package overrideability, and enforces dependency inversion across
the modular monolith.

### V. Product Values Integrity

Product attribute data is stored in a JSON `values` column with
a strict four-key structure: `common`, `locale_specific`,
`channel_specific`, `channel_locale_specific`, plus `categories`
and `associations`. Direct JSON manipulation of this column is
strictly prohibited.

- Reading values MUST use
  `$attribute->getValueFromProductValues($values, $channel, $locale)`.
- Writing values MUST use
  `$attribute->setProductValue($value, $productValues, $channel, $locale)`.
- API value writes MUST use the `ValueSetter` facade
  (`setCommon`, `setLocaleSpecific`, `setChannelSpecific`,
  `setChannelLocaleSpecific`).
- Product creation MUST go through the type instance's
  `prepareProductValues()` pipeline.
- Attribute scope resolution (per-locale, per-channel) MUST
  be determined from the attribute's `value_per_locale` and
  `value_per_channel` flags.

**Rationale:** The JSON structure is the single source of truth
for product data. Bypassing the accessor/mutator pipeline risks
data corruption, locale contamination, and broken completeness
scoring.

### VI. Nested Set Integrity

Category tree structures use the kalnoy/nestedset library with
`_lft`, `_rgt`, and `parent_id` columns. Direct manipulation of
these columns is strictly prohibited.

- Tree mutations MUST use NodeTrait methods: `appendToNode()`,
  `prependToNode()`, `insertAfterNode()`, `insertBeforeNode()`.
- Tree queries MUST use scoped methods:
  `Category::scoped([])->defaultOrder()->get()->toTree()`.
- Category field values are stored in `additional_data` JSON
  column and MUST follow the same accessor patterns as product
  values.

**Rationale:** Manual `_lft`/`_rgt` modification corrupts the
nested set, causing orphaned nodes, infinite loops, and data
loss. The library maintains tree consistency through atomic
operations.

### VII. Dual-Guard Security Architecture

Authentication MUST follow the dual-guard pattern: session-based
(`admin` guard) for web routes, and OAuth2 via Laravel Passport
(`api` guard) for API routes. Authorization MUST use the RBAC
system with 80+ granular ACL permissions.

- Web routes MUST be protected by the `Bouncer` middleware
  which enforces session auth + ACL checks.
- API routes MUST be protected by `auth:api` +
  `ScopeMiddleware` which enforces OAuth2 token + ACL checks.
- New features exposing admin functionality MUST register
  corresponding ACL entries in the package's `Config/acl.php`
  (web) and `Config/api-acl.php` (API).
- Permission keys MUST follow the hierarchical format:
  `{module}.{resource}.{action}`.
- The `SecureHeaders` middleware MUST NOT be bypassed or
  weakened. Security headers (HSTS, X-Frame-Options,
  X-Content-Type-Options, X-XSS-Protection) MUST remain on
  all responses.
- API rate limiting (60 req/min default) MUST remain active.

**Rationale:** PIM systems contain commercially sensitive product
data. Dual-guard prevents session fixation on the API channel
and token theft on the web channel. Granular ACL enables
enterprise role separation.

### VIII. Event-Driven Lifecycle

All CRUD operations on domain entities MUST dispatch before/after
event pairs following the naming convention:
`{domain}.{entity}.{action}.{before|after}`.

- `before` events fire before the database operation.
- `after` events fire after the database operation and receive
  the affected entity.
- Event names MUST use dot-notation:
  `catalog.product.create.before`,
  `catalog.product.create.after`.
- Listeners MUST NOT perform blocking operations in `before`
  events unless they are validation gates.
- Webhook dispatching, notification sending, completeness
  recalculation, and cache invalidation MUST be triggered
  via `after` event listeners, not inline in controllers.

**Rationale:** Event pairs enable extensibility (webhooks,
notifications, history tracking, search indexing, cache
invalidation) without coupling business logic to
cross-cutting concerns.

### IX. Multi-Channel & Multi-Locale First

Every feature that handles product or category data MUST support
multi-channel and multi-locale scoping from inception. Hardcoding
to a single locale or channel is prohibited.

- Translatable entities MUST use the `TranslatableModel` trait
  with a corresponding `*_translations` table.
- Request-scoped locale MUST be resolved from the
  `Accept-Language` header (API) or admin session preference
  (web).
- Channel context MUST be resolved from middleware and passed
  through the request pipeline.
- Attributes with `value_per_locale=true` or
  `value_per_channel=true` MUST be handled through the
  scope-aware value accessors.
- UnoPim supports 33 locales; all user-facing strings MUST
  use `@lang('package::app.key')` translation helpers.

**Rationale:** PIM is inherently a multi-channel, multi-locale
system. Retrofitting i18n/multi-channel into features creates
data model debt and user-facing bugs.

### X. Client Layer Standards

All admin UI MUST use the established component architecture:
server-rendered Blade templates with Vue.js 3 Islands for
interactivity, styled with Tailwind CSS.

- Blade templates MUST use `<x-admin::*>` named components
  (never raw HTML for standard UI elements like forms, modals,
  buttons, dropdowns, grids).
- Vue components MUST be registered globally via
  `app.component()` in `@pushOnce('scripts')` blocks.
- All UI elements MUST support dark mode via `dark:` Tailwind
  variants. Light-only styling is prohibited.
- Forms MUST use VeeValidate for client-side validation.
- DataGrids MUST extend the `Webkul\DataGrid\DataGrid`
  abstract class with proper column definitions, filters,
  and mass actions.
- Icons MUST come from the `unopim-admin` icon font.
- No centralized state store (Vuex/Pinia); component-level
  state with `$emitter` for cross-component communication.

**Rationale:** Consistent UI reduces maintenance burden, ensures
accessibility, and prevents visual regressions. The Islands
architecture balances server-rendering performance with
client-side interactivity.

### XI. History & Auditability

Entities that require change tracking MUST implement the
`HistoryAuditable` interface and use the `HistoryTrait`.
Entities that need human-readable history presentation MUST
also implement `PresentableHistoryInterface`.

- History records are stored via the `HistoryControl` package.
- Presenters MUST format field changes for admin display
  (e.g., `BooleanPresenter`, `ProductValuesPresenter`).
- History tracking MUST NOT be disabled for entities that
  implement it, except in bulk import/export operations where
  performance exemptions are documented.

**Rationale:** Enterprise PIM users require audit trails for
compliance, rollback capabilities, and change attribution.

### XII. Simplicity & YAGNI

Start with the simplest implementation that satisfies the
requirement. Do not add abstraction layers, configuration
options, feature flags, or extensibility points for hypothetical
future needs.

- Three similar lines of code are preferable to a premature
  abstraction.
- New packages MUST have a clear, demonstrable business need.
- Error handling MUST only cover scenarios that can actually
  occur. Do not add defensive code against impossible states.
- Validate at system boundaries (user input, API requests,
  external services) — trust internal code and framework
  guarantees.
- Complexity MUST be justified in code review. If it cannot
  be justified, it MUST be simplified.

**Rationale:** Over-engineering increases maintenance burden,
slows development velocity, and makes the codebase harder to
understand. The existing 19-package architecture provides
sufficient separation; additional abstraction should be earned
through demonstrated need.

## Security & Compliance Constraints

- **OWASP Top 10 Compliance:** All code MUST be free of XSS,
  SQL injection, CSRF, command injection, and other OWASP
  Top 10 vulnerabilities. HTML user input MUST be sanitized
  via the `HtmlPurifier` trait (API) or Blade's default
  escaping (web).
- **Secrets Management:** Environment secrets MUST reside in
  `.env` and MUST NOT be hardcoded in source files, committed
  to version control, or logged. Configuration access MUST
  use `config()` or `env()` helpers.
- **Tenant Isolation (Multi-Tenant Deployments):** When
  multi-tenancy is enabled, all database queries, Elasticsearch
  indices, cache keys, session stores, and queue jobs MUST be
  scoped to the active tenant. Cross-tenant data leakage is a
  P0 security incident.
- **File Storage Security:** Uploaded files MUST be validated
  for type and size. Storage paths MUST NOT allow directory
  traversal. File serving MUST go through application
  middleware, not direct public directory access.
- **Dependency Security:** Composer and npm dependencies MUST
  be audited for known vulnerabilities before version bumps.
  Deprecated or abandoned packages MUST be replaced.

## Development Workflow & Quality Gates

- **Code Style:** All PHP code MUST pass Laravel Pint
  (PSR-12 based) linting. The `linting_tests.yml` CI workflow
  enforces this on every push and pull request.
- **Testing Requirements:**
  - New features MUST include Pest PHP tests covering the
    primary success path and critical edge cases.
  - UI-affecting changes MUST include or update Playwright E2E
    specs where applicable.
  - Tests MUST run against SQLite (unit/integration) and MySQL
    (CI) to validate cross-database compatibility.
  - Run locally: `./vendor/bin/pest --parallel`.
  - E2E: `cd tests/e2e-pw && npx playwright test`.
- **CI/CD Gates:** Three GitHub Actions workflows MUST pass
  before merging: Linting (Pint), Pest PHP tests, Playwright
  E2E tests. No workflow MUST be skipped or overridden without
  documented justification.
- **Code Review:** All changes MUST be reviewed against the
  principles in this constitution. The reviewer MUST verify:
  - Repository pattern adherence (Principle II)
  - GrammarQueryManager usage for raw SQL (Principle III)
  - Contract interfaces on new models (Principle IV)
  - Before/after event pairs on CRUD (Principle VIII)
  - Dark mode support on UI changes (Principle X)
  - ACL entries for new permissions (Principle VII)
- **Branching:** Feature branches MUST be created from
  `master`. Branch names SHOULD follow the format
  `feature/{description}` or `fix/{description}`.
- **Cache Management:** After configuration or migration
  changes, `php artisan config:clear && php artisan cache:clear`
  MUST be run. CI pipelines handle this automatically.

## Governance

This constitution is the supreme governing document for the
UnoPim project. It supersedes all ad-hoc practices, informal
conventions, and undocumented patterns. When a conflict exists
between this document and any other guidance, this document
prevails.

**Amendment Procedure:**

1. Propose the amendment as a pull request modifying this file.
2. The PR description MUST include: the principle affected,
   the rationale for change, and an impact assessment of
   existing code that would be affected.
3. The amendment MUST be reviewed and approved by at least one
   project maintainer.
4. Upon merge, the `CONSTITUTION_VERSION` MUST be incremented
   per semantic versioning rules:
   - **MAJOR:** Principle removed, redefined, or made
     backward-incompatible.
   - **MINOR:** New principle added or existing principle
     materially expanded.
   - **PATCH:** Clarifications, wording fixes, non-semantic
     refinements.
5. The `LAST_AMENDED_DATE` MUST be updated to the merge date.

**Compliance Review:**

- All pull requests and code reviews MUST verify compliance
  with the principles in this constitution.
- The `Constitution Check` section in
  `.specify/templates/plan-template.md` MUST be completed
  before implementation begins on any planned feature.
- Violations discovered in existing code SHOULD be filed as
  technical debt issues and prioritized for remediation.

**Runtime Guidance:**

- For detailed implementation patterns, refer to the
  layer-specific skill documents:
  - `.claude/commands/unopim-data.md` (DATA/EXTERNAL)
  - `.claude/commands/unopim-infra.md` (INFRASTRUCTURE)
  - `.claude/commands/unopim-domain.md` (DOMAIN)
  - `.claude/commands/unopim-app.md` (APPLICATION)
  - `.claude/commands/unopim-middleware.md` (MIDDLEWARE)
  - `.claude/commands/unopim-client.md` (CLIENT)
- For orchestrated task execution, use `.claude/commands/go.md`.

**Version**: 1.0.0 | **Ratified**: 2026-02-14 | **Last Amended**: 2026-02-14
