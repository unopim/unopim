const { test, expect } = require('../../../../utils/fixtures');

test.describe('UnoPim Export Jobs', () => {

  test('create attribute family export with CSV, switch to XLS, then delete', async ({ adminPage }) => {

    const uniqueCode = 'Attribute Family Export CSV ' + Math.random().toString(36).slice(2, 6);

    await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
    await adminPage.getByRole('link', { name: 'Exports' }).click();
    await adminPage.getByRole('link', { name: 'Create Export' }).click();

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
      .getByRole('option', { name: 'Attribute Families' })
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
    await adminPage.getByRole('button', { name: 'Save Export' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export created successfully/i)
    ).toBeVisible();

    // Run Export
    await adminPage.getByRole('button', { name: 'Export Now' }).click();

    const [, csvDownload] = await Promise.all([
      adminPage.waitForEvent('popup'),
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

    await adminPage.getByRole('button', { name: 'Save Export' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export updated successfully/i)
    ).toBeVisible();

    // Export Again
    await adminPage.getByRole('button', { name: 'Export Now' }).click();

    const [, xlsDownload] = await Promise.all([
      adminPage.waitForEvent('popup'),
      adminPage.waitForEvent('download'),
      adminPage.getByRole('link', { name: 'Download Exported Files' }).click(),
    ]);

    // Go back to list
    await adminPage.getByRole('link', { name: 'Exports' }).click();

    // Delete created export
    const itemRow = adminPage.locator('div', { hasText: uniqueCode });

    await itemRow.locator('span[title="Delete"]').first().click();

    await adminPage.getByRole('button', { name: 'Delete' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export deleted successfully/i)
    ).toBeVisible();

  });

});
