// utils/fixtures.js
const base = require('@playwright/test');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '../.state/admin-auth.json');

exports.test = base.test.extend({
  adminPage: async ({ browser }, use) => {
    // Create context with saved admin auth state
    const context = await browser.newContext({ storageState: STORAGE_STATE });
    const page = await context.newPage();

    // Navigate to dashboard to validate session and warm up the app
    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded', timeout: 30000 });

    // Pass to test
    await use(page);

    // Cleanup
    await page.close();
    await context.close();
  },

  /** Unique identifier for test data isolation in parallel execution */
  uid: async ({}, use) => {
    await use(Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
  },
});

exports.expect = base.expect;
