const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo } = require('../../../../utils/helpers');

test.describe('Attribute_Family_Import', () => {

    test('Create Attribute_Family_Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('link', { name: 'Create Import' }).click();
        await adminPage.getByRole('textbox', { name: 'Code' }).click();
        await adminPage.getByRole('textbox', { name: 'Code' }).fill('Attribute_Family_Import');
        const fileInput = adminPage.locator('input[type="file"][name="file"]');
        const path = require('path');
        const assetPath = path.join(__dirname, '../../../../assets/attribute-families.csv');
        await fileInput.setInputFiles(assetPath);
        await adminPage.getByRole('button', { name: 'Save changes' }).click();
        await expect(adminPage.locator('#app').getByText(/Import created successfully/i)).toBeVisible({ timeout: 15000 });
    });

    test('Delete Attribute_Family_Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        const itemRow = await adminPage.locator('div', { hasText: 'Attribute_Family_Import' });
        await itemRow.locator('span[title="Delete"]').first().click();
        await adminPage.getByRole('button', { name: 'Delete' }).click();
        await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible();
    });

});
