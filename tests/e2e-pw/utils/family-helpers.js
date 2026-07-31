// Reusable operations for the attribute-family E2E suite (runs against BASE_URL via family-fixtures).
// Admin polls /admin/get-notifications so `networkidle` never settles; navigations use `domcontentloaded` + explicit waits.
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
    // The option list stays open over the modal footer and swallows the first Save
    // click; focusing another field closes it (Escape would close the whole modal).
    await page.getByPlaceholder('Enter Code').click();
    await page.waitForTimeout(300);
  }
  await Promise.all([
    page.waitForURL(/\/attribute-families\/edit\/\d+/, { timeout: 45000 }),
    page.getByRole('button', { name: 'Save Attribute Family' }).last().click(),
  ]);
  await page.waitForSelector('.group_node', { timeout: 45000 });
  const id = page.url().match(/\/edit\/(\d+)/)[1];
  return { id, code };
}

/**
 * Delete a family by code from the index (search → delete → confirm). Safe if absent.
 * Scoped to the matching grid row: a looser match would delete whichever row happens
 * to carry the first Delete icon, which is another suite's family when they run together.
 */
async function deleteFamilyByCode(page, code) {
  await gotoIndex(page);
  await page.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1500);

  const row = page.locator('#app .row').filter({ hasText: code }).first();

  if (! await row.isVisible({ timeout: 3000 }).catch(() => false)) {
    return;
  }

  const del = row.locator('span[title="Delete"]').first();

  if (await del.isVisible({ timeout: 3000 }).catch(() => false)) {
    await del.click();
    await page.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click().catch(() => {});
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
async function selectMultiselect(page, inputName, optionText, scope = null) {
  const input = (scope ?? page).locator(`input[name="${inputName}"]`).first();
  // Match `.multiselect` as a whole word (not inner `.multiselect__tags`); the option list is a sibling of `__tags`.
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

/**
 * Search the unassigned list for a code, tick it, and bulk-assign it to a group.
 * One attribute per round: a fresh search replaces the list, dropping a selection
 * made on the previous one. Cheaper than cloning a family with every mapping it holds.
 */
async function assignAttributesToGroup(page, codes, groupLabel = 'General') {
  const panel = page.locator('div.mb-4').filter({ hasText: 'Unassigned Attributes' }).first();

  for (const code of codes) {
    await panel.locator('button.icon-search').click().catch(() => {});

    const search = panel.getByRole('textbox', { name: 'Search' });
    await search.click();
    await search.fill('');
    // The control filters on typed input; fill() alone leaves the query unset.
    await search.pressSequentially(code, { delay: 40 });

    await Promise.all([
      page.waitForResponse((response) => response.url().includes(`query=${code}`), { timeout: 30000 }),
      page.keyboard.press('Enter'),
    ]);

    const row = page.locator('#unassigned-attributes div.group').first();
    await row.waitFor({ state: 'visible', timeout: 20000 });

    const label = (await row.innerText()).trim().split('\n').pop().trim();

    await row.locator('button').first().click();

    // Scope to the bulk block: an "Assign …" button also exists outside this panel.
    const bulk = page.locator('input[name="bulk_group_picker"]').first()
      .locator('xpath=ancestor::div[contains(@class, "rounded-md")][1]');

    await selectMultiselect(page, 'bulk_group_picker', groupLabel, bulk);
    // Close the option list: left open, it swallows the click on Assign below it.
    await panel.getByText('Unassigned Attributes').first().click();

    await bulk.getByRole('button', { name: /^Assign/ }).first().click();

    // Presence, not visibility: the destination group may be collapsed.
    await expect(page.locator('#assigned-attribute-groups').getByText(label, { exact: true }))
      .toHaveCount(1, { timeout: 30000 });
  }
}

/**
 * Type a family label. The translatable field keeps its visible input unnamed on
 * purpose and carries each locale's value in a hidden input, so the value has to
 * go through the visible control of that field's group.
 */
async function setFamilyLabel(page, value, locale = 'en_US') {
  const group = page
    .locator(`input[name="${locale}[name]"]`)
    .first()
    .locator('xpath=ancestor::*[@data-control-group][1]');

  const visible = group.locator('input[type="text"]').first();

  await visible.fill(value);
  await visible.blur();

  await expect(page.locator(`input[name="${locale}[name]"]`).first()).toHaveValue(value);
}

/**
 * Trigger the tracked "Save changes" bar (or in-form button) and wait for the save
 * itself. The success flash is transient, so completion is taken from the write
 * response and the bar clearing rather than from catching the toast.
 */
async function saveFamilyEdit(page) {
  const bar = page.getByRole('button', { name: 'Save changes' });
  const named = page.getByRole('button', { name: 'Save Attribute Family' });
  await Promise.race([
    bar.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {}),
    named.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {}),
  ]);
  const target = (await bar.isVisible().catch(() => false)) ? bar : named;
  await target.scrollIntoViewIfNeeded().catch(() => {});

  const write = page.waitForResponse(
    (response) => response.request().method() !== 'GET'
      && /attribute-families\/edit\/\d+/.test(response.url()),
    { timeout: 120000 },
  );

  // JS click avoids "outside of viewport" flakiness on the sticky save bar.
  await target.evaluate((el) => el.click());

  const response = await write;

  expect(response.status(), `${response.request().method()} ${response.url()}`).toBeLessThan(400);

  await expect(bar).toBeHidden({ timeout: 60000 });
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
  setFamilyLabel,
  assignAttributesToGroup,
  withFamilyPage,
};
