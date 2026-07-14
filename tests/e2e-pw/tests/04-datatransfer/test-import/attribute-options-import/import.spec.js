const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo } = require('../../../../utils/helpers');

test.describe('Attribute Options Import', () => {

    test('Create Attribute Options Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('link', { name: 'Create Import' }).click();
        await adminPage.getByRole('textbox', { name: 'Code' }).click();
        await adminPage.getByRole('textbox', { name: 'Code' }).fill('Attribute Options Import');
        await adminPage.locator('#import-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
        await adminPage.getByRole('option', { name: 'Attribute Options' }).locator('span').first().click();
        const fileInput = adminPage.locator('input[type="file"][name="file"]');
        const path = require('path');
        const assetPath = path.join(__dirname, '../../../../assets/attribute-options.csv');
        await fileInput.setInputFiles(assetPath);
        await adminPage.getByRole('button', { name: 'Save changes' }).click();
        await expect(adminPage.locator('#app').getByText(/Import created successfully/i)).toBeVisible({ timeout: 15000 });
    });

    test('Delete Attribute Options Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill('Attribute Options Import');
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');
        const itemRow = await adminPage.locator('div', { hasText: 'Attribute Options Import' });
        await itemRow.locator('span[title="Delete"]').first().click();
        await adminPage.getByRole('button', { name: 'Delete' }).click();
        await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible();
    });

});
