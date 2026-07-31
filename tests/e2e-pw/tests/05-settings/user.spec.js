const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Fill the user creation modal.
 *
 * UI locale, catalog locale, default channel and timezone render only while
 * updating — on create UserForm::prepareForValidation() supplies them — so the
 * create modal exposes name, email, password, role and status alone.
 *
 * @param {import('@playwright/test').Page} adminPage
 * @param {object} opts
 */
async function fillUserForm(adminPage, {
  name = '',
  email = '',
  password = '',
  confirmPassword = '',
  selectRole = true,
  enableStatus = true,
} = {}) {
  if (name !== null) {
    await adminPage.getByRole('textbox', { name: 'Name' }).fill(name);
  }
  if (email !== null) {
    await adminPage.getByPlaceholder('email@example.com').fill(email);
  }
  if (password !== null) {
    await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill(password);
  }
  if (confirmPassword !== null) {
    await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill(confirmPassword);
  }

  if (selectRole) {
    const roleMultiselect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="role_id"]') });
    await roleMultiselect.locator('.multiselect__tags').click();
    await adminPage.waitForTimeout(300);
    const roleOption = adminPage.getByRole('option').first();
    await roleOption.waitFor({ state: 'visible', timeout: 10000 });
    await roleOption.click();
    await adminPage.locator('body').click();
  }

  if (enableStatus) {
    /* No force: the role dropdown overlays the toggle while it closes, and a
     * forced click lands on the selected option, deselecting it. */
    await adminPage.locator('label[for="status"]').click();
  }
}

/**
 * Helper: Create a user end-to-end and verify success.
 */
async function createUser(adminPage, name, email) {
  await adminPage.goto('/admin/settings/users', { waitUntil: 'networkidle', timeout: 60000 });
  await adminPage.getByRole('button', { name: 'Create User' }).waitFor({ state: 'visible', timeout: 30000 });
  await adminPage.getByRole('button', { name: 'Create User' }).click();

  // Wait for the modal form to be fully rendered
  await adminPage.getByRole('textbox', { name: 'Name' }).waitFor({ state: 'visible', timeout: 15000 });

  await fillUserForm(adminPage, {
    name,
    email,
    password: 'testing123',
    confirmPassword: 'testing123',
  });
  await clickSaveAndExpect(adminPage, 'Save User', /User created successfully/i);
}

/**
 * Helper: Delete a user by email (search, delete, confirm).
 * Silently succeeds if the user is not found.
 */
