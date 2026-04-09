const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Issue #243: Export Job fails with Elasticsearch 8 when filtering by boolean status.
 * ES8 rejects integer 1/0 for boolean fields — requires true/false.
 *
 * This test verifies that creating and running a product export with a status
 * filter ("Enable"/"Disable") does not fail.
 */

/**
 * Helper: Create a product export, apply a status filter, run export, and clean up.
 */
async function createExportWithStatusFilter(adminPage, code, statusLabel) {
  // Navigate to exports and create a new product export
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();

  // Fill in export code
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);

  // Select entity type — Products (only products have the status filter)
  await adminPage.locator('#export-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
  await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();

  // Select file format — CSV
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();

  // Enable "With Media"
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();

  // Save the export — redirects to the show/detail page
  await clickSaveAndExpect(adminPage, 'Save Export', /Export created successfully/i);

  // Navigate to the edit page to set the Status filter
  await adminPage.getByRole('link', { name: 'Edit' }).click();
  await adminPage.waitForLoadState('networkidle');

  // Now on the edit page — set the Status filter
  const statusSelect = adminPage.locator('input[name="filters[status]"], select[name="filters[status]"]')
    .locator('..')
    .locator('.multiselect__placeholder, .multiselect__single, .multiselect__tags')
    .first();

  // Status filter must be present — assert, don't skip
  await expect(statusSelect, 'Status filter control should be visible so Issue #243 is actually exercised').toBeVisible({ timeout: 5000 });

  await statusSelect.click();
  await adminPage.getByRole('option', { name: new RegExp(statusLabel, 'i') }).locator('span').first().click();

  // Save with the status filter applied — redirects back to show page
  await adminPage.getByRole('button', { name: /Save Export/i }).click();
  await adminPage.waitForLoadState('networkidle');

  // Run the export — this is where the ES8 bug would manifest
  const exportNowBtn = adminPage.getByRole('button', { name: 'Export Now' });
  await expect(exportNowBtn).toBeVisible({ timeout: 5000 });
  await exportNowBtn.click();

  // The export should be queued successfully, not fail with ES boolean error
  await expect(adminPage.locator('#app').getByText(/Job queued/i)).toBeVisible({ timeout: 20000 });
}

/**
 * Helper: Delete an export job by code via search + delete action.
 */
async function deleteExport(adminPage, code) {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const deleteBtn = adminPage.locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible({ timeout: 20000 });
  }
}

test.describe('Export with Status Filter (Issue #243)', () => {

  test('Create Product Export with Status filter set to Enable and run export', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `exp-status-${uid}`;

    await createExportWithStatusFilter(adminPage, code, 'Enable');

    // Cleanup
    await deleteExport(adminPage, code);
  });

  test('Create Product Export with Status filter set to Disable and run export', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `exp-dis-${uid}`;

    await createExportWithStatusFilter(adminPage, code, 'Disable');

    // Cleanup
    await deleteExport(adminPage, code);
  });
});
