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
  workers: isCI ? 4 : undefined,
  timeout: 90_000,
  expect: { timeout: 15_000 },
  globalSetup: './global-setup/global-setup.ts',
  globalTeardown: './global-teardown/global-teardown.ts',
  reporter: [
    // Inline PR annotations on CI; readable list locally.
    isCI ? ['github'] : ['list'],
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
    { name: 'chromium', use: { ...devices['Desktop Chrome'], channel: 'chrome' } },
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
