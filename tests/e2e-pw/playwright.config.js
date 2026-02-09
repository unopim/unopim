const { defineConfig, devices } = require('@playwright/test');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '.state/admin-auth.json');

module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
  ],
  globalSetup: require.resolve('./global-setup.js'),
  use: {
    baseURL: 'http://127.0.0.1:8000',
    storageState: STORAGE_STATE,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
});

