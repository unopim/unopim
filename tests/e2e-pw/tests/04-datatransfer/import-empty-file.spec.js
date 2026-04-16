const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create a product import job with a specific file.
 */
async function createProductImport(adminPage, code, filePath) {
  await navigateTo(adminPage, 'imports');
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"]').first();
  await fileInput.setInputFiles(filePath);
  await clickSaveAndExpect(adminPage, 'Save Import', /Import created successfully/i);
}

/**
 * Helper: Delete an import job by code via search + delete action.
 */
async function deleteImport(adminPage, code) {
  await navigateTo(adminPage, 'imports');
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const deleteBtn = adminPage.locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible({ timeout: 20000 });
  }
}

test.describe('Import Empty File Validation (Issue #696)', () => {

  test('Import with empty XLSX file can be created and queued without PHP crash', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `empty-imp-${uid}`;

    // Step 1: Create import with empty XLSX file — should succeed (file is accepted)
    await createProductImport(adminPage, code, 'assets/empty.xlsx');

    // Step 2: Click Import Now — should redirect to tracker page
    await adminPage.getByRole('button', { name: 'Import Now' }).click();
    await adminPage.waitForURL(/\/admin\/settings\/data-transfer\/tracker/, { timeout: 30000 });

    // Step 3: Verify tracker page loaded with the import code (no PHP crash)
    await expect(adminPage.getByText(new RegExp(code, 'i'))).toBeVisible({ timeout: 10000 });

    // Step 4: Verify no raw PHP error messages are exposed on the page
    const pageContent = await adminPage.locator('#app').textContent();
    expect(pageContent).not.toContain('Cannot assign false to property');
    expect(pageContent).not.toContain('TypeError');
    expect(pageContent).not.toContain('AbstractSource');

    // Cleanup
    await deleteImport(adminPage, code);
  });
});
