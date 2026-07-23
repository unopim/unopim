const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Channel-derived locale activation.
 *
 * The channel form lists every locale, not just the active ones. Attaching one
 * activates it; detaching it deactivates it when nothing else still uses it.
 *
 * `af_ZA` is a seeded, non-default locale that no channel or user references on a
 * clean install, which makes it a safe subject for the enable/disable round trip.
 */
const TARGET_CODE = 'af_ZA';
const TARGET_NAME = 'Afrikaans (South Africa)';

/**
 * Open a multiselect and return its search input.
 *
 * The caret is the only always-clickable target: once a tag is selected it covers
 * the middle of the control, so clicking the control itself hits the tag instead.
 */
async function openMultiselect(adminPage, controlId) {
  const control = adminPage.locator(`#${controlId}`);
  const search = control.locator('input.multiselect__input');

  if (! await search.isVisible().catch(() => false)) {
    await control.locator('.multiselect__select').click();
    await search.waitFor({ state: 'visible', timeout: 15000 });
  }

  return { control, search };
}

/**
 * Filter a multiselect by text and pick the matching option.
 */
async function selectOption(adminPage, controlId, optionName) {
  const { control, search } = await openMultiselect(adminPage, controlId);

  await search.fill(optionName);

  const option = control.getByRole('option', { name: optionName }).first();
  await expect(option).toBeVisible({ timeout: 15000 });
  await option.click();

  await expect(control.locator('.multiselect__tag').filter({ hasText: optionName })).toHaveCount(1);
  await adminPage.keyboard.press('Escape');
}

/**
 * Remove an already selected option from a multiselect by its tag label.
 */
async function removeSelectedOption(adminPage, controlId, optionName) {
  const tag = adminPage.locator(`#${controlId} .multiselect__tag`).filter({ hasText: optionName }).first();

  await tag.waitFor({ state: 'visible', timeout: 15000 });
  await tag.locator('i.multiselect__tag-icon').click();
  await expect(adminPage.locator(`#${controlId} .multiselect__tag`).filter({ hasText: optionName })).toHaveCount(0);
}

/**
 * Read the status label rendered by the locales DataGrid for a code.
 *
 * @returns {Promise<'Enabled'|'Disabled'>}
 */
async function readLocaleStatus(adminPage, code) {
  await navigateTo(adminPage, 'locales');
  await searchInDataGrid(adminPage, code, 'Search by code');

  const row = adminPage.locator('div.row.grid.cursor-pointer').filter({ hasText: code }).first();
  await row.waitFor({ state: 'visible', timeout: 30000 });

  return (await row.innerText()).includes('Disabled') ? 'Disabled' : 'Enabled';
}

/**
 * Force the locale into the disabled state so the test starts from a known baseline.
 */
async function ensureLocaleDisabled(adminPage, code) {
  if (await readLocaleStatus(adminPage, code) === 'Disabled') {
    return;
  }

  const row = adminPage.locator('div.row.grid.cursor-pointer').filter({ hasText: code }).first();
  await row.locator('span[title="Edit"]').first().click();
  await adminPage.locator('label[for="status"]').click();
  await clickSaveAndExpect(adminPage, 'Save Locale', /Locale updated successfully/i);
}

/**
 * Delete a channel by code. Silently succeeds when it is already gone.
 */
async function deleteChannel(adminPage, code) {
  await navigateTo(adminPage, 'channels');
  await searchInDataGrid(adminPage, code);

  const deleteBtn = adminPage.locator('div.row.grid.cursor-pointer').filter({ hasText: code }).first()
    .locator('span[title="Delete"]').first();

  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
}

test.describe('Channel locale activation', () => {
  test('Attaching an inactive locale to a channel enables it and detaching disables it', async ({ adminPage }) => {
    const code = `${generateUid()}act`;

    await ensureLocaleDisabled(adminPage, TARGET_CODE);
    expect(await readLocaleStatus(adminPage, TARGET_CODE)).toBe('Disabled');

    try {
      await adminPage.goto('/admin/settings/channels/create', { waitUntil: 'load' });

      await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
      await adminPage.locator('input[name="en_US\\[name\\]"]').fill(`${code} Channel`);

      const { control: rootCategory } = await openMultiselect(adminPage, 'root_category_id');
      await rootCategory.getByRole('option').first().click();

      await selectOption(adminPage, 'locales', 'English (United States)');

      // The whole point: an inactive locale is offered by the channel form.
      await selectOption(adminPage, 'locales', TARGET_NAME);
      await selectOption(adminPage, 'currencies', 'US Dollar');

      await clickSaveAndExpect(adminPage, 'Save Channel', /Channel created successfully/i);

      expect(await readLocaleStatus(adminPage, TARGET_CODE)).toBe('Enabled');

      await navigateTo(adminPage, 'channels');
      await searchInDataGrid(adminPage, code);
      await adminPage.locator('div.row.grid.cursor-pointer').filter({ hasText: code }).first()
        .locator('span[title="Edit"]').first().click();
      await adminPage.waitForLoadState('networkidle');

      await removeSelectedOption(adminPage, 'locales', TARGET_NAME);
      await clickSaveAndExpect(adminPage, 'Save Channel', /Update Channel Successfully/i);

      expect(await readLocaleStatus(adminPage, TARGET_CODE)).toBe('Disabled');
    } finally {
      await deleteChannel(adminPage, code);
    }
  });
});
