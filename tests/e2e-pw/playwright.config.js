const { defineConfig, devices } = require('@playwright/test');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '.state/admin-auth.json');

module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: process.env.CI
    ? [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]]
    : [['html', { outputFolder: 'playwright-report' }]],
  timeout: 30_000,
  expect: { timeout: 10_000 },
  globalSetup: require.resolve('./global-setup.js'),
  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    storageState: STORAGE_STATE,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    actionTimeout: 10_000,
    navigationTimeout: 15_000,
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
});
