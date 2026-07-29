const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');
const path = require('path');

/**
 * Export/import profile pages — "Edit" button visibility.
 *
 * The profile pages rendered their Edit button unconditionally, so an admin
 * whose role withholds the edit permission still saw it and was sent to a 403.
 * The button now follows the same permission gate the Export Now button uses.
 */
test.describe('Data transfer profile — edit button ACL', () => {
  test.setTimeout(180000);

  const ADMIN_STATE = path.resolve(__dirname, '../../.state/admin-auth.json');
  const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

  // These contexts are built by hand rather than via the adminPage fixture, so
  // they need the same overlay suppression: on a dev server the Debugbar sits
  // over the unsaved-changes bar and swallows clicks on its Save button.
  const HIDE_OVERLAYS = `
    (function() {
      var s = document.createElement('style');
      s.textContent = '.ap-shell, .phpdebugbar { display: none !important; }';
      if (document.head) { document.head.appendChild(s); }
      else { document.addEventListener('DOMContentLoaded', function() { document.head.appendChild(s); }); }
    })();
  `;

  /** Create a custom role through the UI, seeded with Dashboard so it validates. */
  async function createRole(adminPage, roleName) {
    await navigateTo(adminPage, 'roles');
    await adminPage
      .getByRole('link', { name: 'Create Role' })
      .or(adminPage.getByRole('button', { name: 'Create Role' }))
      .first()
      .click();
    await adminPage.waitForLoadState('networkidle').catch(() => {});

    const dashboardLabel = adminPage.locator('label').filter({ hasText: 'Dashboard' }).locator('span').first();
    await dashboardLabel.waitFor({ state: 'visible', timeout: 10000 });
    await dashboardLabel.click();

    await adminPage.getByRole('textbox', { name: 'Name' }).fill(roleName);
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('Export view without edit permission');

    await clickSaveAndExpect(adminPage, 'Save changes', /Roles Created Successfully/i);
  }

  /**
   * Set the role's permission list exactly. The permission tree is nested and
   * awkward to drive click-by-click, so the values are submitted directly the
   * way the role form would post them.
   */
  async function setRolePermissions(adminPage, roleName, permissions) {
    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, roleName);
    await adminPage.locator('span[title="Edit"]').first().click();
    await adminPage.waitForURL(/\/roles\/edit\/\d+/, { timeout: 15000 });

    const roleId = adminPage.url().match(/\/edit\/(\d+)/)?.[1];
    expect(roleId, 'the created role must be reachable from the grid').toBeTruthy();

    await adminPage.evaluate(
      async ({ baseURL, roleId, roleName, permissions }) => {
        const token = document.querySelector('input[name="_token"]')?.value ?? '';
        const body = new URLSearchParams({
          _token: token,
          _method: 'PUT',
          name: roleName,
          description: 'Export view without edit permission',
          permission_type: 'custom',
        });
        permissions.forEach((permission) => body.append('permissions[]', permission));

        await fetch(`${baseURL}/admin/settings/roles/edit/${roleId}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        });
      },
      { baseURL: BASE_URL, roleId, roleName, permissions },
    );

    return roleId;
  }

  /**
   * Create a user bound to the given role. Submitted directly for the same
   * reason as the permission list — the create form is a modal of dependent
   * multiselects, and none of that is what this test is exercising.
   */
  async function createUserWithRole(adminPage, { name, email, password, roleId }) {
    await navigateTo(adminPage, 'users');

    const status = await adminPage.evaluate(
      async ({ baseURL, name, email, password, roleId }) => {
        const token = document.querySelector('input[name="_token"]')?.value ?? '';
        const body = new URLSearchParams({
          _token: token,
          name,
          email,
          password,
          password_confirmation: password,
          role_id: roleId,
          status: '1',
        });

        const response = await fetch(`${baseURL}/admin/settings/users/create`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        });

        return response.status;
      },
      { baseURL: BASE_URL, name, email, password, roleId },
    );

    expect(status, 'the restricted user must be created').toBeLessThan(400);
  }

  async function removeRecord(adminPage, route, term) {
    await navigateTo(adminPage, route);
    await searchInDataGrid(adminPage, term);
    const deleteBtn = adminPage.locator('span[title="Delete"]').first();
    if (!(await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false))) {
      return;
    }
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete', exact: true }).first().click();
    await adminPage.waitForLoadState('networkidle');
  }

  test('the export profile hides Edit for a role without the edit permission', async ({ browser }) => {
    const uid = generateUid();
    const roleName = `ExportNoEdit${uid}`;
    const userEmail = `export-no-edit-${uid}@example.com`;
    const userPassword = 'Test@12345';

    const adminContext = await browser.newContext({ storageState: ADMIN_STATE });
    await adminContext.addInitScript(HIDE_OVERLAYS);
    const adminPage = await adminContext.newPage();

    await createRole(adminPage, roleName);
    const roleId = await setRolePermissions(adminPage, roleName, [
      'dashboard',
      'data_transfer',
      'data_transfer.export',
      'data_transfer.export.execute',
    ]);
    await createUserWithRole(adminPage, {
      name: `Export Viewer ${uid}`,
      email: userEmail,
      password: userPassword,
      roleId,
    });

    // Act — sign in as the restricted user and open the export profile page.
    const userContext = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    await userContext.addInitScript(HIDE_OVERLAYS);
    const userPage = await userContext.newPage();

    await userPage.goto(`${BASE_URL}/admin/login`);
    await userPage.locator('input[name="email"]').fill(userEmail);
    await userPage.locator('input[name="password"]').fill(userPassword);
    await userPage.locator('button[aria-label="Sign In"]').click();
    await userPage.waitForURL((url) => !url.pathname.endsWith('/admin/login'), { timeout: 30000 });

    // The execute permission keeps the grid's Export action, which opens the profile page.
    // Selectors stay locale-independent: the user's UI locale is whatever the
    // install defaults to, so icons and hrefs are matched instead of labels.
    await userPage.goto(`${BASE_URL}/admin/data-transfer/exports`, { waitUntil: 'domcontentloaded' });
    await userPage.locator('span.icon-export').first().click();
    await userPage.waitForURL(/\/data-transfer\/exports\/export\/\d+/, { timeout: 20000 });

    // The profile page renders (view permission granted) — its Back link proves it.
    await expect(userPage.locator('a[href$="/data-transfer/exports"]').first()).toBeVisible({ timeout: 15000 });

    // ...but the Edit button is gone.
    await expect(userPage.locator('a[href*="/data-transfer/exports/edit/"]')).toHaveCount(0);

    await userContext.close();

    // Cleanup — remove the user first so the role is no longer referenced.
    await removeRecord(adminPage, 'users', userEmail);
    await removeRecord(adminPage, 'roles', roleName);
    await adminContext.close();
  });
});
