// Dedicated fixture for the attribute-family suite.
//
// The default e2e target (127.0.0.1:8000) currently runs an OLDER checkout whose
// route group is `/admin/catalog/families`. The variant/family work under test
// lives in this branch, served at 192.168.15.243:8023 where the routes were
// renamed to `/admin/catalog/attribute-families`. These fixtures pin the suite to
// that server + its own saved storage state (auto-regenerated on demand) so they
// are independent of the shared default config.
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
