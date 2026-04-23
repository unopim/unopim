const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create a custom role with no permissions via the UI.
 */
async function createEmptyRole(adminPage, roleName) {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).or(adminPage.getByRole('button', { name: 'Create Role' })).first().click();
  await adminPage.waitForLoadState('networkidle').catch(() => {});

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
 * Helper: Strip all permissions from a role via a direct PUT request.
 * The create form requires at least one permission to pass frontend validation,
 * so we create with Dashboard and then strip via API to trigger the Bouncer's
 * empty-permissions 403 logout flow.
 */
async function stripRolePermissions(adminPage, roleName, baseURL) {
  await navigateTo(adminPage, 'roles');
  await searchInDataGrid(adminPage, roleName);
  await adminPage.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('networkidle').catch(() => {});

  const roleId = adminPage.url().match(/\/(\d+)$/)?.[1];
  if (!roleId) return;

  // Use fetch() inside the browser context so the request carries the admin
  // session cookies automatically and the CSRF token is taken from the live page.
  // Omitting permissions from the FormData makes the backend default to [].
  await adminPage.evaluate(async ({ baseURL, roleId, roleName }) => {
    // The admin layout does not use a <meta name="csrf-token"> tag —
    // the token is in the hidden _token input of the current form.
    const token = document.querySelector('input[name="_token"]')?.value ?? '';
    const body = new URLSearchParams({
      _token: token,
      _method: 'PUT',
      name: roleName,
      description: 'Test role with minimal permissions for 403 message test',
      permission_type: 'custom',
      // Omitting permissions → backend sets permissions to []
    });
    await fetch(`${baseURL}/admin/settings/roles/edit/${roleId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    });
  }, { baseURL, roleId, roleName });
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
  await adminPage.getByRole('button', { name: 'Delete', exact: true }).first().click();
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
  await adminPage.getByRole('button', { name: 'Delete', exact: true }).first().click();
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

    // Strip all permissions from the role so the Bouncer's empty-permissions
    // 403 logout is triggered when the user logs in.
    await stripRolePermissions(adminPage, roleName, baseURL);

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

    // The Bouncer detects empty permissions, logs the user out, and redirects
    // back to the login page with a session-flashed error message.
    await userPage.waitForLoadState('networkidle').catch(() => {});

    // Step 3: Positive assertion — the translated 403 message must be present.
    // It is inlined into the page source by the flash-group component.
    const pageSource = await userPage.content();
    expect(pageSource).toContain('You do not have permission to access this page');

    // Negative assertions — no raw translation keys must appear.
    expect(pageSource).not.toContain('admin::app.errors.403.message');
    expect(pageSource).not.toContain('admin::app.error');

    // Also verify the translated toast notification is visible in the DOM.
    await expect(
      userPage.getByText(/You do not have permission to access this page/i).first()
    ).toBeVisible({ timeout: 5000 });

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
