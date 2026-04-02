const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create a product import job with given code and file.
 */
async function createProductImport(adminPage, code, filePath = 'assets/1k_products.xlsx') {
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
 * Helper: Create a category import job with given code and file.
 */
async function createCategoryImport(adminPage, code, filePath = 'assets/1k_products.xlsx') {
  await navigateTo(adminPage, 'imports');
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
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

test.describe('UnoPim Import Jobs', () => {

  // ── Validation tests (no data dependency) ──

  test('Create Import with empty Code field shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();
    const fileInput = adminPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles('assets/1k_products.xlsx');
    await adminPage.getByRole('button', { name: 'Save Import' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create Import with empty Type field shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(`imp-type-${uid}`);
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
    const fileInput = adminPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles('assets/1k_products.xlsx');
    await adminPage.getByRole('button', { name: 'Save Import' }).click();
    await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
  });

  test('Create Import with empty File field shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(`imp-file-${uid}`);
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();
    await adminPage.getByRole('button', { name: 'Save Import' }).click();
    await expect(adminPage.locator('#app').getByText('The File field is required')).toBeVisible();
  });

  test('Create Import with all required fields empty shows all validation errors', async ({ adminPage }) => {
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
    await adminPage.locator('#action').getByRole('combobox').locator('div').filter({ hasText: 'Create/Update' }).click();
    await adminPage.getByRole('option', { name: 'Create/Update' }).locator('span').first().click();
    await adminPage.locator('#validation_strategy').getByRole('combobox').locator('div').filter({ hasText: 'Stop on Errors' }).click();
    await adminPage.getByText('Stop on Errors').click();
    await adminPage.getByRole('textbox', { name: 'Allowed Errors' }).fill('');
    await adminPage.getByRole('textbox', { name: 'Field Separator' }).fill('');
    await adminPage.getByRole('button', { name: 'Save Import' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Action field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Validation Strategy field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Allowed Errors field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Field Separator field is required')).toBeVisible();
  });

  test('Create Import with empty Action field shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(`imp-action-${uid}`);
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
    const fileInput = adminPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles('assets/1k_products.xlsx');
    await adminPage.locator('#action').getByRole('combobox').locator('div').filter({ hasText: 'Create/Update' }).click();
    await adminPage.getByRole('option', { name: 'Create/Update' }).locator('span').first().click();
    await adminPage.getByRole('button', { name: 'Save Import' }).click();
    await expect(adminPage.locator('#app').getByText('The Action field is required')).toBeVisible();
  });

  test('Create Import with empty Validation Strategy field shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(`imp-vs-${uid}`);
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
    const fileInput = adminPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles('assets/1k_products.xlsx');
    await adminPage.locator('#validation_strategy').getByRole('combobox').locator('div').filter({ hasText: 'Stop on Errors' }).click();
    await adminPage.getByText('Stop on Errors').click();
    await adminPage.getByRole('button', { name: 'Save Import' }).click();
    await expect(adminPage.locator('#app').getByText('The Validation Strategy field is required')).toBeVisible();
  });

  test('Create Import with empty Allowed Errors field shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(`imp-ae-${uid}`);
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
    const fileInput = adminPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles('assets/1k_products.xlsx');
    await adminPage.getByRole('textbox', { name: 'Allowed Errors' }).fill('');
    await adminPage.getByRole('button', { name: 'Save Import' }).click();
    await expect(adminPage.locator('#app').getByText('The Allowed Errors field is required')).toBeVisible();
  });

  test('Create Import with empty Field Separator field shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(`imp-fs-${uid}`);
    await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
    const fileInput = adminPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles('assets/1k_products.xlsx');
    await adminPage.getByRole('textbox', { name: 'Field Separator' }).fill('');
    await adminPage.getByRole('button', { name: 'Save Import' }).click();
    await expect(adminPage.locator('#app').getByText('The Field Separator field is required')).toBeVisible();
  });

  // ── Create Product Import, run Import Now ──

  test('Create Product Import and run Import Now', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `prod-imp-${uid}`;

    await createProductImport(adminPage, code);
    await adminPage.getByRole('button', { name: 'Import Now' }).click();
    await expect(adminPage.locator('#app').getByText('Job queued')).toBeVisible();

    // Cleanup
    await deleteImport(adminPage, code);
  });

  // ── Duplicate code validation ──

  test('Create Import with duplicate Code shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `dup-imp-${uid}`;

    // Create the first import
    await createProductImport(adminPage, code);

    // Try to create another with the same code
    await createProductImport(adminPage, code);
    await expect(adminPage.locator('#app').getByText('The code has already been taken.')).toBeVisible();

    // Cleanup
    await deleteImport(adminPage, code);
  });

  // ── Search ──

  test('Search finds an import job by code', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `search-imp-${uid}`;

    await createProductImport(adminPage, code);

    // Search
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(code, { exact: true })).toBeVisible();

    // Cleanup
    await deleteImport(adminPage, code);
  });

  // ── Filter menu ──

  test('Filter menu opens when Filter is clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'imports');
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  // ── Per page ──

  test('Items per page can be changed', async ({ adminPage }) => {
    await navigateTo(adminPage, 'imports');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  // ── Actions (Edit, Delete, Import) ──

  test('Import job row shows Edit, Delete, and Import actions', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `actions-imp-${uid}`;

    // Create
    await createProductImport(adminPage, code);

    // Navigate and search
    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');

    const itemRow = adminPage.locator('div', { hasText: code });

    // Test Import action
    await itemRow.locator('span[title="Import"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/import/);
    await adminPage.goBack();
    await adminPage.waitForLoadState('networkidle');

    // Search again
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');

    // Test Edit action
    const itemRow2 = adminPage.locator('div', { hasText: code });
    await itemRow2.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/edit/);
    await adminPage.goBack();
    await adminPage.waitForLoadState('networkidle');

    // Search again
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');

    // Test Delete action (opens confirmation modal)
    const itemRow3 = adminPage.locator('div', { hasText: code });
    await itemRow3.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();

    // Confirm delete to clean up
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible();
  });

  // ── Category Import ──

  test('Create Category Import and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cat-imp-${uid}`;

    await createCategoryImport(adminPage, code);

    // Cleanup
    await deleteImport(adminPage, code);
  });
});
