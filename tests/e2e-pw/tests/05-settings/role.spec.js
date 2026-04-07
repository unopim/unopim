const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create an "All" permission role via the UI.
 */
async function createAdminRole(adminPage, name, description = 'Full access role') {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill(name);
  await adminPage.getByRole('textbox', { name: 'Description' }).fill(description);
  await clickSaveAndExpect(adminPage, 'Save Role', /Roles Created Successfully/i);
}

/**
 * Helper: Create a "Custom" permission role with specific permissions.
 */
async function createCustomRole(adminPage, name, description, permissions = ['Dashboard', 'Catalog']) {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.waitForLoadState('networkidle');

  // Select custom permissions by clicking their labels
  for (const perm of permissions) {
    const label = adminPage.locator('label').filter({ hasText: perm }).locator('span').first();
    await label.waitFor({ state: 'visible', timeout: 10000 });
    await label.click();
  }

  await adminPage.getByRole('textbox', { name: 'Name' }).fill(name);
  await adminPage.getByRole('textbox', { name: 'Description' }).fill(description);

  await clickSaveAndExpect(adminPage, 'Save Role', /Roles Created Successfully/i);
}

/**
 * Helper: Delete a role by name (search, find, delete, confirm).
 * Silently succeeds if the role is not found.
 */
async function deleteRole(adminPage, name) {
  await navigateTo(adminPage, 'roles');
  await searchInDataGrid(adminPage, name);
  const row = adminPage.locator('#app div').filter({ hasText: name });
  const deleteBtn = row.locator('span[title="Delete"]').first();

  try {
    await deleteBtn.waitFor({ state: 'visible', timeout: 3000 });
    await deleteBtn.click({ timeout: 5000 });
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  } catch {
    // Role not found — that's fine
  }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

test.describe('Role Management', () => {

  // --- Validation Tests ---

  test('Create role with empty permission field shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');
    await adminPage.getByRole('link', { name: 'Create Role' }).click();
    // Leave permission_type as Custom without selecting any permissions
    await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
    await adminPage.getByRole('option', { name: 'Custom' }).locator('span').first().click();
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('Test Role');
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('Test description');
    await adminPage.getByRole('button', { name: 'Save Role' }).click();
    await expect(adminPage.locator('#app').getByText(/The Permissions field is required/i)).toBeVisible();
  });

  test('Create role with empty Name shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');
    await adminPage.getByRole('link', { name: 'Create Role' }).click();
    await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
    await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('Test description');
    await adminPage.getByRole('button', { name: 'Save Role' }).click();
    await expect(adminPage.locator('#app').getByText(/The Name field is required/i)).toBeVisible();
  });

  test('Create role with empty Description shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'roles');
    await adminPage.getByRole('link', { name: 'Create Role' }).click();
    await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
    await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
    await adminPage.getByRole('textbox', { name: 'Name' }).fill(`${uid} Role`);
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('');
    await adminPage.getByRole('button', { name: 'Save Role' }).click();
    await expect(adminPage.locator('#app').getByText(/The Description field is required/i)).toBeVisible();
  });

  test('Create role with all required fields empty shows all validation errors', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');
    await adminPage.getByRole('link', { name: 'Create Role' }).click();
    await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
    await adminPage.getByRole('option', { name: 'Custom' }).locator('span').first().click();
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('');
    await adminPage.getByRole('button', { name: 'Save Role' }).click();
    await expect(adminPage.locator('#app').getByText(/The Permissions field is required/i)).toBeVisible();
    await expect(adminPage.locator('#app').getByText(/The Name field is required/i)).toBeVisible();
    await expect(adminPage.locator('#app').getByText(/The Description field is required/i)).toBeVisible();
  });

  // --- CRUD Tests: Administrator (All permissions) role ---

  test('Create Administrator role', async ({ adminPage }) => {
    const uid = generateUid();
    const roleName = `${uid} Admin`;
    await createAdminRole(adminPage, roleName, 'Full access role');

    // Cleanup
    await deleteRole(adminPage, roleName);
  });

  test('Search for seeded Administrator role', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, 'Administrator');
    await expect(adminPage.locator('#app').getByText('Administrator', { exact: true }).first()).toBeVisible();
  });

  test('Update Administrator role', async ({ adminPage }) => {
    const uid = generateUid();
    const roleName = `${uid} Admin`;

    // Create
    await createAdminRole(adminPage, roleName, 'Full access role');

    // Search and edit
    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, roleName);
    const row = adminPage.locator('#app div').filter({ hasText: roleName });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('Updated full access of UnoPim');
    await clickSaveAndExpect(adminPage, 'Save Role', /Roles is updated successfully/i);

    // Cleanup
    await deleteRole(adminPage, roleName);
  });

  test('Delete Administrator role', async ({ adminPage }) => {
    const uid = generateUid();
    const roleName = `${uid} Admin`;

    // Create
    await createAdminRole(adminPage, roleName, 'Full access role');

    // Delete
    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, roleName);
    const row = adminPage.locator('#app div').filter({ hasText: roleName });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Roles is deleted successfully/i)).toBeVisible();
  });

  // --- CRUD Tests: Custom permission role ---

  test('Create Custom role', async ({ adminPage }) => {
    const uid = generateUid();
    const roleName = `${uid} CatMgr`;
    await createCustomRole(adminPage, roleName, 'Catalog only access', ['Dashboard', 'Catalog']);

    // Cleanup
    await deleteRole(adminPage, roleName);
  });

  test('Update Custom role', async ({ adminPage }) => {
    const uid = generateUid();
    const roleName = `${uid} CatMgr`;

    // Create
    await createCustomRole(adminPage, roleName, 'Catalog only access', ['Dashboard', 'Catalog']);

    // Search and edit — remove Categories permission
    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, roleName);
    const row = adminPage.locator('#app div').filter({ hasText: roleName });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');
    const categoriesLabel = adminPage.locator('label').filter({ hasText: 'Categories' }).locator('span').first();
    await categoriesLabel.waitFor({ state: 'visible', timeout: 10000 });
    await categoriesLabel.click();
    await clickSaveAndExpect(adminPage, 'Save Role', /Roles is updated successfully/i);

    // Cleanup
    await deleteRole(adminPage, roleName);
  });

  test('Delete Custom role', async ({ adminPage }) => {
    const uid = generateUid();
    const roleName = `${uid} CatMgr`;

    // Create
    await createCustomRole(adminPage, roleName, 'Catalog only access', ['Dashboard', 'Catalog']);

    // Delete
    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, roleName);
    const row = adminPage.locator('#app div').filter({ hasText: roleName });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Roles is deleted successfully/i)).toBeVisible();
  });

  // --- Delete seeded Administrator role (should fail) ---

  test('Delete seeded Administrator role shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, 'Administrator');
    const row = adminPage.locator('#app div').filter({ hasText: 'Administrator' });
    await row.locator('span[title="Delete"]').first().click();
    const deleteResponse = adminPage.waitForResponse(
      resp => resp.url().includes('/roles/') && resp.request().method() === 'DELETE'
    );
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await deleteResponse;
    await expect(adminPage.locator('#app').getByText(/Role is already used by/i)).toBeVisible();
  });
});
