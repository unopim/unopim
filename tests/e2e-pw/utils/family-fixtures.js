// Dedicated fixture for the attribute-family suite.
//
// This suite needs the `/admin/catalog/attribute-families` routes, so it keeps
// its own storage state (auto-regenerated on demand) rather than sharing the
// default one, which may have been captured against another host. The target
// itself follows BASE_URL (see ensure-family-state.js).
const base = require('@playwright/test');
const { ensureFamilyState, STATE_PATH, FAMILY_BASE_URL } = require('./ensure-family-state');

const HIDE_WIDGET_SCRIPT = `
  (function() {
    var s = document.createElement('style');
    s.id = 'pw-hide-widget';
    // Hide chat widget, promo bar, and the Laravel debugbar — the debugbar sits at
    // the bottom and intercepts clicks on the sticky "Save changes" bar.
    s.textContent = '.ap-shell{display:none!important}#unopim-promo-bar{display:none!important}.phpdebugbar,.phpdebugbar-open-handler{display:none!important}';
    if (document.head) { document.head.appendChild(s); }
    else { document.addEventListener('DOMContentLoaded', function() { document.head.appendChild(s); }); }
  })();
`;

exports.FAMILY_BASE_URL = FAMILY_BASE_URL;

exports.test = base.test.extend({
  // Worker-scoped: log in once per worker if the saved state is missing/stale.
  familyState: [async ({}, use) => {
    await ensureFamilyState();
    await use(STATE_PATH);
  }, { scope: 'worker' }],

  adminPage: async ({ browser, familyState }, use) => {
    const context = await browser.newContext({
      storageState: familyState,
      baseURL: FAMILY_BASE_URL,
    });
    await context.addInitScript(HIDE_WIDGET_SCRIPT);
    const page = await context.newPage();
    await use(page);
    await page.close();
    await context.close();
  },
});

exports.expect = base.expect;
