# Test Scenario Matrix

Apply every scenario category below to each module in `constants/modules.ts`, prioritizing source-specific rules listed in `businessRules`, `mandatoryFields`, and `dependencies`.

## Page-Level Coverage

- Smoke: route loads, authenticated shell renders, no 403/404/500, sidebar/menu item opens expected page.
- Sanity: create/edit form opens, mandatory field labels exist, primary grid actions are visible.
- Regression: CRUD, copy where available, mass update/delete, sorting, pagination, filters, search, export/download, and localized toast messages.
- End-to-end: create prerequisite metadata, create business entity, verify grid/API/database, mutate, export/import, delete/archive.
- Positive: valid minimal data, valid maximal data, valid optional fields, valid localized/channel-specific values.
- Negative: blank mandatory data, duplicate codes/SKUs/emails, malformed code/SKU, invalid type, invalid permissions, invalid IDs, stale forms.
- Boundary: maximum field lengths from migrations, empty optional fields, zero allowed errors, maximum upload sizes, pagination first/last page.
- Equivalence: valid/invalid code classes, enabled/disabled status, simple/configurable product type, CSV/XLSX/ZIP media classes.
- Exploratory: browser back/forward, reload after save, double submit, interrupted network, stale modal state.
- Security: XSS in search/text fields, SQL metacharacters in filters, CSRF on mutation, unauthorized role, unauthenticated access, unsafe webhook URL, installer takeover.
- Accessibility: landmark/heading structure, visible focus, form labels, modal focus trap, color-independent state, keyboard-only CRUD.
- Responsive/browser: desktop, tablet, mobile Chrome/Safari profiles, Chromium/Firefox/WebKit/Edge projects.
- API/database: response status/schema, auth errors, API/UI parity, database row count and key field persistence.
- Performance: grid first load, filtered search, import/export start/stats polling, API list latency, no runaway console errors.

## Module Hot Spots

- Products: SKU uniqueness, status boolean, variant combinations, media upload, associations, categories, bulk edit, filterable attributes, channel/locale guard.
- Attributes: unsupported reserved codes, swatch type conditional validation, option sort/update/delete, super attribute delete block.
- Categories: tree integrity, parent deletion, locale data, category media, API patch.
- Channels: root category/locales/currencies required, default/last deletion block.
- Users/Roles: avatar MIME-extension match, password confirmation, inactive admin login, custom ACL route denial.
- Imports: empty file, invalid file type, CSV/XLSX parsing, image ZIP validation, pause/resume/cancel, error report.
- Exports: filters by channel/locale/currency/attribute/family/category, generated file download, invalid filters.
- API Keys: generate/regenerate secrets, revoked key denial, scoped permission bypass tests.
- Magic AI/AI Agent: disabled credential, failed provider, throttling, prompt CRUD, rollback/audit trail.
- Webhooks: safe URL validation, log detail, mass delete, network failure retry.
- Installer: already-installed lock, requirement failure, invalid env/db/admin data.
