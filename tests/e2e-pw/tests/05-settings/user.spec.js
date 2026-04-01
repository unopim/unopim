const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid } = require('../../utils/helpers');

/**
 * Helper: Fill user creation modal with all fields.
 * @param {import('@playwright/test').Page} adminPage
 * @param {object} opts
 */
async function fillUserForm(adminPage, {
  name = '',
  email = '',
  password = '',
  confirmPassword = '',
  selectLocale = true,
  selectTimezone = true,
  selectRole = true,
  enableStatus = true,
} = {}) {
  if (name !== null) {
    await adminPage.getByRole('textbox', { name: 'Name' }).fill(name);
  }
  if (email !== null) {
    await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill(email);
  }
  if (password !== null) {
    await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill(password);
  }
  if (confirmPassword !== null) {
    await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill(confirmPassword);
  }

  if (selectLocale) {
    await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  }

  if (selectTimezone) {
    // Click the timezone multiselect wrapper to open it and focus the search input
    const tzWrapper = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="timezone"]') });
    await tzWrapper.click();
    // The multiselect focuses its internal search input; type into it
    const tzInput = tzWrapper.locator('input[type="text"]').first();
    await tzInput.fill('America/New_York');
    const tzOption = adminPage.getByRole('option', { name: /New_York/ }).first();
    await tzOption.waitFor({ state: 'visible', timeout: 5000 });
    await tzOption.click();
  }

  if (selectRole) {
    await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').waitFor({ state: 'visible', timeout: 5000 });
    await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option').first().click();
    // Close the dropdown by clicking elsewhere
    await adminPage.locator('body').click();
  }

  if (enableStatus) {
    await adminPage.locator('label[for="status"]').click({ force: true });
  }
}

/**
 * Helper: Create a user end-to-end and verify success.
 */
async function createUser(adminPage, name, email) {
  await navigateTo(adminPage, 'users');
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await fillUserForm(adminPage, {
    name,
    email,
    password: 'testing123',
    confirmPassword: 'testing123',
  });
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/User created successfully/i)).toBeVisible({ timeout: 15000 });
}

/**
 * Helper: Delete a user by email (search, delete, confirm).
 * Silently succeeds if the user is not found.
 */
async function deleteUser(adminPage, email) {
  await navigateTo(adminPage, 'users');
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
    await adminPage.getByRole('button', { name: 'Save User' }).click();
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
    await adminPage.getByRole('button', { name: 'Save User' }).click();
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
    await adminPage.getByRole('button', { name: 'Save User' }).click();
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
    await adminPage.getByRole('button', { name: 'Save User' }).click();
    await expect(adminPage.locator('#app').getByText(/The Password field is required/i)).toBeVisible();
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
    await adminPage.getByRole('button', { name: 'Save User' }).click();
    await expect(adminPage.locator('#app').getByText(/The Password field confirmation does not match/i)).toBeVisible();
  });

  test('Create User with empty UI Locale shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await fillUserForm(adminPage, {
      name: `${uid} User`,
      email: `${uid}@example.com`,
      password: 'testing123',
      confirmPassword: 'testing123',
      selectLocale: false,
    });
    await adminPage.getByRole('button', { name: 'Save User' }).click();
    await expect(adminPage.locator('#app').getByText(/The UI Locale field is required/i)).toBeVisible();
  });

  test('Create User with empty Timezone shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'users');
    await adminPage.getByRole('button', { name: 'Create User' }).click();
    await fillUserForm(adminPage, {
      name: `${uid} User`,
      email: `${uid}@example.com`,
      password: 'testing123',
      confirmPassword: 'testing456',
      selectTimezone: false,
    });
    await adminPage.getByRole('button', { name: 'Save User' }).click();
    await expect(adminPage.locator('#app').getByText(/The Timezone field is required/i)).toBeVisible();
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
    await adminPage.getByRole('button', { name: 'Save User' }).click();
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
    await adminPage.getByRole('button', { name: 'Save User' }).click();
    await expect(adminPage.locator('#app').getByText(/User updated successfully/i)).toBeVisible({ timeout: 15000 });

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
    await expect(adminPage.locator('#app').getByText(/User deleted successfully/i)).toBeVisible({ timeout: 15000 });
  });

  test('Delete default admin user shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'users');
    await searchInDataGrid(adminPage, 'admin@example.com');
    const row = adminPage.locator('#app div').filter({ hasText: 'admin@example.com' });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Last User delete failed/i)).toBeVisible();
  });
});
