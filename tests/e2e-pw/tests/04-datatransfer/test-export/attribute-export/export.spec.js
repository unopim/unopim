const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo } = require('../../../../utils/helpers');

test.describe('UnoPim Export Jobs', () => {

  test('create attribute export with CSV, switch to XLS, then delete', async ({ adminPage }) => {

    const uniqueCode = 'Attribute_Export_CSV_' + Math.random().toString(36).slice(2, 6);

    await adminPage.goto('/admin/data-transfer/exports/create', { waitUntil: 'networkidle' });

    await adminPage.getByRole('textbox', { name: 'Code' }).fill(uniqueCode);

    await adminPage
      .locator('#export-type')
      .getByRole('combobox')
      .locator('div')
      .filter({ hasText: 'Categories' })
      .click();

    await adminPage
      .getByRole('option', { name: 'Attributes' })
      .locator('span')
      .first()
      .click();

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

    await adminPage.getByRole('button', { name: 'Save changes' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export created successfully/i)
    ).toBeVisible();

    await adminPage.getByRole('button', { name: 'Export Now' }).click();

    const [, csvDownload] = await Promise.all([
      adminPage.waitForEvent('popup'),
      adminPage.waitForEvent('download'),
      adminPage.getByRole('link', { name: 'Download Exported Files' }).click(),
    ]);

    await adminPage.getByRole('link', { name: 'Edit' }).click();

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

    await adminPage.getByRole('button', { name: 'Export Now' }).click();

    const [, xlsDownload] = await Promise.all([
      adminPage.waitForEvent('popup'),
      adminPage.waitForEvent('download'),
      adminPage.getByRole('link', { name: 'Download Exported Files' }).click(),
    ]);

    await navigateTo(adminPage, 'exports');

    const itemRow = await adminPage.locator('div', { hasText: uniqueCode });

    await itemRow.locator('span[title="Delete"]').first().click();

    await adminPage.getByRole('button', { name: 'Delete' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export deleted successfully/i)
    ).toBeVisible();

  });

});
