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

  // Select Type: Channels
  // The locator for the Type field might vary, but based on export.spec.js:
  await adminPage.locator('#export-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
  await adminPage.getByRole('option', { name: 'Channels' }).locator('span').first().click();

  // Select File Format
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: format }).locator('span').first().click();

  await clickSaveAndExpect(adminPage, 'Save Export', /Export created successfully/i);
}

/**
 * Helper: Delete an export job by code via search + delete action.
 */
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

    // Cleanup
    await deleteExport(adminPage, code);
  });
  

  test('Create Channel Export (XLS) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chan-xls-${uid}`;
    await createChannelExport(adminPage, code, 'XLS');

    // Cleanup
    await deleteExport(adminPage, code);
  });

  test('Create Channel Export (XLSX) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chan-xlsx-${uid}`;
    await createChannelExport(adminPage, code, 'XLSX');

    // Cleanup
    await deleteExport(adminPage, code);
  });

  test('Create Channel Export (CSV) and run Export Now', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chan-exp-now-${uid}`;

    await createChannelExport(adminPage, code, 'CSV');
    await expect(adminPage.getByRole('button', { name: 'Export Now' })).toBeVisible();
    await adminPage.getByRole('button', { name: 'Export Now' }).click();
    await expect(adminPage.locator('#app').getByText('Job queued')).toBeVisible();

    // Cleanup
    await deleteExport(adminPage, code);
  });
});
