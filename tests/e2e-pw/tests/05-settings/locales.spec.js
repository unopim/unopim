const { test, expect } = require('../../utils/fixtures');
const { navigateTo, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create a locale via the modal.
 */
async function createLocale(adminPage, code, enableStatus = true) {
  await navigateTo(adminPage, 'locales');
  await adminPage.getByRole('button', { name: 'Create Locale' }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill(code);
  if (enableStatus) {
    await adminPage.locator('label[for="status"]').click();
  }
  await clickSaveAndExpect(adminPage, 'Save Locale', /Locale created successfully/i);
}

/**
 * Helper: Delete a locale by code (search, delete, confirm).
 * Silently succeeds if the locale is not found or cannot be deleted.
 */
async function deleteLocale(adminPage, code) {
  await navigateTo(adminPage, 'locales');
  await searchInDataGrid(adminPage, code, 'Search by code');

  // Find the row containing the code and click its Delete button
  const row = adminPage.locator('#app div').filter({ hasText: code });
  const deleteBtn = row.locator('span[title="Delete"]').first();

  try {
    await deleteBtn.waitFor({ state: 'visible', timeout: 3000 });
    await deleteBtn.click({ timeout: 5000 });
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  } catch {
    // Locale not found or not deletable — that's fine
  }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

test.describe('Locale Management', () => {

  // --- Validation Tests ---

  test('Create locale with empty Code shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'locales');
    await adminPage.getByRole('button', { name: 'Create Locale' }).click();
    await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('');
    await adminPage.locator('label[for="status"]').click();
    await adminPage.getByRole('button', { name: 'Save Locale' }).click();
    await expect(adminPage.locator('#app').getByText(/The Code field is required/i)).toBeVisible();
  });

  test('Create locale with existing Code shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'locales');
    await adminPage.getByRole('button', { name: 'Create Locale' }).click();
    await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('en_US');
    await adminPage.locator('label[for="status"]').click();
    await adminPage.getByRole('button', { name: 'Save Locale' }).click();
    await expect(adminPage.locator('#app').getByText(/The code has already been taken/i)).toBeVisible();
  });

  // --- CRUD Tests ---

  test('Create locale successfully', async ({ adminPage }) => {
    // Use af_ZA - first delete it if it exists, then create it, then cleanup
    await deleteLocale(adminPage, 'af_ZA');
    await createLocale(adminPage, 'af_ZA');

    // Cleanup
    await deleteLocale(adminPage, 'af_ZA');
  });

  test('Update Locale', async ({ adminPage }) => {
    // Ensure clean state
    await deleteLocale(adminPage, 'af_ZA');

    // Create
    await createLocale(adminPage, 'af_ZA');

    // Search and edit
    await navigateTo(adminPage, 'locales');
    await searchInDataGrid(adminPage, 'af_ZA', 'Search by code');
    const row = adminPage.locator('#app div').filter({ hasText: 'af_ZA' });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.locator('label[for="status"]').click();
    await clickSaveAndExpect(adminPage, 'Save Locale', /Locale updated successfully/i);

    // Cleanup
    await deleteLocale(adminPage, 'af_ZA');
  });

  test('Delete Locale', async ({ adminPage }) => {
    // Ensure clean state
    await deleteLocale(adminPage, 'af_ZA');

    // Create
    await createLocale(adminPage, 'af_ZA');

    // Delete
    await navigateTo(adminPage, 'locales');
    await searchInDataGrid(adminPage, 'af_ZA', 'Search by code');
    const row = adminPage.locator('#app div').filter({ hasText: 'af_ZA' });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Locale deleted successfully/i)).toBeVisible();
  });

  test('Delete channel-linked locale en_US shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'locales');
    await searchInDataGrid(adminPage, 'en_US', 'Search by code');
    const row = adminPage.locator('#app div').filter({ hasText: 'en_US' }).first();
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/cannot delete a locale linked to a channel or user/i)).toBeVisible();
  });
});
