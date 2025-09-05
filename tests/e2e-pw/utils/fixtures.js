// utils/fixtures.js
const base = require('@playwright/test');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '../.state/admin-auth.json');

exports.test = base.test.extend({
  adminPage: async ({ browser }, use) => {
    // Create context with saved admin auth state
    const context = await browser.newContext({ storageState: STORAGE_STATE });
    const page = await context.newPage();

    // Automatically navigate to admin dashboard
    await page.goto('/admin/dashboard');
    await page.waitForLoadState('networkidle');

    // Pass to test
    await use(page);

    // Cleanup
    await page.close();
    await context.close();
  }
});

exports.expect = base.expect;
