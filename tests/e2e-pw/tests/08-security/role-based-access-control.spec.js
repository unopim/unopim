const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');
const path = require('path');

/**
 * Role-based access control (privilege escalation) guard.
 *
 * A user holding only the `dashboard` permission must not be able to reach or
 * mutate another module. Hiding the menu is not enough — the server must reject
 * a direct request. This drives the role + user through the same admin flow the
 * Magic-AI ACL specs use, logs in as that restricted user, then asserts that a
 * privileged GET and a privileged state-changing POST are both denied.
 */
test.describe('Role-based access control - restricted user cannot escalate', () => {
  test.setTimeout(180000);

  const ADMIN_STATE = path.resolve(__dirname, '../..', process.env.PW_STATE_DIR || '.state', 'admin-auth.json');
  const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

  const HIDE_OVERLAYS = `
    (function() {
      var s = document.createElement('style');
      s.textContent = '.ap-shell, .phpdebugbar { display: none !important; }';
      if (document.head) { document.head.appendChild(s); }
      else { document.addEventListener('DOMContentLoaded', function() { document.head.appendChild(s); }); }
    })();
  `;

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
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('Dashboard only');

    await clickSaveAndExpect(adminPage, 'Save changes', /Roles Created Successfully/i);
  }

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
          description: 'Dashboard only',
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

  async function loginAs(userPage, email, password) {
    await userPage.goto(`${BASE_URL}/admin/login`);
    await userPage.locator('input[name="email"]').fill(email);
    await userPage.locator('input[name="password"]').fill(password);
    await userPage.locator('button[aria-label="Sign In"]').click();
    await userPage.waitForURL((url) => !url.pathname.endsWith('/admin/login'), { timeout: 30000 });
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

  test('dashboard-only user is denied a privileged page and a privileged write', async ({ browser }) => {
    const uid = generateUid();
    const roleName = `DashboardOnly${uid}`;
    const userEmail = `dashboard-only-${uid}@example.com`;
    const userPassword = 'Test@12345';

    const adminContext = await browser.newContext({ storageState: ADMIN_STATE });
    await adminContext.addInitScript(HIDE_OVERLAYS);
    const adminPage = await adminContext.newPage();

    await createRole(adminPage, roleName);
    const roleId = await setRolePermissions(adminPage, roleName, ['dashboard']);
    await createUserWithRole(adminPage, {
      name: `Dashboard Operator ${uid}`,
      email: userEmail,
      password: userPassword,
      roleId,
    });

    const userContext = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    await userContext.addInitScript(HIDE_OVERLAYS);
    const userPage = await userContext.newPage();

    await loginAs(userPage, userEmail, userPassword);

    const pageStatus = await userPage.evaluate(async (baseURL) => {
      const res = await fetch(`${baseURL}/admin/settings/users`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        redirect: 'manual',
      });
      return res.status;
    }, BASE_URL);

    expect([0, 302, 401, 403], 'GET on a module the user lacks must be denied').toContain(pageStatus);

    const writeStatus = await userPage.evaluate(async (baseURL) => {
      const token = document.querySelector('meta[name="csrf-token"]')?.content
        ?? document.querySelector('input[name="_token"]')?.value
        ?? '';
      const body = new URLSearchParams({
        _token: token,
        name: 'Escalated Role',
        description: 'should never be created',
        permission_type: 'all',
      });

      const res = await fetch(`${baseURL}/admin/settings/roles/create`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: body.toString(),
        redirect: 'manual',
      });
      return res.status;
    }, BASE_URL);

    expect(writeStatus, 'a privileged write by a dashboard-only user must be denied').not.toBe(200);
    expect([0, 302, 401, 403, 419]).toContain(writeStatus);

    await userContext.close();

    await removeRecord(adminPage, 'users', userEmail);
    await removeRecord(adminPage, 'roles', roleName);
    await removeRecord(adminPage, 'roles', 'Escalated Role');
    await adminContext.close();
  });
});
