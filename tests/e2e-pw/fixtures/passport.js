// Reusable operations for the DPP E2E suites. Mirrors the attribute-family suite: own storage state,
// domcontentloaded + explicit waits (no networkidle — admin polls /admin/get-notifications).
//
// PREREQ (once per env): seed the canonical `dpp_e2e` family + config the specs assume:
//   docker exec unopim-unopim-fpm-1 php artisan unopim:passport:install-preset
//   docker exec unopim-unopim-fpm-1 php artisan tinker tests/e2e-pw/scripts/seed-dpp-e2e.php
const base = require('@playwright/test');
const { generateUid } = require('../utils/helpers');
const { ensureFamilyState, STATE_PATH, FAMILY_BASE_URL } = require('../utils/ensure-family-state');
const { withFamilyPage, selectMultiselect } = require('../utils/family-helpers');

exports.BASE_URL = FAMILY_BASE_URL;
exports.generateUid = generateUid;
exports.withFamilyPage = withFamilyPage;

const HIDE_OVERLAYS_SCRIPT = `
  (function() {
    var s = document.createElement('style');
    s.id = 'pw-hide-widget';
    s.textContent = '.ap-shell, .ap-panel, .ap-backdrop, .phpdebugbar, .phpdebugbar-open-handler { display: none !important; }';
    if (document.head) { document.head.appendChild(s); }
    else { document.addEventListener('DOMContentLoaded', function() { document.head.appendChild(s); }); }
  })();
`;

// The canonical family seeded by scripts/seed-dpp-e2e.php. Persistent — never deleted by the suite.
const DPP_FAMILY = { code: 'dpp_e2e', name: 'DPP E2E' };
exports.DPP_FAMILY = DPP_FAMILY;

// dpp attribute value/document buckets, mirroring DppAttributeSeeder::ATTRIBUTES. Drives the product-edit
// input name `values[<bucket>][...][code]` (Attribute::getAttributeInputFieldName) and whether a field is
// a document (file) rather than a value.
const DPP_FIELDS = {
  dpp_material_composition:      { locale: true,  channel: false, doc: false },
  dpp_substances_of_concern:     { locale: true,  channel: false, doc: false },
  dpp_recycled_content_pct:      { locale: false, channel: false, doc: false },
  dpp_carbon_footprint:          { locale: false, channel: false, doc: false },
  dpp_energy_consumption:        { locale: false, channel: false, doc: false },
  dpp_durability_statement:      { locale: true,  channel: false, doc: false },
  dpp_repairability_score:       { locale: false, channel: false, doc: false },
  dpp_spare_parts_availability:  { locale: true,  channel: false, doc: false },
  dpp_care_instructions:         { locale: true,  channel: false, doc: false },
  dpp_disassembly_guide:         { locale: false, channel: false, doc: true },
  dpp_manufacturer_name:         { locale: false, channel: false, doc: false },
  dpp_manufacturing_site:        { locale: true,  channel: false, doc: false },
  dpp_country_of_origin:         { locale: false, channel: false, doc: false },
  dpp_supply_chain_notes:        { locale: true,  channel: false, doc: false },
  dpp_end_of_life_instructions:  { locale: true,  channel: false, doc: false },
  dpp_take_back_scheme:          { locale: true,  channel: true,  doc: false },
  dpp_declaration_of_conformity: { locale: false, channel: false, doc: true },
  dpp_test_reports:              { locale: false, channel: false, doc: true },
  dpp_certificates:              { locale: false, channel: false, doc: true },
  dpp_gtin:                      { locale: false, channel: false, doc: false },
  dpp_model_identifier:          { locale: false, channel: false, doc: false },
  dpp_batch_identifier:          { locale: false, channel: false, doc: false },
  dpp_warranty_terms:            { locale: true,  channel: true,  doc: false },
  // plain source attributes seeded alongside the family (field-mapping + custom-field scenarios).
  origin_country:                { locale: false, channel: false, doc: false },
  shelf_life:                    { locale: false, channel: false, doc: false },
};
exports.DPP_FIELDS = DPP_FIELDS;

/** The product-edit input name for a value attribute, given the active channel/locale. */
function fieldInputName(code, { channelCode = 'default', localeCode = 'en_US' } = {}) {
  const meta = DPP_FIELDS[code] ?? { locale: false, channel: false };

  if (meta.locale && meta.channel) {
    return `values[channel_locale_specific][${channelCode}][${localeCode}][${code}]`;
  }
  if (meta.channel) {
    return `values[channel_specific][${channelCode}][${code}]`;
  }
  if (meta.locale) {
    return `values[locale_specific][${localeCode}][${code}]`;
  }

  return `values[common][${code}]`;
}
exports.fieldInputName = fieldInputName;

