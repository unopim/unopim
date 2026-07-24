// Reusable operations for the Digital Product Passport E2E suite. Follows the
// attribute-family suite's conventions (utils/family-fixtures.js /
// utils/family-helpers.js): its own storage state, domcontentloaded + explicit
// waits (no networkidle — the admin polls /admin/get-notifications).
const base = require('@playwright/test');
const { generateUid } = require('../utils/helpers');
const { ensureFamilyState, STATE_PATH, FAMILY_BASE_URL } = require('../utils/ensure-family-state');
const {
  createFamily,
  deleteFamilyByCode,
  gotoTab,
  assignGroup,
  saveFamilyEdit,
  withFamilyPage,
} = require('../utils/family-helpers');

exports.BASE_URL = FAMILY_BASE_URL;
exports.generateUid = generateUid;
exports.withFamilyPage = withFamilyPage;
exports.createFamily = createFamily;
exports.deleteFamilyByCode = deleteFamilyByCode;
exports.gotoTab = gotoTab;
exports.assignGroup = assignGroup;
exports.saveFamilyEdit = saveFamilyEdit;

exports.test = base.test.extend({
  passportState: [async ({}, use) => {
    await ensureFamilyState();
    await use(STATE_PATH);
  }, { scope: 'worker' }],

  adminPage: async ({ browser, passportState }, use) => {
    const context = await browser.newContext({ storageState: passportState, baseURL: FAMILY_BASE_URL });
    const page = await context.newPage();
    await use(page);
    await page.close();
    await context.close();
  },
});

exports.expect = base.expect;

/**
 * Create a family carrying the `dpp` group with `dpp_manufacturer_name`
 * (common bucket — no locale/channel scoping, so a single value satisfies
 * every locale) marked as a completeness requirement on the given channel.
 * Requires `unopim:passport:install-attributes` to have already been run
 * against this environment.
 *
 * KNOWN ISSUE (verified live, 2026-07-23): `assignGroup()`/`selectMultiselect()`
 * from `utils/family-helpers.js` target the OLD modal + vue-multiselect
 * "Assign Attribute Group" flow. The attribute-family edit page has since
 * been redesigned to a drag/tick-and-assign "Family Structure" layout
 * (Assigned Groups list + Unassigned Attributes list with per-row/bulk
 * "assign" buttons, no modal, no `input[name="group"]`), so this call
 * currently no-ops silently and the family never actually gains the `dpp`
 * group. This is pre-existing staleness in the shared family E2E helpers
 * (not specific to this feature) — fixing it belongs in
 * `utils/family-helpers.js` itself, shared by every consumer of
 * `assignGroup()`, once the new layout's real selectors are captured.
 * Until then, this suite's family/completeness/product-creation steps are
 * written and structurally sound but do not yet produce a family that
 * actually carries the `dpp` group end-to-end.
 */
async function createDppFamily(page, channelCode) {
  const code = `dppfam_${generateUid()}`;
  const family = await createFamily(page, code, { basedOn: 'default' });

  await gotoTab(page, family.id, '');
  await assignGroup(page, 'Digital Product Passport');
  await saveFamilyEdit(page).catch(() => {});

  // Mark dpp_manufacturer_name as required for the given channel so the
  // product reaches 100% completeness with a single common-bucket value.
  // The completeness grid searches the attribute's translated label
  // ("Manufacturer Name"), not its code.
  await gotoTab(page, family.id, 'completeness');
  const search = page.getByRole('textbox', { name: 'Search' }).first();
  await search.fill('Manufacturer Name');
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1200);

  // The completeness datagrid re-creates its row nodes on each notification poll, so
  // drive vue-multiselect's native select synchronously (focus opens the dropdown; the
  // option's mouse events fire @select) to pick the channel by its code.
  await page.locator('input[name="channel_requirements"]').first().waitFor({ state: 'attached', timeout: 25000 });
  await page.waitForTimeout(1500);
  await page.evaluate(async (code) => {
    const control = document.querySelector('input[name="channel_requirements"]')?.closest('.multiselect');
    if (! control) {
      return;
    }
    control.querySelector('.multiselect__input')?.focus();
    await new Promise((resolve) => setTimeout(resolve, 400));
    const option = [...control.querySelectorAll('.multiselect__option')]
      .find((el) => el.textContent.trim().toLowerCase().includes(code.toLowerCase()))
      ?? control.querySelector('.multiselect__option');
    ['mousedown', 'mouseup', 'click'].forEach((type) => option?.dispatchEvent(new MouseEvent(type, { bubbles: true })));
  }, channelCode);
  await page.waitForTimeout(800);

  return family;
}

/**
 * Create a simple product against the given family/SKU via the "Create
 * Product" modal, landing on the edit page.
 * @returns {Promise<{id: string, sku: string}>}
 */
async function createProduct(page, familyName, sku) {
  await page.goto('/admin/catalog/products', { waitUntil: 'domcontentloaded' });
  await page.getByRole('button', { name: 'Create Product' }).click();

  await page.locator('[name="type-searchbox"], input[placeholder="Select option"]').first().click();
  await page.getByText('Simple', { exact: true }).first().click();

  await page.locator('input[placeholder="Select option"]').last().click();
  await page.getByText(familyName, { exact: false }).first().click();

  await page.getByRole('textbox', { name: 'SKU' }).fill(sku);

  await Promise.all([
    page.waitForURL(/\/catalog\/products\/edit\/\d+/, { timeout: 30000 }),
    page.getByRole('button', { name: 'Save Product' }).click(),
  ]);

  const id = page.url().match(/\/edit\/(\d+)/)[1];

  return { id, sku };
}

module.exports = {
  ...module.exports,
  createDppFamily,
  createProduct,
};
