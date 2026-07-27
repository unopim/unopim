const { test, expect } = require('../../../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../../../utils/helpers');


async function createExportWithStatusFilter(adminPage, code, statusLabel) {
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('link', { name: 'Create Export' }).click();

    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);

    await adminPage.locator('#export-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
    await adminPage.getByRole('option', { name: 'Currencies' }).locator('span').first().click();

    await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
    await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();


    await clickSaveAndExpect(adminPage, 'Save changes', /Export created successfully/i);

    await adminPage.getByRole('link', { name: 'Edit' }).click();
    await adminPage.waitForLoadState('domcontentloaded');

    const statusSelect = adminPage.locator('input[name="filters[status]"], select[name="filters[status]"]')
        .locator('..')
        .locator('.multiselect__placeholder, .multiselect__single, .multiselect__tags')
        .first();

    await expect(statusSelect, 'Status filter control should be visible so Issue #243 is actually exercised').toBeVisible({ timeout: 5000 });

    await statusSelect.click({ force: true });
    await adminPage.getByRole('option', { name: new RegExp(statusLabel, 'i') }).locator('span').first().click();

    await adminPage.getByRole('button', { name: 'Save changes' }).click();
    await adminPage.waitForLoadState('domcontentloaded');

    const exportNowBtn = adminPage.getByRole('button', { name: 'Export Now' });
    await expect(exportNowBtn).toBeVisible({ timeout: 5000 });
    await exportNowBtn.click();

    await expect(adminPage.locator('#app').getByText(/Job queued|Queued|Processing|Completed/i).first()).toBeVisible({ timeout: 20000 });
}

/**
 * Helper: Delete an export job by code via search + delete action.
 */
async function deleteExport(adminPage, code) {
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('domcontentloaded');
    const deleteBtn = adminPage.locator('span[title="Delete"]').first();
    if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
        await deleteBtn.click();
        await adminPage.getByRole('button', { name: 'Delete' }).click();
        await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible({ timeout: 20000 });
    }
}

test.describe('Export with Status Filter', () => {

    test('Create Currency Export with Status filter set to Enable and run export', async ({ adminPage }) => {
        const uid = generateUid();
        const code = `exp-status-${uid}`;

        await createExportWithStatusFilter(adminPage, code, 'Enable');

        await deleteExport(adminPage, code);
    });

    test('Create Currency Export with Status filter set to All and run export', async ({ adminPage }) => {
        const uid = generateUid();
        const code = `exp-all-${uid}`;

        await createExportWithStatusFilter(adminPage, code, 'All');

        await deleteExport(adminPage, code);
    });
});