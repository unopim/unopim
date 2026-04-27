const { test, expect } = require('../../../../utils/fixtures');

test.describe('UnoPim Export Jobs', () => {

  test('create attribute export with CSV, switch to XLS, then delete', async ({ adminPage }) => {

    const uniqueCode = 'Attribute Export CSV ' + Math.random().toString(36).slice(2, 6);

    await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
    await adminPage.getByRole('link', { name: 'Exports' }).click();
    await adminPage.getByRole('link', { name: 'Create Export' }).click();

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

    await adminPage.getByRole('button', { name: 'Save Export' }).click();

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

    await adminPage.getByRole('button', { name: 'Save Export' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export updated successfully/i)
    ).toBeVisible();

    await adminPage.getByRole('button', { name: 'Export Now' }).click();

    const [, xlsDownload] = await Promise.all([
      adminPage.waitForEvent('popup'),
      adminPage.waitForEvent('download'),
      adminPage.getByRole('link', { name: 'Download Exported Files' }).click(),
    ]);

    await adminPage.getByRole('link', { name: 'Exports' }).click();

    const itemRow = await adminPage.locator('div', { hasText: uniqueCode });

    await itemRow.locator('span[title="Delete"]').first().click();

    await adminPage.getByRole('button', { name: 'Delete' }).click();

    await expect(
      adminPage.locator('#app').getByText(/Export deleted successfully/i)
    ).toBeVisible();

  });

});
