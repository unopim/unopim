const { test, expect } = require('../../utils/fixtures');
const { generateUid } = require('../../utils/helpers');
const {
  BASE_URL,
  HIDE_OVERLAYS,
  createRole,
  setRolePermissions,
  createUserWithRole,
  loginAs,
  privilegedWriteStatus,
  removeRecord,
} = require('../../utils/acl-helpers');
const path = require('path');

/**
 * Role-based access control (privilege escalation) guard.
 *
 * A user holding only the `dashboard` permission must not be able to reach or
 * mutate another module. Hiding the menu is not enough — the server must reject
 * a direct request. This drives the role + user through the admin flow, logs in
 * as that restricted user, then asserts that a privileged GET and a privileged
 * state-changing POST are both denied.
 */
test.describe('Role-based access control - restricted user cannot escalate', () => {
  test.setTimeout(180000);

  const ADMIN_STATE = path.resolve(__dirname, '../..', process.env.PW_STATE_DIR || '.state', 'admin-auth.json');

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

    expect([401, 403], 'GET on a module the user lacks must be denied').toContain(pageStatus);

    const writeStatus = await privilegedWriteStatus(userPage);

    expect(writeStatus, 'the settings.roles.create route ACL must reject a dashboard-only user with 403').toBe(403);

    await userContext.close();

    await removeRecord(adminPage, 'users', userEmail);
    await removeRecord(adminPage, 'roles', roleName);
    await removeRecord(adminPage, 'roles', 'Escalated Role');
    await adminContext.close();
  });
});
