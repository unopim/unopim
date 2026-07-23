// utils/fixtures.js
const base = require('@playwright/test');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '../.state/admin-auth.json');

/**
 * Init-script injected into every page to hide overlays that collide with the
 * admin UI in tests:
 *  - `.ap-shell` — the Agenting PIM chat widget, whose buttons/inputs cause
 *    Playwright strict-mode violations. agentingPIM.spec.js opts out via the
 *    `adminPageWithWidget` fixture.
 *  - `.phpdebugbar` — the dev Debugbar, a fixed bottom overlay that on the dev
 *    server (APP_DEBUG) sits over the unsaved-changes bar and intercepts pointer
 *    events on its Save/Discard buttons. Absent in CI, so the rule is a no-op there.
 * Hiding via CSS also removes them from the accessibility tree so getByRole()
 * no longer matches them.
 */
const HIDE_WIDGET_SCRIPT = `
  (function() {
    var s = document.createElement('style');
    s.id = 'pw-hide-widget';
    s.textContent = '.ap-shell, .phpdebugbar { display: none !important; }';
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
   *
   * The widget honours `general.magic_ai.agentic_pim.open_by_default` (default true),
   * so on a fresh dev DB the panel auto-opens and the floating "Open Agenting PIM"
   * button is hidden. Tests assert against the closed state, so we seed
   * sessionStorage with isOpen=false before any page navigation. The widget's
   * restoreState() reads this and keeps the panel closed regardless of the
   * server-side default.
   */
  adminPageWithWidget: async ({ browser }, use) => {
    const context = await browser.newContext({ storageState: STORAGE_STATE });
    await context.addInitScript(() => {
      try {
        sessionStorage.setItem('agenting_pim_state', JSON.stringify({ isOpen: false }));
      } catch (e) {}
    });
    const page = await context.newPage();

    await use(page);

    await page.close();
    await context.close();
  },

  /**
   * Widget-visible fixture WITHOUT the per-navigation sessionStorage re-seed.
   * `adminPageWithWidget` re-injects `{ isOpen: false }` on every navigation,
   * which masks any regression in the widget's own cross-page state persistence.
   * This fixture lets the widget manage its own state exactly as a real user's
   * browser would, so tests can assert that a manually-closed panel stays closed
   * after PJAX navigation (navigation.js unmounts/remounts the Vue app per page).
   */
  adminPageWithWidgetNoSeed: async ({ browser }, use) => {
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
