const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

async function createExport(adminPage, code, format = 'CSV') {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  
  await adminPage.locator('#export-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
  await adminPage.getByRole('option', { name: 'Users' }).locator('span').first().click();

  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: format }).locator('span').first().click();
  
  await clickSaveAndExpect(adminPage, 'Save Export', /Export created successfully/i);
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

test.describe('User Export Jobs', () => {
  test('Create User Export (CSV) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `user-csv-${uid}`;
    await createExport(adminPage, code, 'CSV');

    // Cleanup
    await deleteExport(adminPage, code);
  });
});
