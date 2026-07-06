# UnoPim Source Analysis

Generated from the repository source under `packages/Webkul/*`, `routes/*`, `database/*`, and the existing `tests/e2e-pw` suite.

## Architecture

UnoPim is a Laravel 12 PIM with a package-oriented Webkul architecture. The root app is thin; most controllers, routes, models, migrations, data grids, views, config, and validation live in package namespaces registered in `composer.json`.

Primary packages discovered:

- `Admin`: admin UI routes, Blade/Vue views, data grids, auth/profile, catalog/settings/data-transfer/configuration controllers, menu and ACL config.
- `AdminApi`: Passport-protected REST APIs for catalog/settings and API key integrations.
- `Attribute`, `Category`, `Product`, `Core`, `User`: domain models, migrations, repositories, rules, and seeders.
- `DataTransfer`: import/export job instances, trackers, importers/exporters config, queued batch processing.
- `Notification`: notification and user notification tables/models.
- `MagicAI` and `AiAgent`: prompts, platforms, credentials, agents, chat, executions, conversations, dashboards, throttled routes.
- `Webhook`: webhook settings/logs and safe URL validation.
- `Completeness`: product completeness settings, scores, dashboard data, family completeness.
- `HistoryControl`: audit history, version view/restore/delete.
- `Installer`, `AppUrlGuard`, `ElasticSearch`, `FPC`, `DebugBar`: install, URL guard, search indexing, cache, and dev tooling surfaces.

## UI Navigation

Menu entries are source-derived from `packages/Webkul/Admin/src/Config/menu.php` plus extension package menus.

- Dashboard: `/admin/dashboard`
- Catalog: products, categories, category fields, attributes, attribute groups, attribute families
- Data Transfer: tracker, imports, exports
- Settings: appearance, locales, currencies, channels, users, roles
- Configuration: integrations and nested configuration sections
- Help: resources page
- Extension routes: Magic AI, AI Agent, Webhooks, Completeness, History

## Security Model

Admin routes use the `admin` middleware and ACL route mapping. RBAC is configured in `packages/Webkul/Admin/src/Config/acl.php` and extension ACL files. API routes use `auth:api` with Passport. Login, SSO, and forgot password routes have throttling middleware.

## Existing Automation

An existing JavaScript Playwright suite exists in `tests/e2e-pw`, with UI and API tests plus JSON schemas and payload helpers. This generated framework is a separate strict TypeScript QA layer under `tests/e2e-framework` for review before any CI integration.
