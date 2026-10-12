const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

const dirtyGroups = async (page) =>
  page.$$eval('[data-control-group].unsaved-dirty', groups =>
    groups.map(group => (group.querySelector('label')?.textContent || '').replace(/\s+/g, ' ').trim())
  );

/**
 * A products export is the only entity type that renders the `locales`
 * scope filter, which these tests need to exercise.
 */
async function createProductExport(adminPage, code) {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();

  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);

  await adminPage.locator('#export-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
  await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();

  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();

  await clickSaveAndExpect(adminPage, 'Save changes', /Export created successfully/i);
}

async function deleteExport(adminPage, code) {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const deleteBtn = adminPage.locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible({ timeout: 20000 });
  }
}

test.describe('Export profile filter rehydration', () => {
  test('does not mark dependent filters dirty when the edit page loads', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `exp-rehydrate-${uid}`;

    await createProductExport(adminPage, code);

    await adminPage.getByRole('link', { name: 'Edit' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.waitForTimeout(1500);

    expect(await dirtyGroups(adminPage)).toEqual([]);

    await expect(adminPage.locator('button[data-unsaved-save]')).toBeHidden();

    await deleteExport(adminPage, code);
  });

  test('still marks a filter dirty when the user changes it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `exp-rehydrate-${uid}`;

    await createProductExport(adminPage, code);

    await adminPage.getByRole('link', { name: 'Edit' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.waitForTimeout(1500);

    const locales = adminPage.locator('[data-control-group]:has(input[name="filters[locales]"])');

    await locales.locator('.multiselect').click();

    const option = locales.locator('.multiselect__element').first();
    await option.waitFor({ state: 'visible' });
    await option.click();

    await expect
      .poll(async () => (await dirtyGroups(adminPage)).length, { timeout: 5000 })
      .toBeGreaterThan(0);

    await deleteExport(adminPage, code);
  });
});
