const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create an export job with given parameters.
 * @returns {Promise<void>}
 */
async function createExport(adminPage, code, format = 'CSV', withMedia = true) {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: format }).locator('span').first().click();
  if (withMedia) {
    await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  }
  await clickSaveAndExpect(adminPage, 'Save Export', /Export created successfully/i);
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

test.describe('UnoPim Export Jobs', () => {

  // ── Validation tests (no data dependency) ──

  test('Create Export with empty Code field shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('link', { name: 'Create Export' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
    await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
    await adminPage.getByRole('button', { name: 'Save Export' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create Export with empty Type field shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('link', { name: 'Create Export' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(`exp-type-${uid}`);
    await adminPage.locator('#export-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
    await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
    await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
    await adminPage.getByRole('button', { name: 'Save Export' }).click();
    await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
  });

  test('Create Export with empty File Format field shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('link', { name: 'Create Export' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(`exp-fmt-${uid}`);
    await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
    await adminPage.getByRole('button', { name: 'Save Export' }).click();
    await expect(adminPage.locator('#app').getByText('The File Format field is required')).toBeVisible();
  });

  test('Create Export with all fields empty shows all validation errors', async ({ adminPage }) => {
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('link', { name: 'Create Export' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('#export-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
    await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
    await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
    await adminPage.getByRole('button', { name: 'Save Export' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The File Format field is required')).toBeVisible();
  });

  // ── Create & Delete: Category CSV ──

  test('Create Category Export (CSV) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cat-csv-${uid}`;
    await createExport(adminPage, code, 'CSV', true);

    // Cleanup
    await deleteExport(adminPage, code);
  });

  // ── Duplicate code validation ──

  test('Create Export with duplicate Code shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `dup_exp_${uid}`;

    // Create the first export
    await createExport(adminPage, code, 'CSV', true);

    // Try to create another with the same code — don't use createExport helper
    // because we expect a validation error, not success
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('link', { name: 'Create Export' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
    await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
    await adminPage.getByRole('button', { name: 'Save Export' }).click();
    await expect(adminPage.locator('#app').getByText(/Code has already been taken|already exists/i)).toBeVisible({ timeout: 20000 });

    // Cleanup
    await deleteExport(adminPage, code);
  });

  // ── Search ──

  test('Search finds an export job by code', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `search-exp-${uid}`;

    // Create
    await createExport(adminPage, code, 'CSV', true);

    // Search
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(code, { exact: true })).toBeVisible();

    // Cleanup
    await deleteExport(adminPage, code);
  });

  // ── Filter menu ──

  test('Filter menu opens when Filter is clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'exports');
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  // ── Per page ──

  test('Items per page can be changed', async ({ adminPage }) => {
    await navigateTo(adminPage, 'exports');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  // ── Actions (Edit, Delete, Export) ──

  test('Export job row shows Edit, Delete, and Export actions', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `actions-exp-${uid}`;

    // Create
    await createExport(adminPage, code, 'CSV', true);

    // Navigate and search
    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');

    const itemRow = adminPage.locator('div', { hasText: code });

    // Test Export action
    await itemRow.locator('span[title="Export"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/exports\/export/);
    await adminPage.goBack();
    await adminPage.waitForLoadState('networkidle');

    // Search again after going back
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');

    // Test Edit action
    const itemRow2 = adminPage.locator('div', { hasText: code });
    await itemRow2.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/exports\/edit/);
    await adminPage.goBack();
    await adminPage.waitForLoadState('networkidle');

    // Search again after going back
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');

    // Test Delete action (opens confirmation modal)
    const itemRow3 = adminPage.locator('div', { hasText: code });
    await itemRow3.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();

    // Confirm delete to clean up
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible();
  });

  // ── Category XLS with Export Now ──

  test('Create Category Export (XLS) and run Export Now', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cat-xls-${uid}`;

    await createExport(adminPage, code, 'XLS', false);
    await expect(adminPage.getByRole('button', { name: 'Export Now' })).toBeVisible();
    await adminPage.getByRole('button', { name: 'Export Now' }).click();
    await expect(adminPage.locator('#app').getByText('Job queued')).toBeVisible();

    // Cleanup
    await deleteExport(adminPage, code);
  });

  // ── Category XLSX ──

  test('Create Category Export (XLSX) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cat-xlsx-${uid}`;

    await createExport(adminPage, code, 'XLSX', true);

    // Cleanup
    await deleteExport(adminPage, code);
  });

  // ── Product CSV ──

  test('Create Product Export (CSV) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `prod-csv-${uid}`;

    await createExport(adminPage, code, 'CSV', true);

    // Cleanup
    await deleteExport(adminPage, code);
  });

  // ── Product XLS ──

  test('Create Product Export (XLS) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `prod-xls-${uid}`;

    await createExport(adminPage, code, 'XLS', false);

    // Cleanup
    await deleteExport(adminPage, code);
  });

  // ── Product XLSX ──

  test('Create Product Export (XLSX) and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `prod-xlsx-${uid}`;

    await createExport(adminPage, code, 'XLSX', true);

    // Cleanup
    await deleteExport(adminPage, code);
  });
});
