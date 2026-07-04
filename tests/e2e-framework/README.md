# UnoPim Playwright Framework

Source-backed TypeScript Playwright automation framework for UnoPim.

## Run

```bash
cd tests/e2e-framework
cp environments/local.env.example .env
npm install
npm test
```

By default the framework runs Chromium only, matching the existing CI browser install. Set `FULL_BROWSER_MATRIX=true` after installing all browsers to run Firefox, WebKit, Edge, and mobile projects.

## Structure

- `pages/`: Page Object Model with base and reusable CRUD pages.
- `tests/`: Auth, module, API, database, security, accessibility, responsive-ready specs.
- `fixtures/`: Custom Playwright fixtures.
- `utils/`: Logger, retry, file upload, screenshots, network, accessibility, visual regression.
- `helpers/`: Auth and workflow helpers.
- `constants/`: Discovered module registry and scenario taxonomy.
- `api/`: OAuth-aware API helper.
- `database/`: MySQL database helper.
- `reports/`: HTML, JUnit, Allure, traces, videos, screenshots.
- `ci/` and `docker/`: GitHub Actions, Jenkins, Docker assets.

## Notes

This framework is intentionally separate from the existing JavaScript suite in `tests/e2e-pw` while it is reviewed. Module-driven tests expand automatically from `constants/modules.ts`.
