const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo, generateUid } = require('../../../../utils/helpers');
const path = require('path');

test.describe('Attribute Group Import', () => {
    const uid = generateUid();
    const importCode = `attribute-group-import-${uid}`;

    test('Create Attribute Group Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('link', { name: 'Create Import' }).click();

        await adminPage.getByRole('textbox', { name: 'Code' }).fill(importCode);

        await adminPage.locator('#import-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
        await adminPage.getByRole('option', { name: 'Attribute Groups' }).locator('span').first().click();

        const fileInput = adminPage.locator('input[type="file"][name="file"]');
        const assetPath = path.join(__dirname, '../../../../assets/attribute-groups.csv');
        await fileInput.setInputFiles(assetPath);

        await adminPage.getByRole('button', { name: 'Save changes' }).click();
        await expect(adminPage.locator('#app').getByText(/Import created successfully/i)).toBeVisible({ timeout: 15000 });
    });

    test('Delete Attribute Group Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill(importCode);
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('domcontentloaded');

        const itemRow = adminPage.locator('div', { hasText: importCode });
        await itemRow.locator('span[title="Delete"]').first().click();
        await adminPage.getByRole('button', { name: 'Delete' }).click();

        await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible();
    });
});
