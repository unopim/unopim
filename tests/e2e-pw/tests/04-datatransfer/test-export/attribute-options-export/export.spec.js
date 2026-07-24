const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo } = require('../../../../utils/helpers');

test.describe('UnoPim Export Jobs', () => {

  test('create attribute options export with CSV, switch to XLS, then delete', async ({ adminPage }) => {

    const uniqueCode = 'Attribute_Options_Export_CSV_' + Math.random().toString(36).slice(2, 6);

    await adminPage.goto('/admin/data-transfer/exports/create', { waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(1000);

    // Fill Code
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(uniqueCode);

    // Select Export Type
    await adminPage
      .locator('#export-type')
      .getByRole('combobox')
      .locator('div')
      .filter({ hasText: 'Categories' })
      .click();

    await adminPage
      .getByRole('option', { name: 'Attribute Options' })
      .locator('span')
      .first()
      .click();

    // Select File Format CSV
    await adminPage
      .locator('input[name="filters[file_format]"]')
      .locator('..')
      .locator('.multiselect__placeholder')
      .click();

    await adminPage
      .getByRole('option', { name: 'CSV' })
      .locator('span')
      .first()
      .click();

    // Save Export
    await adminPage.getByRole('button', { name: 'Save changes' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export created successfully/i)
    ).toBeVisible();

    // Run Export
    await adminPage.getByRole('button', { name: 'Export Now' }).click();

    await adminPage.getByRole('link', { name: 'Download Exported Files' }).waitFor({ state: 'visible', timeout: 60000 });

    const [csvDownload] = await Promise.all([
      adminPage.waitForEvent('download'),
      adminPage.getByRole('link', { name: 'Download Exported Files' }).click(),
    ]);

    // Edit Export
    await adminPage.getByRole('link', { name: 'Edit' }).click();

    // Change File Format to XLS
    await adminPage
      .locator('input[name="filters[file_format]"]')
      .locator('..')
      .locator('.multiselect__single')
      .click();

    await adminPage
      .getByRole('option', { name: 'XLS' })
      .locator('span')
      .first()
      .click();

    await adminPage.getByRole('button', { name: 'Save changes' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export updated successfully/i)
    ).toBeVisible();

    // Export Again
    await adminPage.getByRole('button', { name: 'Export Now' }).click();

    await adminPage.getByRole('link', { name: 'Download Exported Files' }).waitFor({ state: 'visible', timeout: 60000 });

    const [xlsDownload] = await Promise.all([
      adminPage.waitForEvent('download'),
      adminPage.getByRole('link', { name: 'Download Exported Files' }).click(),
    ]);

    // Go back to list
    await navigateTo(adminPage, 'exports');

    // Delete created export
    const itemRow = adminPage.locator('div', { hasText: uniqueCode });

    await itemRow.locator('span[title="Delete"]').first().click();

    await adminPage.getByRole('button', { name: 'Delete' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export deleted successfully/i)
    ).toBeVisible();

  });

});
