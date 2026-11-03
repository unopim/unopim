const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');
const path = require('path');

/**
 * Mass-assignment / privilege-escalation guard on self-service account update.
 *
 * admin.account.update feeds $request->safe() into $user->update(), so a field
 * the AccountForm does not validate (role_id, status, type) must never reach
 * the model even though the Admin model lists it as fillable. A dashboard-only
 * user POSTs its own profile with those fields injected, then is proven to hold
 * no new power: the privileged write it was denied before is still denied.
 */
test.describe('Mass assignment - self account update cannot escalate role', () => {
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

  async function privilegedWriteStatus(userPage) {
    return userPage.evaluate(async (baseURL) => {
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

  test('injected role_id/status/type on account update are ignored', async ({ browser }) => {
    const uid = generateUid();
    const roleName = `DashboardOnly${uid}`;
    const userEmail = `massassign-${uid}@example.com`;
    const userPassword = 'Test@12345';

    const adminContext = await browser.newContext({ storageState: ADMIN_STATE });
    await adminContext.addInitScript(HIDE_OVERLAYS);
    const adminPage = await adminContext.newPage();

    await createRole(adminPage, roleName);
    const roleId = await setRolePermissions(adminPage, roleName, ['dashboard']);
    await createUserWithRole(adminPage, {
      name: `Mass Assign Operator ${uid}`,
      email: userEmail,
      password: userPassword,
      roleId,
    });

    const userContext = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    await userContext.addInitScript(HIDE_OVERLAYS);
    const userPage = await userContext.newPage();

    await loginAs(userPage, userEmail, userPassword);

    const before = await privilegedWriteStatus(userPage);
    expect(before, 'baseline: restricted user must be denied the privileged write').not.toBe(200);

    await userPage.goto(`${BASE_URL}/admin/account`, { waitUntil: 'domcontentloaded' });

    const updateStatus = await userPage.evaluate(async ({ baseURL, name }) => {
      const token = document.querySelector('meta[name="csrf-token"]')?.content
        ?? document.querySelector('input[name="_token"]')?.value
        ?? '';
      const body = new URLSearchParams({
        _token: token,
        _method: 'PUT',
        name,
        // Injected fields the AccountForm does not validate — must be dropped.
        role_id: '1',
        status: '1',
        type: 'admin',
      });
      const res = await fetch(`${baseURL}/admin/account`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: body.toString(),
        redirect: 'manual',
      });
      return res.status;
    }, { baseURL: BASE_URL, name: `Mass Assign Operator ${uid}` });

    expect(updateStatus, 'the profile update itself should not server-error').toBeLessThan(500);

    const after = await privilegedWriteStatus(userPage);
    expect(after, 'after injecting role_id, the user must still be denied the privileged write').not.toBe(200);

    await userContext.close();

    await removeRecord(adminPage, 'users', userEmail);
    await removeRecord(adminPage, 'roles', roleName);
    await removeRecord(adminPage, 'roles', 'Escalated Role');
    await adminContext.close();
  });
});
