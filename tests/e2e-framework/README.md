# UnoPim Playwright Framework

A TypeScript, Page-Object-based Playwright framework for end-to-end testing of
the UnoPim admin panel and REST API. Tests are **data-driven** from a single
source-backed module registry (`constants/modules.ts`), so coverage scales with
the registry rather than with copy-pasted specs.

## Quick Start

```bash
cd tests/e2e-framework
cp environments/local.env.example .env
npm install
npx playwright install --with-deps chrome
npm run typecheck   # strict TypeScript gate
npm test            # run the suite (headless)
```

By default the framework runs the Chromium profile used in CI. To expand to
Firefox, WebKit, Edge, and mobile emulation, set `FULL_BROWSER_MATRIX=true`
after installing the required browsers.

## Test Coverage

The suite is a **functional smoke + regression foundation**. Every check is
generated for each admin module discovered in `constants/modules.ts`, so adding
a module to the registry automatically extends coverage.

| Area | Spec | Tags | What it verifies |
| --- | --- | --- | --- |
| Authentication | `tests/auth/authentication.spec.ts` | `@smoke` `@negative` `@security` | Valid admin login lands on an authenticated admin page (not `/login`); wrong credentials surface the server "check your credentials" flash; empty fields trigger client-side field validation. |
| Module load | `tests/modules/module-regression.spec.ts` | `@smoke` | Each admin module page loads with no 403/404/500 or Laravel exception. |
| Hostile input | `tests/modules/module-regression.spec.ts` | `@regression` | Grid search survives XSS/SQL-metacharacter input without crashing. |
| Keyboard | `tests/modules/module-regression.spec.ts` | `@keyboard` | Focus advances to a visible control via `Tab`. |
| Accessibility | `tests/modules/module-regression.spec.ts` | `@a11y` | No critical/serious axe violations (WCAG 2.0/2.1 A & AA). *Non-blocking in CI.* |
| Authorization | `tests/security/authorization.spec.ts` | `@authorization` | Unauthenticated users are redirected to login for every admin module. |
| Database | `tests/database/schema-validation.spec.ts` | `@database` | Each module's backing table is queryable. |
| Admin API | `tests/api/admin-api.spec.ts` | `@api` | List endpoints return a JSON envelope. *Skipped unless API credentials are configured.* |

The broader scenario catalogue (CRUD, boundary, import/export, API/UI parity,
etc.) is documented as the roadmap in
[`docs/test-scenario-matrix.md`](docs/test-scenario-matrix.md) and the
per-module rules in [`docs/functional-specification.md`](docs/functional-specification.md).

## Watching Tests Run in a Browser

CI runs **headless**, so nothing is shown on screen there. To watch tests
execute in a real browser locally:

```bash
npm run test:headed          # visible browser window
npm run test:debug           # step through with the Playwright Inspector
npx playwright test --ui     # interactive UI mode (time-travel, watch mode)
```

Every run also produces an HTML report with screenshots, traces, and video of
any failures — the best way to see what happened on a headless/CI run:

```bash
npm run report               # opens reports/html
```

Traces (`retain-on-failure`) can be opened with `npx playwright show-trace <file>`.

## Selective Runs

```bash
npm run test:smoke           # @smoke only
npm run test:regression      # @regression only
npm run test:api             # API specs (needs API credentials)
npm run test:a11y            # accessibility only
npx playwright test --grep-invert @a11y   # everything except accessibility (the CI gate)
```

## Project Structure

- `pages/` — Page Object Model (`BasePage` → `CrudPage`/`LoginPage`).
- `tests/` — auth, module, API, database, and security specifications.
- `fixtures/` — custom Playwright fixtures (page objects, API client, DB helper).
- `constants/` — the source-backed module registry (`modules.ts`).
- `api/` — OAuth-aware REST API client.
- `database/` — MySQL validation helpers.
- `utils/` — logging, random test data, accessibility, and environment helpers.
- `docs/` — functional spec, scenario matrix, and source analysis.
- `reports/` — HTML, JUnit, Allure, trace, video, and screenshot output (git-ignored).

## Continuous Integration

The `Playwright E2E` workflow provisions MySQL, migrates and seeds UnoPim with
deterministic admin credentials, boots `php artisan serve`, type-checks the
framework, then runs the gating suite. The accessibility audit runs as a
separate, non-blocking step while the admin UI is triaged for axe violations.

## Notes

This framework is intentionally separated from the legacy JavaScript suite in
`tests/e2e-pw` while it is reviewed and expanded.