async function deleteUser(adminPage, email) {
  await adminPage.goto('/admin/settings/users', { waitUntil: 'networkidle', timeout: 60000 });
  await searchInDataGrid(adminPage, email);
  const row = adminPage.locator('#app div').filter({ hasText: email });
  const deleteBtn = row.locator('span[title="Delete"]').first();

  try {
    await deleteBtn.waitFor({ state: 'visible', timeout: 3000 });
    await deleteBtn.click({ timeout: 5000 });
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  } catch {
    // User not found — that's fine
  }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

test.describe('User Management', () => {

  // --- Validation Tests ---

  test('Create User with empty Name shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await fillUserForm(adminPage, {
      name: '',
      email: `${uid}@example.com`,
      password: 'testing123',
      confirmPassword: 'testing123',
    });
    await clickSave(adminPage, 'Save User');
    await expect(adminPage.locator('#app').getByText(/The Name field is required/i)).toBeVisible();
  });

  test('Create User with empty Email shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await fillUserForm(adminPage, {
      name: `${uid} User`,
      email: '',
      password: 'testing123',
      confirmPassword: 'testing123',
    });
    await clickSave(adminPage, 'Save User');
    await expect(adminPage.locator('#app').getByText(/The Email field is required/i)).toBeVisible();
  });

  test('Create User with empty Password shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await fillUserForm(adminPage, {
      name: `${uid} User`,
      email: `${uid}@example.com`,
      password: '',
      confirmPassword: 'testing123',
    });
    await clickSave(adminPage, 'Save User');
    await expect(adminPage.locator('#app').getByText(/The Password field is required/i)).toBeVisible();
  });

  test('Create User with empty Confirm Password shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await fillUserForm(adminPage, {
      name: `${uid} User`,
      email: `${uid}@example.com`,
      password: 'testing123',
      confirmPassword: '',
    });
    await clickSave(adminPage, 'Save User');
    await expect(adminPage.locator('#app').getByText(/The Confirm Password field is required/i)).toBeVisible();
  });

  test('Create User with mismatched passwords shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await fillUserForm(adminPage, {
      name: `${uid} User`,
      email: `${uid}@example.com`,
      password: 'testing123',
      confirmPassword: 'testing456',
    });
    await clickSave(adminPage, 'Save User');
    await expect(adminPage.locator('#app').getByText(/The Confirm Password field confirmation does not match/i)).toBeVisible();
  });

  test('Create User omits the scope fields the server defaults', async ({ adminPage }) => {
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await adminPage.getByRole('textbox', { name: 'Name' }).waitFor({ state: 'visible', timeout: 15000 });

    await expect(adminPage.locator('input[name="ui_locale_id"]')).toHaveCount(0);
    await expect(adminPage.locator('input[name="catalog_locale_id"]')).toHaveCount(0);
    await expect(adminPage.locator('input[name="default_channel_id"]')).toHaveCount(0);
    await expect(adminPage.locator('input[name="timezone"]')).toHaveCount(0);
  });

  test('Create User with empty Role shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await fillUserForm(adminPage, {
      name: `${uid} User`,
      email: `${uid}@example.com`,
      password: 'testing123',
      confirmPassword: 'testing456',
      selectRole: false,
    });
    await clickSave(adminPage, 'Save User');
    await expect(adminPage.locator('#app').getByText(/The Role field is required/i)).toBeVisible();
  });

  // --- CRUD Tests ---

  test('Create User successfully', async ({ adminPage }) => {
    const uid = generateUid();
    const email = `${uid}@example.com`;
    await createUser(adminPage, `${uid} User`, email);

    // Cleanup
    await deleteUser(adminPage, email);
  });

  test('Search for User', async ({ adminPage }) => {
    const uid = generateUid();
    const email = `${uid}@example.com`;

    // Create
    await createUser(adminPage, `${uid} User`, email);

    // Search
    await navigateTo(adminPage, 'users');
    await searchInDataGrid(adminPage, email);
    await expect(adminPage.locator('#app').getByText(email)).toBeVisible();

    // Cleanup
    await deleteUser(adminPage, email);
  });

  test('Update User', async ({ adminPage }) => {
    const uid = generateUid();
    const email = `${uid}@example.com`;

    // Create
    await createUser(adminPage, `${uid} User`, email);

    // Search and edit
    await navigateTo(adminPage, 'users');
    await searchInDataGrid(adminPage, email);
    const row = adminPage.locator('#app div').filter({ hasText: email });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('label[for="status"]').click();
    await adminPage.locator('input[name="current_password"]').fill(process.env.ADMIN_PASSWORD || 'admin123');
    await clickSaveAndExpect(adminPage, 'Save User', /User updated successfully/i);

    // Cleanup
    await deleteUser(adminPage, email);
  });

  test('Delete User', async ({ adminPage }) => {
    const uid = generateUid();
    const email = `${uid}@example.com`;

    // Create
    await createUser(adminPage, `${uid} User`, email);

    // Delete
    await navigateTo(adminPage, 'users');
    await searchInDataGrid(adminPage, email);
    const row = adminPage.locator('#app div').filter({ hasText: email });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/User deleted successfully/i)).toBeVisible({ timeout: 20000 });
  });

  /* The suite authenticates as this admin, so destroy() rejects it as the logged
   * in user before it ever reaches the last-user guard. */
  test('Delete signed-in admin user shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'users');
    await searchInDataGrid(adminPage, 'admin@example.com');
    const row = adminPage.locator('#app div').filter({ hasText: 'admin@example.com' });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/The logged in user can not be deleted/i)).toBeVisible();
  });
});
