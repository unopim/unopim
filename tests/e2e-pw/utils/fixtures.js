// utils/fixtures.js
const base = require('@playwright/test');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '../.state/admin-auth.json');

exports.test = base.test.extend({
  /**
   * Authenticated admin page fixture.
   * Creates a browser context with pre-saved admin session.
   * If the stored session has been invalidated (e.g. by a prior logout test),
   * re-authenticates automatically and persists the fresh session back to
   * admin-auth.json so subsequent tests reuse the valid session without
   * hitting the login rate limiter.
   */
  adminPage: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: STORAGE_STATE });
    const page = await context.newPage();

    // Verify the stored session is still valid by navigating to an authenticated page
    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded', timeout: 30000 });

    // If redirected to login, re-authenticate and persist the fresh session
    if (page.url().includes('/admin/login')) {
      await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
      await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
      await page.getByRole('button', { name: 'Sign In' }).click();
      await page.waitForLoadState('networkidle');

      // Persist the fresh session so subsequent tests don't need to re-login
      await page.context().storageState({ path: STORAGE_STATE });
    }

    await use(page);

    await page.close();
    await context.close();
  },

  /** Unique identifier for test data isolation in parallel execution */
  uid: async ({}, use) => {
    const { randomBytes } = require('crypto');
    await use(Date.now().toString(36) + randomBytes(4).toString('hex'));
  },
});

exports.expect = base.expect;
