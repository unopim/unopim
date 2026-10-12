const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo, clickSaveAndExpect } = require('../../../../utils/helpers');

test.describe('Category_Fields_Import', () => {

    test('Create Category_Fields_Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('link', { name: 'Create Import' }).click();
        await adminPage.getByRole('textbox', { name: 'Code' }).click();
        await adminPage.getByRole('textbox', { name: 'Code' }).fill('Category_Fields_Import');
        await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
        await adminPage.getByRole('option', { name: 'Category Fields' }).locator('span').first().click();
        const fileInput = adminPage.locator('input[type="file"][name="file"]');
        const path = require('path');
        const assetPath = path.join(__dirname, '../../../../assets/category-fields.csv');
        await fileInput.setInputFiles(assetPath);
        await clickSaveAndExpect(adminPage, 'Save changes', /Import created successfully/i);
    });

    test('Delete Category_Fields_Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill('Category_Fields_Import');
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');
        const itemRow = await adminPage.locator('div', { hasText: 'Category_Fields_Import' });
        await itemRow.locator('span[title="Delete"]').first().click();
        await adminPage.getByRole('button', { name: 'Delete' }).click();
        await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible();
    });

});
