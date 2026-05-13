const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../../../utils/helpers');

/**
 * Helper: Create a channel import job with given code and file.
 */
async function createChannelImport(adminPage, code, filePath = 'assets/channels.csv') {
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);

    // Select 'Channels' from the type dropdown
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'categories' }).click();
    await adminPage.getByRole('option', { name: 'Channels' }).locator('span').first().click();

    const fileInput = adminPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles(filePath);
    await clickSaveAndExpect(adminPage, 'Save Import', /Import created successfully/i);
}

/**
 * Helper: Delete an import job by code via search + delete action.
 */
async function deleteImport(adminPage, code) {
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const deleteBtn = adminPage.locator('span[title="Delete"]').first();
    if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
        await deleteBtn.click();
        await adminPage.getByRole('button', { name: 'Delete' }).click();
        await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible({ timeout: 20000 });
    }
}


test.describe('UnoPim Channel Import Jobs', () => {

    test('Create Channel Import and run Import Now', async ({ adminPage }) => {
        const uid = generateUid();
        const code = `chan-imp-${uid}`;

        await createChannelImport(adminPage, code);

        // Verify we are on the edit/show page of the created import
        await expect(adminPage.getByRole('button', { name: 'Import Now' })).toBeVisible();

        // Run Import Now
        await adminPage.getByRole('button', { name: 'Import Now' }).click();
        await expect(adminPage.locator('#app').getByText('Job queued')).toBeVisible();

        // Cleanup
        await deleteImport(adminPage, code);
    });

    test('Search finds a channel import job by code', async ({ adminPage }) => {
        const uid = generateUid();
        const code = `search-chan-${uid}`;

        await createChannelImport(adminPage, code);

        // Search
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');
        await expect(adminPage.locator('#app').getByText(code, { exact: true })).toBeVisible();

        // Cleanup
        await deleteImport(adminPage, code);
    });

    test('Channel import job row shows Edit, Delete, and Import actions', async ({ adminPage }) => {
        const uid = generateUid();
        const code = `actions-chan-${uid}`;

        // Create
        await createChannelImport(adminPage, code);

        // Navigate and search
        await navigateTo(adminPage, 'imports');
        await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');

        const itemRow = adminPage.locator('div', { hasText: code });

        // Test Import action icon
        await itemRow.locator('span[title="Import"]').first().click();
        await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/import/);
        await adminPage.goBack();
        await adminPage.waitForLoadState('networkidle');

        // Search again
        await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');

        // Test Edit action icon
        const itemRow2 = adminPage.locator('div', { hasText: code });
        await itemRow2.locator('span[title="Edit"]').first().click();
        await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/edit/);
        await adminPage.goBack();
        await adminPage.waitForLoadState('networkidle');

        // Cleanup
        await deleteImport(adminPage, code);
    });
});
