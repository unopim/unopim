// utils/fixtures.js
const base = require('@playwright/test');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '../.state/admin-auth.json');

exports.test = base.test.extend({
  /**
   * Authenticated admin page fixture.
   * Creates a browser context with pre-saved admin session.
   * Each test navigates to its own page via navigateTo().
   */
  adminPage: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: STORAGE_STATE });
    const page = await context.newPage();

    await use(page);

    await page.close();
    await context.close();
  },

  /** Unique identifier for test data isolation in parallel execution */
  uid: async ({}, use) => {
    await use(Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
  },
});

exports.expect = base.expect;
