const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../../../utils/helpers');
const path = require('path');

test.describe('Locale Import', () => {
    const uid = generateUid();
    const importCode = `locale-import-${uid}`;

    test('Create Locale Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('link', { name: 'Create Import' }).click();

        await adminPage.getByRole('textbox', { name: 'Code' }).fill(importCode);

        await adminPage.locator('#import-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
        await adminPage.getByRole('option', { name: 'Locales' }).locator('span').first().click();

        const fileInput = adminPage.locator('input[type="file"][name="file"]');
        const assetPath = path.join(__dirname, '../../../../assets/locales.csv');
        await fileInput.setInputFiles(assetPath);

        await clickSaveAndExpect(adminPage, 'Save changes', /Import created successfully/i);
    });

    test('Run Locale Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill(importCode);
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');

        const itemRow = adminPage.locator('div', { hasText: importCode });
        await itemRow.locator('span[title="Import"]').first().click();

        await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/import/);

        const importNowBtn = adminPage.getByRole('button', { name: 'Import Now' });
        await expect(importNowBtn).toBeVisible({ timeout: 5000 });
        await importNowBtn.click();

        await expect(adminPage.locator('#app').getByText(/Job queued|Queued|Processing|Completed/i).first()).toBeVisible({ timeout: 20000 });
    });

    test('Delete Locale Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill(importCode);
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');

        const itemRow = adminPage.locator('div', { hasText: importCode });
        await itemRow.locator('span[title="Delete"]').first().click();
        await adminPage.getByRole('button', { name: 'Delete' }).click();

        await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible();
    });
});
