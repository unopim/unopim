import { defineConfig, devices } from '@playwright/test';
import { environment } from './config/environment';

const isCI = Boolean(process.env.CI);
const browserMatrix = process.env.FULL_BROWSER_MATRIX === 'true';

export default defineConfig({
  testDir: './tests',
  outputDir: './reports/artifacts',
  fullyParallel: true,
  forbidOnly: isCI,
  retries: isCI ? 2 : 0,
  // 2 workers on CI: 4 parallel Chromium instances (plus MySQL, PHP server and
  // the memory-heavy axe runs) exhaust a standard ~7GB runner and get killed
  // with exit 137 (OOM/SIGKILL). Two is the stable ceiling.
  workers: isCI ? 2 : undefined,
  timeout: 90_000,
  expect: { timeout: 15_000 },
  globalSetup: './global-setup/global-setup.ts',
  globalTeardown: './global-teardown/global-teardown.ts',
  reporter: [
    // Always print each test name as it runs; add inline PR annotations on CI.
    ['list'],
    ...(isCI ? [['github'] as [string]] : []),
    ['html', { outputFolder: 'reports/html', open: 'never' }],
    ['junit', { outputFile: 'reports/junit/results.xml' }],
    ['allure-playwright', { outputFolder: 'reports/allure-results' }]
  ],
  use: {
    baseURL: environment.baseUrl,
    storageState: environment.storageStatePath,
    actionTimeout: 15_000,
    navigationTimeout: 45_000,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: isCI ? 'retain-on-failure' : 'off',
    locale: 'en-US',
    timezoneId: 'UTC',
    ignoreHTTPSErrors: true
  },
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        channel: 'chrome',
        // --disable-dev-shm-usage is essential in CI: the container's /dev/shm
        // is tiny, and Chromium otherwise exhausts it and is killed (exit 137).
        launchOptions: { args: ['--disable-dev-shm-usage', '--disable-gpu', '--no-sandbox'] }
      }
    },
    ...(browserMatrix
      ? [
          { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
          { name: 'webkit', use: { ...devices['Desktop Safari'] } },
          { name: 'edge', use: { ...devices['Desktop Edge'], channel: 'msedge' } },
          { name: 'mobile-chrome', use: { ...devices['Pixel 7'] } },
          { name: 'mobile-safari', use: { ...devices['iPhone 15'] } }
        ]
      : [])
  ]
});