exports.test = base.test.extend({
  passportState: [async ({}, use) => {
    await ensureFamilyState();
    await use(STATE_PATH);
  }, { scope: 'worker' }],

  adminPage: async ({ browser, passportState }, use) => {
    const context = await browser.newContext({ storageState: passportState, baseURL: FAMILY_BASE_URL });
    // Same overlay hiding as utils/fixtures.js: on an APP_DEBUG host the Debugbar
    // sits over the unsaved-changes bar and swallows its clicks. No-op in CI.
    await context.addInitScript(HIDE_OVERLAYS_SCRIPT);
    const page = await context.newPage();
    await use(page);
    await page.close();
    await context.close();
  },
});

exports.expect = base.expect;

/**
 * POST JSON to an admin endpoint using the app's own CSRF scheme. The admin panel has no csrf-token
 * meta tag; it relies on the encrypted `XSRF-TOKEN` cookie which Laravel's VerifyCsrfToken decrypts
 * from the `X-XSRF-TOKEN` header (exactly what the bundled axios sends). Returns {status, body}.
 */
async function adminPost(page, path, body = null) {
  return page.evaluate(
    async ({ path, body }) => {
      const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
      const res = await fetch(path, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-XSRF-TOKEN': xsrf,
        },
        body: body === null ? undefined : JSON.stringify(body),
      });

      return { status: res.status, body: await res.json().catch(() => null) };
    },
    { path, body },
  );
}
exports.adminPost = adminPost;

/** Read a DataGrid's rows via its own AJAX JSON branch (X-Requested-With). */
async function fetchGridRows(page, path) {
  return page.evaluate(async (url) => {
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });

    return res.json();
  }, path);
}
exports.fetchGridRows = fetchGridRows;

/** Rows from a UnoPim DataGrid AJAX payload (`records`), tolerant of alternative keys. */
function gridRecords(grid) {
  return grid.records ?? grid.data ?? grid.rows ?? [];
}
exports.gridRecords = gridRecords;

/**
 * Resolve the numeric id of a settings entity (channel/locale) by code. Searched
 * through the grid's own filter rather than read off page 1 — an environment with
 * more than a page of channels/locales would otherwise silently resolve nothing.
 */
async function settingsIdByCode(page, path, code) {
  const query = new URLSearchParams({ 'filters[all][]': code }).toString();
  const grid = await fetchGridRows(page, `${path}${path.includes('?') ? '&' : '?'}${query}`);
  const row = gridRecords(grid).find((r) => r.code === code);

  return row?.id;
}
exports.settingsIdByCode = settingsIdByCode;

/**
 * The canonical DPP family is seeded backend-side (scripts/seed-dpp-e2e.php); the suite only reads it.
 * Returns its {code, name} — never creates or deletes a family, so the shared fixture stays stable.
 */
async function ensureDppFamily() {
  return { ...DPP_FAMILY };
}
exports.ensureDppFamily = ensureDppFamily;

/**
 * Create a simple product against the given family/SKU via the "Create Product" modal, landing on the
 * edit page. `familyName` must be the family's translated NAME (what the modal dropdown shows).
 * @returns {Promise<{id: string, sku: string}>}
 */
async function createProduct(page, familyName, sku) {
  await page.goto('/admin/catalog/products', { waitUntil: 'domcontentloaded' });
  await page.getByRole('button', { name: 'Create Product', exact: true }).click();

  // Both selects are vue-multiselect controls; drive them via their wrapper (input hidden until focus).
  await page.locator('input[name="type"]').first().waitFor({ state: 'attached', timeout: 20000 });
  await selectMultiselect(page, 'type', 'Simple');
  // The family select is async (typeahead) — the option text is the family's translated name.
  await selectMultiselect(page, 'attribute_family_id', familyName);

  await page.locator('input[name="sku"]').first().fill(sku);

  await Promise.all([
    page.waitForURL(/\/catalog\/products\/edit\/\d+/, { timeout: 30000 }),
    page.getByRole('button', { name: 'Save Product' }).click(),
  ]);

  const id = page.url().match(/\/edit\/(\d+)/)[1];

  return { id, sku };
}
exports.createProduct = createProduct;

/**
 * The channel locale ids the passport panel publishes, read from the product-edit panel DOM
 * (`.passport-publish-all-btn[data-locale-ids]`) — exactly the ids the publish action posts, and
 * paginated-grid-proof unlike the locales datagrid.
 * @returns {Promise<number[]>}
 */
