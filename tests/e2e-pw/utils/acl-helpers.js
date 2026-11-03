const { expect } = require('@playwright/test');
const { navigateTo, searchInDataGrid, clickSaveAndExpect } = require('./helpers');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

/**
 * Init-script that hides the Agenting-PIM and Debugbar overlays so their
 * buttons do not intercept clicks or trip strict-mode locator checks. Shared
 * by the security specs that drive a second, restricted-user browser context.
 */
const HIDE_OVERLAYS = `
  (function() {
    var s = document.createElement('style');
    s.textContent = '.ap-shell, .ap-panel, .ap-backdrop, .phpdebugbar { display: none !important; }';
    if (document.head) { document.head.appendChild(s); }
    else { document.addEventListener('DOMContentLoaded', function() { document.head.appendChild(s); }); }
  })();
`;

/**
 * Create a role carrying only the Dashboard permission through the admin UI.
 *
 * @param {import('@playwright/test').Page} adminPage
 * @param {string} roleName
 */
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

/**
 * Narrow a role to a custom permission set by posting the role update form,
 * and return its id.
 *
 * @param {import('@playwright/test').Page} adminPage
 * @param {string} roleName
 * @param {string[]} permissions
 * @returns {Promise<string>}
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

/**
 * Create a user bound to the given role by posting the user create form.
 *
 * @param {import('@playwright/test').Page} adminPage
 * @param {{name: string, email: string, password: string, roleId: string}} user
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

/**
 * Sign a restricted user into a fresh (unauthenticated) browser context.
 *
 * @param {import('@playwright/test').Page} userPage
 * @param {string} email
 * @param {string} password
 */
async function loginAs(userPage, email, password) {
  await userPage.goto(`${BASE_URL}/admin/login`);
  await userPage.locator('input[name="email"]').fill(email);
  await userPage.locator('input[name="password"]').fill(password);
  await userPage.locator('button[aria-label="Sign In"]').click();
  await userPage.waitForURL((url) => !url.pathname.endsWith('/admin/login'), { timeout: 30000 });
}

/**
 * Attempt a privileged, state-changing write (role creation) as the current
 * user and return the HTTP status. Used to prove a restricted user gained no
 * power. `redirect: 'manual'` keeps a login/deny redirect visible as 0/3xx.
 *
 * @param {import('@playwright/test').Page} userPage
 * @returns {Promise<number>}
 */
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

/**
 * Delete the first matching datagrid record, a no-op if already absent.
 *
 * @param {import('@playwright/test').Page} adminPage
 * @param {string} route
 * @param {string} term
 */
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

module.exports = {
  BASE_URL,
  HIDE_OVERLAYS,
  createRole,
  setRolePermissions,
  createUserWithRole,
  loginAs,
  privilegedWriteStatus,
  removeRecord,
};
