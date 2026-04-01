const { defineConfig, devices } = require('@playwright/test');
const path = require('path');
const os = require('os');

const STORAGE_STATE = path.resolve(__dirname, '.state/admin-auth.json');
const workerCount = process.env.CI
  ? Math.max(2, Math.floor(os.cpus().length / 2))
  : Math.max(2, os.cpus().length - 1);

module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: workerCount,
  reporter: [['html', { outputFolder: 'playwright-report', open: 'never' }]],
  timeout: 60_000,
  expect: { timeout: 15_000 },
  globalSetup: require.resolve('./global-setup.js'),
  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    storageState: STORAGE_STATE,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    actionTimeout: 15_000,
    navigationTimeout: 60_000,
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'], headless: true } },
  ],
});
