const { defineConfig, devices } = require('@playwright/test');
const path = require('path');
const os = require('os');

const isCI = !!process.env.CI;
const STORAGE_STATE = path.resolve(__dirname, '.state/admin-auth.json');

/**
 * Worker count strategy:
 * - CI: half the available CPUs (nginx + PHP-FPM handles concurrency)
 * - Local: all CPUs minus 1 (leave one for the dev server)
 * - Minimum 2 workers to parallelize across test files
 */
const workerCount = isCI
  ? Math.max(2, Math.floor(os.cpus().length / 2))
  : Math.max(2, os.cpus().length - 1);

module.exports = defineConfig({
  testDir: './tests',

  /* Run tests within each file sequentially, but files in parallel across workers */
  fullyParallel: false,

  /* Fail CI if test.only() is accidentally committed */
  forbidOnly: isCI,

  /* Retry once in CI to handle transient network/rendering flakes */
  retries: isCI ? 1 : 0,

  /* Parallel workers across test files */
  workers: workerCount,

  /**
   * Reporters:
   * - CI: list (live output) + html (artifact on failure)
   * - Local: html with auto-open on failure
   */
  reporter: isCI
    ? [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]]
    : [['html', { outputFolder: 'playwright-report', open: 'on-failure' }]],

  /* Per-test timeout: 60s is enough with nginx serving requests concurrently */
  timeout: 60_000,

  /* Assertion timeout: 15s for Vue components to render */
  expect: { timeout: 15_000 },

  /* Pre-authenticate admin session before all tests */
  globalSetup: require.resolve('./global-setup.js'),

  use: {
    /* Base URL — configurable via env for different environments */
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',

    /* Reuse authenticated session across all tests */
    storageState: STORAGE_STATE,

    /* Traces only on retry — keeps artifacts small, available when needed */
    trace: 'on-first-retry',

    /* Screenshots only on failure — saves disk I/O during passing runs */
    screenshot: 'only-on-failure',

    /* No video recording — significant performance overhead */
    video: 'off',

    /* Action timeout: clicks, fills, selects — 15s is enough with fast server */
    actionTimeout: 15_000,

    /* Navigation timeout: page.goto — 30s handles slow pages */
    navigationTimeout: 30_000,

    /* Reduce motion to speed up animations */
    reducedMotion: 'reduce',

    /* Set locale for consistent date/number formatting */
    locale: 'en-US',

    /* Viewport for consistent rendering */
    viewport: { width: 1280, height: 720 },
  },

  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        headless: true,
        /* Launch options for performance */
        launchOptions: {
          args: [
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--no-sandbox',
          ],
        },
      },
    },
  ],
});