async function passportLocaleIds(page) {
  const btn = page.locator('.passport-publish-all-btn');
  await btn.first().waitFor({ state: 'attached', timeout: 20000 });

  return page.evaluate(() => {
    const el = document.querySelector('.passport-publish-all-btn');

    return el ? JSON.parse(el.dataset.localeIds || '[]') : [];
  });
}
exports.passportLocaleIds = passportLocaleIds;

/**
 * Robustly pick an option in a `v-select-handler` select, verifying the hidden value input actually
 * committed (the control's searchbox shares the field name, so a mis-timed type-and-click can leave the
 * value empty). Retries a few times; asserts the hidden input by [type=hidden] to dodge the searchbox.
 */
async function selectHandlerOption(page, hiddenName, optionLabel) {
  const hidden = page.locator(`input[type="hidden"][name="${hiddenName}"]`);
  await hidden.waitFor({ state: 'attached', timeout: 20000 });
  const container = hidden.locator('xpath=parent::*');
  const multiselect = container.locator('.multiselect').first();

  for (let attempt = 0; attempt < 3; attempt++) {
    await multiselect.click();
    const option = container.locator('.multiselect__content-wrapper li, .multiselect__element')
      .filter({ hasText: optionLabel }).first();
    await option.click({ timeout: 5000 }).catch(() => {});

    const value = await hidden.inputValue().catch(() => '');
    if (value) {
      return value;
    }
    await page.waitForTimeout(300);
  }

  throw new Error(`select-handler "${hiddenName}" did not commit a value for "${optionLabel}"`);
}
exports.selectHandlerOption = selectHandlerOption;

/** Navigate to a product's edit page and wait for the attribute panel to be interactive. */
async function gotoProductEdit(page, productId) {
  await page.goto(`/admin/catalog/products/edit/${productId}`, { waitUntil: 'domcontentloaded' });
  await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
}
exports.gotoProductEdit = gotoProductEdit;

/**
 * Fill the required product attributes the product-edit form enforces on save for the `default`-cloned
 * family: the localized `name` and the common `url_key`. (WYSIWYG description fields aren't enforced.)
 */
async function fillProductBasics(page, sku, { channelCode = 'default', localeCode = 'en_US' } = {}) {
  await page.locator(`input[name="values[channel_locale_specific][${channelCode}][${localeCode}][name]"]`).first().fill(`E2E ${sku}`);
  await page.locator('input[name="values[common][url_key]"]').first().fill(sku.toLowerCase());
}
exports.fillProductBasics = fillProductBasics;

/** Fill a dpp value field on the product-edit page by attribute code (waits for it to render). */
async function fillDppField(page, code, value, opts = {}) {
  const name = fieldInputName(code, opts);
  const field = page.locator(`input[name="${name}"], textarea[name="${name}"]`).first();
  await field.waitFor({ state: 'visible', timeout: 20000 });
  await field.fill(String(value));
}
exports.fillDppField = fillDppField;

/**
 * A field name carries array syntax (`values[common][sku]`), which is not a usable id; controls render
 * the tokenised form instead and labels point their `for` at that. Mirrors `form_control_id()`.
 */
function controlId(name) {
  return name.replace(/[^A-Za-z0-9_.:-]+/g, '_').replace(/^_+|_+$/g, '');
}

/**
 * Attach a file to a dpp document field by attribute code. The media component's file input carries a
 * dynamic Vue id and a `[]`-suffixed name, so scope to the field's control-group via its `label[for]`.
 */
async function uploadDppFile(page, code, filePath, opts = {}) {
  const name = fieldInputName(code, opts);
  const label = page.locator(`label[for="${controlId(name)}"]`).first();
  await label.waitFor({ state: 'attached', timeout: 20000 });
  const group = label.locator('xpath=ancestor::*[@data-control-group][1]');
  await group.locator('input[type="file"]').first().setInputFiles(filePath);
}
exports.uploadDppFile = uploadDppFile;

/**
 * Map of the passport panel's channel locales to their locale ids, read from the product-edit panel DOM.
 * @returns {Promise<Record<string, number>>}
 */
async function passportLocaleMap(page) {
  await page.locator('#passport-panel').waitFor({ state: 'attached', timeout: 20000 });

  return page.evaluate(() => {
    const map = {};
    document.querySelectorAll('#passport-panel tr[data-locale-code]').forEach((tr) => {
      const code = tr.dataset.localeCode;
      const btn = tr.querySelector('.passport-publish-btn[data-locale-id]');
      if (code && btn) {
        map[code] = Number(btn.dataset.localeId);
      }
    });

    return map;
  });
}
exports.passportLocaleMap = passportLocaleMap;

