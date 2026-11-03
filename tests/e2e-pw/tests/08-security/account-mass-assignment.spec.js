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
