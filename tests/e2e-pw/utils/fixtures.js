// utils/fixtures.js
const base = require('@playwright/test');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '../.state/admin-auth.json');

/**
 * Init-script injected into every page to hide the Agenting PIM chat widget.
 * The widget adds buttons, inputs, and search fields that collide with the
 * admin page's own elements, causing Playwright strict-mode violations.
 * Hiding via CSS removes them from the accessibility tree so getByRole()
 * no longer matches them.  agentingPIM.spec.js opts out by using the
 * `adminPageWithWidget` fixture instead.
 */
const HIDE_WIDGET_SCRIPT = `
  (function() {
    var s = document.createElement('style');
    s.id = 'pw-hide-widget';
    s.textContent = '.ap-shell { display: none !important; }';
    if (document.head) { document.head.appendChild(s); }
    else { document.addEventListener('DOMContentLoaded', function() { document.head.appendChild(s); }); }
  })();
`;

exports.test = base.test.extend({
  /**
   * Authenticated admin page fixture (widget hidden).
   * Creates a browser context with pre-saved admin session.
   * Each test navigates to its own page via navigateTo().
   */
  adminPage: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: STORAGE_STATE });
    await context.addInitScript(HIDE_WIDGET_SCRIPT);
    const page = await context.newPage();

    await use(page);

    await page.close();
    await context.close();
  },

  /**
   * Authenticated admin page WITH the chat widget visible.
   * Used only by tests that verify the widget itself (agentingPIM.spec.js).
   */
  adminPageWithWidget: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: STORAGE_STATE });
    const page = await context.newPage();

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
