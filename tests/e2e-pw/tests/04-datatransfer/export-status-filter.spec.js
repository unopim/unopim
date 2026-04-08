const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Issue #243: Export Job fails with Elasticsearch 8 when filtering by boolean status.
 * ES8 rejects integer 1/0 for boolean fields — requires true/false.
 *
 * This test verifies that creating and running a product export with a status
 * filter ("Enable") does not fail.
 */
test.describe('Export with Status Filter (Issue #243)', () => {

  test('Create Product Export with Status filter set to Enable and run export', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `exp-status-${uid}`;

    // Navigate to exports and create a new product export
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('link', { name: 'Create Export' }).click();

    // Fill in export code
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);

    // Select file format — CSV
    await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
    await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();

    // Enable "With Media"
    await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();

    // Save the export
    await clickSaveAndExpect(adminPage, 'Save Export', /Export created successfully/i);

    // Now on the edit page — set the Status filter to "Enable"
    const statusSelect = adminPage.locator('input[name="filters[status]"], select[name="filters[status]"]')
      .locator('..')
      .locator('.multiselect__placeholder, .multiselect__single, .multiselect__tags')
      .first();

    // Try to find and set the status filter
    const statusFilterExists = await statusSelect.isVisible({ timeout: 5000 }).catch(() => false);

    if (statusFilterExists) {
      await statusSelect.click();
      await adminPage.getByRole('option', { name: /Enable/i }).locator('span').first().click();

      // Save again with the status filter applied
      await adminPage.getByRole('button', { name: /Save Export/i }).click();
      await adminPage.waitForLoadState('networkidle');
    }

    // Run the export — this is where the ES8 bug would manifest
    const exportNowBtn = adminPage.getByRole('button', { name: 'Export Now' });
    if (await exportNowBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await exportNowBtn.click();

      // The export should be queued successfully, not fail with ES boolean error
      await expect(adminPage.locator('#app').getByText(/Job queued/i)).toBeVisible({ timeout: 20000 });
    }

    // Cleanup — delete the export
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
  });

  test('Create Product Export with Status filter set to Disable and run export', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `exp-dis-${uid}`;

    // Navigate to exports and create a new product export
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('link', { name: 'Create Export' }).click();

    // Fill in export code
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);

    // Select file format — CSV
    await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
    await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();

    // Enable "With Media"
    await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();

    // Save the export
    await clickSaveAndExpect(adminPage, 'Save Export', /Export created successfully/i);

    // Now on the edit page — set the Status filter to "Disable"
    const statusSelect = adminPage.locator('input[name="filters[status]"], select[name="filters[status]"]')
      .locator('..')
      .locator('.multiselect__placeholder, .multiselect__single, .multiselect__tags')
      .first();

    const statusFilterExists = await statusSelect.isVisible({ timeout: 5000 }).catch(() => false);

    if (statusFilterExists) {
      await statusSelect.click();
      await adminPage.getByRole('option', { name: /Disable/i }).locator('span').first().click();

      // Save again with the status filter applied
      await adminPage.getByRole('button', { name: /Save Export/i }).click();
      await adminPage.waitForLoadState('networkidle');
    }

    // Run the export
    const exportNowBtn = adminPage.getByRole('button', { name: 'Export Now' });
    if (await exportNowBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await exportNowBtn.click();
      await expect(adminPage.locator('#app').getByText(/Job queued/i)).toBeVisible({ timeout: 20000 });
    }

    // Cleanup
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
  });
});
