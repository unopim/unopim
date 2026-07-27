// Reusable operations for the attribute-family E2E suite (runs against BASE_URL via
// family-fixtures). Selectors verified against the live rendered admin.
//
// NOTE: the admin polls /admin/get-notifications, so `networkidle` never settles.
// Every navigation uses `domcontentloaded` + an explicit wait for a page element.
const { expect } = require('@playwright/test');
const { generateUid } = require('./helpers');
const { ensureFamilyState, STATE_PATH, FAMILY_BASE_URL } = require('./ensure-family-state');

/**
 * Run a callback with a fresh authenticated page against the suite's target (used from
 * beforeAll/afterAll where the adminPage fixture is unavailable). Ensures the
 * saved session is valid first.
 */
async function withFamilyPage(browser, fn) {
  await ensureFamilyState();
  const context = await browser.newContext({ storageState: STATE_PATH, baseURL: FAMILY_BASE_URL });
  const page = await context.newPage();
  try {
    return await fn(page);
  } finally {
    await page.close();
    await context.close();
  }
}

const INDEX_PATH = '/admin/catalog/attribute-families';
const editPath = (id, tab = '') => `/admin/catalog/attribute-families/edit/${id}${tab ? `?${tab}` : ''}`;

/** Navigate and wait for the datagrid/app to be interactive (no networkidle). */
async function gotoIndex(page) {
  await page.goto(INDEX_PATH, { waitUntil: 'domcontentloaded' });
  await page.getByRole('button', { name: 'Create Attribute Family' }).waitFor({ state: 'visible', timeout: 30000 });
}

/**
 * Create a family via the index create modal. Lands on the edit page.
 * @returns {Promise<{id:string, code:string}>}
 */
async function createFamily(page, code = `fam_${generateUid()}`, { name, basedOn } = {}) {
  await gotoIndex(page);
  await page.getByRole('button', { name: 'Create Attribute Family' }).click();
  await page.getByPlaceholder('Enter Name').fill(name || code);
  await page.getByPlaceholder('Enter Code').fill(code);
  if (basedOn) {
    await selectMultiselect(page, 'based_on', basedOn).catch(() => {});
  }
  await Promise.all([
    page.waitForURL(/\/attribute-families\/edit\/\d+/, { timeout: 45000 }),
    page.getByRole('button', { name: 'Save Attribute Family' }).click(),
  ]);
  await page.waitForSelector('.group_node', { timeout: 45000 });
  const id = page.url().match(/\/edit\/(\d+)/)[1];
  return { id, code };
}

/** Delete a family by code from the index (search → delete → confirm). Safe if absent. */
async function deleteFamilyByCode(page, code) {
  await gotoIndex(page);
  await page.getByRole('textbox', { name: 'Search' }).fill(code);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1500);
  const del = page.locator('div', { hasText: code }).locator('span[title="Delete"]').first();
  if (await del.isVisible({ timeout: 3000 }).catch(() => false)) {
    await del.click();
    await page.getByRole('button', { name: 'Delete' }).click().catch(() => {});
    await page.waitForTimeout(1500);
  }
}

/** Navigate to a family edit tab: '', 'variants', 'completeness', 'history'. */
async function gotoTab(page, id, tab = '') {
  await page.goto(editPath(id, tab), { waitUntil: 'domcontentloaded' });
  await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
}

/**
 * Pick an option in a UnoPim searchable multiselect (vue-multiselect) identified
 * by the hidden input name. Opens the control via its wrapper, filters, clicks.
 */
async function selectMultiselect(page, inputName, optionText) {
  const input = page.locator(`input[name="${inputName}"]`).first();
  // Scope to the real `.multiselect` root, not the inner `.multiselect__tags` (both
  // contain the substring "multiselect", so match the class as a whole word) — the
  // option list is a sibling of `__tags` and falls outside a `__tags`-scoped search.
  const wrapper = input.locator('xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " multiselect ")][1]');
  await wrapper.click();
  if (optionText) {
    await input.pressSequentially(String(optionText), { delay: 15 }).catch(() => {});
  }
  // Scope to the opened wrapper so a sibling multiselect's options are ignored.
  const option = wrapper.locator('.multiselect__content-wrapper li, .multiselect__element')
    .filter({ hasText: optionText || /\S/ }).first();
  await option.waitFor({ state: 'visible', timeout: 8000 });
  await option.click();
}

/** Open the "Assign Attribute Group" modal, pick a group (first if none given), submit. */
async function assignGroup(page, groupText) {
  await page.getByText('Assign Attribute Group', { exact: true }).first().click();
  await page.waitForTimeout(600);
  await selectMultiselect(page, 'group', groupText);
  await page.locator('[role="dialog"], .modal-content, .modal').getByRole('button', { name: 'Assign Attribute Group' })
    .click()
    .catch(async () => { await page.getByRole('button', { name: 'Assign Attribute Group' }).last().click(); });
  await page.waitForTimeout(800);
}

/** Trigger the tracked "Save changes" bar (or in-form button) and confirm success. */
async function saveFamilyEdit(page) {
  const bar = page.getByRole('button', { name: 'Save changes' });
  const named = page.getByRole('button', { name: 'Save Attribute Family' });
  await Promise.race([
    bar.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {}),
    named.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {}),
  ]);
  const target = (await bar.isVisible().catch(() => false)) ? bar : named;
  await target.scrollIntoViewIfNeeded().catch(() => {});
  // JS click avoids "outside of viewport" flakiness on the sticky save bar.
  await target.evaluate((el) => el.click());
  await expect(page.locator('#app').getByText(/updated successfully/i).first())
    .toBeVisible({ timeout: 25000 });
}

module.exports = {
  INDEX_PATH,
  editPath,
  gotoIndex,
  createFamily,
  deleteFamilyByCode,
  gotoTab,
  selectMultiselect,
  assignGroup,
  saveFamilyEdit,
  withFamilyPage,
};
