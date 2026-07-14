const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo, generateUid } = require('../../../../utils/helpers');
const path = require('path');

test.describe('Currency Import', () => {
    const uid = generateUid();
    const importCode = `currency-import-${uid}`;

    test('Create Currency Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('link', { name: 'Create Import' }).click();

        await adminPage.getByRole('textbox', { name: 'Code' }).fill(importCode);

        // Select Type: Currencies
        const typeWrapper = adminPage.locator('#import-type');
        await typeWrapper.locator('.multiselect__tags').click();
        await typeWrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
        await adminPage.getByRole('option', { name: 'Currencies' }).first().click();

        const fileInput = adminPage.locator('input[type="file"][name="file"]');
        const assetPath = path.join(__dirname, '../../../../assets/currencies.csv');
        await fileInput.setInputFiles(assetPath);

        // Saving is driven by the global unsaved-changes bar (the in-form submit
        // button is hidden once the form tracks changes).
        const saveChanges = adminPage.getByRole('button', { name: 'Save changes' });
        await expect(saveChanges).toBeVisible({ timeout: 5000 });
        await saveChanges.click();

        await expect(adminPage.locator('#app').getByText(/Import created successfully/i)).toBeVisible({ timeout: 15000 });
    });

    test('Run Currency Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill(importCode);
        await adminPage.keyboard.press('Enter');

        const itemRow = adminPage.locator('.row', { hasText: importCode });
        await expect(itemRow).toBeVisible({ timeout: 10000 });
        // exact: the import code contains the word "import", so a loose title match
        // would hit the code cell instead of the action icon.
        await itemRow.getByTitle('Import', { exact: true }).click();

        await expect(adminPage).toHaveURL(/\/admin\/data-transfer\/imports\/import/);

        const importNowBtn = adminPage.getByRole('button', { name: 'Import Now' });
        await expect(importNowBtn).toBeVisible({ timeout: 5000 });
        await importNowBtn.click();

        await expect(adminPage.locator('#app').getByText(/Job queued|Queued|Processing|Completed/i).first()).toBeVisible({ timeout: 20000 });
    });

    test('Delete Currency Import', async ({ adminPage }) => {
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill(importCode);
        await adminPage.keyboard.press('Enter');

        const itemRow = adminPage.locator('.row', { hasText: importCode });
        await expect(itemRow).toBeVisible({ timeout: 10000 });
        await itemRow.getByTitle('Delete', { exact: true }).click();
        await adminPage.getByRole('button', { name: 'Delete' }).click();

        await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible();
    });
});