/**
 * The signed operator/authority links and QR carrier link the passport panel minted for a locale row,
 * read from the product-edit panel DOM after publishing. Only present once a version is live.
 *
 * The row's actions live behind an "Actions" dropdown (`x-admin::dropdown`, teleported), so the menu
 * items are not descendants of the row (or even of `#passport-panel`) once opened — open the row's
 * dropdown first, then read the teleported items from the document.
 * @returns {Promise<{operator: string|null, authority: string|null, carrier: string|null}>}
 */
async function passportRowLinks(page, localeCode) {
  await page.locator('#passport-panel').waitFor({ state: 'attached', timeout: 20000 });

  // The passport table lives inside a product.section-drawer, which starts
  // closed (`v-show="isOpen"`); open it via its section-card toggle before
  // interacting with anything inside.
  if (! (await page.locator('#passport-panel').isVisible())) {
    // Two elements match this title: an in-form DPP fields accordion and the
    // sidebar section-card that opens the drawer (the one offering "View").
    await page.locator('[role="button"]').filter({ hasText: 'Digital Product Passport' })
      .filter({ hasText: 'View' })
      .first()
      .click();
    await page.locator('#passport-panel').waitFor({ state: 'visible', timeout: 10000 });
  }

  const row = page.locator(`#passport-panel tr[data-locale-code="${localeCode}"]`);
  const toggle = row.locator('button.icon-chevron-down');

  if (await toggle.count() === 0) {
    return { operator: null, authority: null, carrier: null };
  }

  await toggle.first().click();
  await page.locator('.passport-copy-link-btn, a[download]').first().waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});

  const links = await page.evaluate(() => {
    const linkByLabel = (needle) => {
      const btn = [...document.querySelectorAll('.passport-copy-link-btn')]
        .find((b) => (b.dataset.label || '').toLowerCase().includes(needle));

      return btn ? btn.dataset.link : null;
    };

    return {
      operator: linkByLabel('operator'),
      authority: linkByLabel('authority'),
      carrier: document.querySelector('a[download]')?.getAttribute('href') ?? null,
    };
  });

  await page.keyboard.press('Escape');

  return links;
}
exports.passportRowLinks = passportRowLinks;

/** Trigger the tracked save (sticky "unsaved changes" bar, falling back to an in-form submit) and confirm. */
async function saveProduct(page) {
  const barBtn = page.locator('[data-unsaved-save]');
  const named = page.getByRole('button', { name: 'Save Product' });

  await Promise.race([
    barBtn.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {}),
    named.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {}),
  ]);

  const target = (await barBtn.isVisible().catch(() => false)) ? barBtn : named;
  await target.evaluate((el) => el.click());

  await base.expect(page.locator('#app').getByText(/updated successfully|saved successfully/i).first())
    .toBeVisible({ timeout: 25000 });
}
exports.saveProduct = saveProduct;

/**
 * POST the passport publish action via authenticated fetch (session cookie + XSRF). Returns
 * {status, body}. The panel button posts the same shape; fetch keeps the test independent of it.
 */
async function publishViaFetch(page, productId, channelId, localeIds) {
  return adminPost(page, `/admin/catalog/passports/publish/${productId}`, { channel_id: channelId, locale_ids: localeIds });
}
exports.publishViaFetch = publishViaFetch;

/**
 * Publish and wait until every requested locale has a live version. Absorbs the async gap between the
 * queued publish job (publication queue) and the completeness job (system queue) by re-dispatching the
 * idempotent publish until versions appear or the deadline passes.
 * @returns {Promise<Array<{locale_code: string, version: number|null}>>}
 */
async function publishAndWait(page, productId, channelId, localeIds, { expected = localeIds.length, timeout = 45000 } = {}) {
  const deadline = Date.now() + timeout;
  let rows = [];

  while (Date.now() < deadline) {
    const publish = await publishViaFetch(page, productId, channelId, localeIds);
    base.expect(publish.status, JSON.stringify(publish.body)).toBe(200);

    await page.waitForTimeout(1500);

    const status = await fetchGridRows(page, `/admin/products/${productId}/passport`);
    rows = (status.rows || []).filter((row) => row.version != null);

    if (rows.length >= expected) {
      return rows;
    }
  }

  return rows;
}
exports.publishAndWait = publishAndWait;

/** The publications datagrid row for a SKU (its uuid, id, status). */
async function passportRowForSku(page, sku) {
  const grid = await fetchGridRows(page, '/admin/catalog/passports');

  return gridRecords(grid).find((r) => r.sku === sku);
}
exports.passportRowForSku = passportRowForSku;

/** Withdraw a publication by id via authenticated fetch. Returns the HTTP status. */
async function withdrawViaFetch(page, publicationId) {
  const res = await adminPost(page, `/admin/catalog/passports/withdraw/${publicationId}`);

  return res.status;
}
exports.withdrawViaFetch = withdrawViaFetch;
