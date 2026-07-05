# UnoPim Playwright Framework

This directory contains a professional, source-backed Playwright automation framework for UnoPim end-to-end testing.

## Quick Start

```bash
cd tests/e2e-framework
cp environments/local.env.example .env
npm install
npx playwright install --with-deps chrome
npm test
```

By default, the framework runs the Chromium profile used in CI. To expand coverage to additional browsers, set `FULL_BROWSER_MATRIX=true` after installing the required browsers.

## Project Structure

- `pages/`: Page Object Model implementation with shared base and CRUD page classes.
- `tests/`: Auth, module, API, database, security, accessibility, and responsive-ready specifications.
- `fixtures/`: Custom Playwright fixtures and shared test setup.
- `utils/`: Logging, retry, file upload, screenshot, network, accessibility, and visual regression helpers.
- `helpers/`: Authentication and workflow helpers.
- `constants/`: Discovered module registry and scenario taxonomy.
- `api/`: OAuth-aware API client helpers.
- `database/`: MySQL database validation helpers.
- `reports/`: HTML, JUnit, Allure, trace, video, and screenshot outputs.
- `docs/`: Framework documentation, scenarios, and implementation notes.

## Common Commands

```bash
npm run test:smoke
npm run test:regression
npm run test:api
npm run test:headed
npm run report
```

## Documentation

For a complete setup guide, refer to [docs/getting-started.md](docs/getting-started.md).

## Notes

This framework is intentionally separated from the existing JavaScript suite in `tests/e2e-pw` while it is being reviewed and expanded. Module-driven tests are derived from the discovery layer in `constants/modules.ts`.
