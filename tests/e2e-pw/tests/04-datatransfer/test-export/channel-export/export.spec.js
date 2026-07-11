const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../../../utils/helpers');

/**
 * Helper: Create an export job with given parameters for Channels.
 * @returns {Promise<void>}
 */
async function createChannelExport(adminPage, code, format = 'CSV') {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);

  await adminPage.locator('#export-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
  await adminPage.getByRole('option', { name: 'Channels' }).locator('span').first().click();

  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: format }).locator('span').first().click();

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

test.describe('Channel Export Jobs', () => {

  test('Create Channel Export (CSV) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chan-csv-${uid}`;
    await createChannelExport(adminPage, code, 'CSV');

    await deleteExport(adminPage, code);
  });
  

  test('Create Channel Export (XLS) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chan-xls-${uid}`;
    await createChannelExport(adminPage, code, 'XLS');

    await deleteExport(adminPage, code);
  });

  test('Create Channel Export (XLSX) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chan-xlsx-${uid}`;
    await createChannelExport(adminPage, code, 'XLSX');

    await deleteExport(adminPage, code);
  });

  test('Create Channel Export (CSV) and run Export Now', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chan-exp-now-${uid}`;

    await createChannelExport(adminPage, code, 'CSV');
    await expect(adminPage.getByRole('button', { name: 'Export Now' })).toBeVisible();
    await adminPage.getByRole('button', { name: 'Export Now' }).click();
    await expect(adminPage.locator('#app').getByText(/Job queued|Queued|Processing|Completed/i).first()).toBeVisible();

    await deleteExport(adminPage, code);
  });
});
