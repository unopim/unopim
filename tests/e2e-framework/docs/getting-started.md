# Getting Started with the UnoPim Playwright Framework

This guide provides a professional and practical introduction to the Playwright-based end-to-end testing setup for UnoPim.

## Prerequisites

Before running the suite, make sure the following are available:

- Node.js 22 or later (matches the CI runtime)
- A running UnoPim application instance
- A configured database and environment file
- Chrome browser support for the default CI-compatible execution profile

## Environment Setup

1. Change into the Playwright framework directory:

   ```bash
   cd tests/e2e-framework
   ```

2. Copy the default environment template:

   ```bash
   cp environments/local.env.example .env
   ```

3. Review the values in `.env` and update them to match your local environment.

4. Install the required dependencies:

   ```bash
   npm install
   ```

5. Install the Playwright browser dependencies:

   ```bash
   npx playwright install --with-deps chrome
   ```

## Running Tests

Use the following commands depending on the scope of validation you need:

```bash
npm test
npm run test:smoke
npm run test:regression
npm run test:api
npm run test:headed
```

## Reporting

After a test run, reports can be viewed with:

```bash
npm run report
```

The framework stores traces, screenshots, and HTML reports under the `reports/` directory.

## Recommended Workflow

- Use `npm run test:smoke` for quick confidence checks.
- Use `npm run test:regression` for broader regression coverage.
- Use `npm run test:api` for API assertion scenarios.
- Use `npm run test:headed` when debugging UI issues interactively.

## Notes

The Playwright suite is intentionally separated from the existing JavaScript test setup while it is being reviewed and expanded. Keeping the framework modular helps maintain stable page objects, fixtures, and reusable helper logic.
