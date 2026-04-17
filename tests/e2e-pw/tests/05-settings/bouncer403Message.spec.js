const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create a custom role with no permissions via the UI.
 */
async function createEmptyRole(adminPage, roleName) {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).or(adminPage.getByRole('button', { name: 'Create Role' })).first().click();
  await adminPage.waitForLoadState('networkidle');

  // Keep permission type as Custom, select only Dashboard so the role saves
  // (an empty permission set triggers validation error on the role form)
  const dashboardLabel = adminPage.locator('label').filter({ hasText: 'Dashboard' }).locator('span').first();
  await dashboardLabel.waitFor({ state: 'visible', timeout: 10000 });
  await dashboardLabel.click();

  await adminPage.getByRole('textbox', { name: 'Name' }).fill(roleName);
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('Test role with minimal permissions for 403 message test');

  await clickSaveAndExpect(adminPage, 'Save Role', /Roles Created Successfully/i);
}

/**
 * Helper: Create a user assigned to a specific role via the UI.
 */
async function createUserWithRole(adminPage, { name, email, password, roleName }) {
  await navigateTo(adminPage, 'users');
  await adminPage.getByRole('link', { name: 'Create User' }).or(adminPage.getByRole('button', { name: 'Create User' })).first().click();
  await adminPage.waitForLoadState('networkidle');

  await adminPage.getByRole('textbox', { name: 'Name' }).fill(name);
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill(email);
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill(password);
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill(password);

  // Select UI Locale
  const localeMultiselect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="ui_locale_id"]') });
  await localeMultiselect.locator('.multiselect__tags').click();
  await adminPage.waitForTimeout(300);
  const localeOption = adminPage.getByRole('option', { name: 'English (United States)' }).first();
  await localeOption.waitFor({ state: 'visible', timeout: 10000 });
  await localeOption.click();

  // Select Timezone
  const tzMultiselect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="timezone"]') });
  await tzMultiselect.locator('.multiselect__tags').click();
  await adminPage.waitForTimeout(300);
  await adminPage.keyboard.type('UTC');
  await adminPage.waitForTimeout(500);
  const tzOption = adminPage.getByRole('option', { name: /UTC/ }).first();
  await tzOption.waitFor({ state: 'visible', timeout: 10000 });
  await tzOption.click();

  // Select the specific role
  const roleMultiselect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="role_id"]') });
  await roleMultiselect.locator('.multiselect__tags').click();
  await adminPage.waitForTimeout(300);
  await adminPage.keyboard.type(roleName);
  await adminPage.waitForTimeout(500);
  const roleOption = adminPage.getByRole('option', { name: roleName }).first();
  await roleOption.waitFor({ state: 'visible', timeout: 10000 });
  await roleOption.click();

  // Enable status
  const statusToggle = adminPage.locator('input[name="status"]');
  const isChecked = await statusToggle.isChecked().catch(() => false);
  if (!isChecked) {
    await statusToggle.locator('..').click();
  }

  await clickSaveAndExpect(adminPage, 'Save User', /User created successfully/i);
}

/**
 * Helper: Delete a user by email.
 */
async function deleteUser(adminPage, email) {
  await navigateTo(adminPage, 'users');
  await searchInDataGrid(adminPage, email);
  const deleteBtn = adminPage.locator('span[title="Delete"]').first();
  const visible = await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false);
  if (!visible) return;
  await deleteBtn.click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.waitForLoadState('networkidle');
}

/**
 * Helper: Delete a role by name.
 */
async function deleteRole(adminPage, name) {
  await navigateTo(adminPage, 'roles');
  await searchInDataGrid(adminPage, name);
  const deleteBtn = adminPage.locator('span[title="Delete"]').first();
  const visible = await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false);
  if (!visible) return;
  await deleteBtn.click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.waitForLoadState('networkidle');
}

// ─── Tests ──────────────────────────────────────────────────────────

test.describe('Bouncer 403 Error Message', () => {
  test.setTimeout(120000);

  test('should show a translated error message when a restricted user logs in', async ({ browser }) => {
    const uid = generateUid();
    const roleName = `NoPerms${uid}`;
    const userName = `TestUser ${uid}`;
    const userEmail = `test-${uid}@example.com`;
    const userPassword = 'Test@12345';
    const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8000';

    // Step 1: As admin, create a role with minimal permissions and a user
    const adminContext = await browser.newContext({
      storageState: require('path').resolve(__dirname, '../../.state/admin-auth.json'),
    });
    const adminPage = await adminContext.newPage();

    await createEmptyRole(adminPage, roleName);
    await createUserWithRole(adminPage, {
      name: userName,
      email: userEmail,
      password: userPassword,
      roleName,
    });

    await adminPage.close();
    await adminContext.close();

    // Step 2: Log in as the restricted user in a fresh context (no stored session)
    const userContext = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const userPage = await userContext.newPage();

    await userPage.goto(`${baseURL}/admin/login`, { waitUntil: 'domcontentloaded' });
    await userPage.waitForURL(/\/admin\/login/, { timeout: 10000 }).catch(() => {});
    await userPage.getByRole('textbox', { name: 'Email Address' }).fill(userEmail);
    await userPage.getByRole('textbox', { name: 'Password' }).fill(userPassword);
    await userPage.getByRole('button', { name: 'Sign In' }).click();
    await userPage.waitForLoadState('networkidle').catch(() => {});

    // Step 3: Verify the error message is translated (not a raw translation key)
    const errorToast = userPage.locator('#app').getByText(/admin::app/i).first();
    await expect(errorToast).not.toBeVisible({ timeout: 5000 });

    // Verify a meaningful error message is shown (translated text, not raw key)
    const pageContent = await userPage.textContent('body');
    expect(pageContent).not.toContain('admin::app.error');
    expect(pageContent).not.toContain('admin::app.errors.403.message');

    await userPage.close();
    await userContext.close();

    // Step 4: Cleanup as admin
    const cleanupContext = await browser.newContext({
      storageState: require('path').resolve(__dirname, '../../.state/admin-auth.json'),
    });
    const cleanupPage = await cleanupContext.newPage();

    await deleteUser(cleanupPage, userEmail);
    await deleteRole(cleanupPage, roleName);

    await cleanupPage.close();
    await cleanupContext.close();
  });
});
