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
    expect(before, 'baseline: restricted user must be denied the privileged write').toBe(403);

    await userPage.goto(`${BASE_URL}/admin/account`, { waitUntil: 'domcontentloaded' });

    // Inject fields the AccountForm does not validate. status is flipped to 0
    // (distinct from the seeded 1) so a successful mass-assignment would be
    // observable: the account would go inactive and block the next login.
    const updateStatus = await userPage.evaluate(async ({ baseURL, name }) => {
      const token = document.querySelector('meta[name="csrf-token"]')?.content
        ?? document.querySelector('input[name="_token"]')?.value
        ?? '';
      const body = new URLSearchParams({
        _token: token,
        _method: 'PUT',
        name,
        role_id: '1',
        status: '0',
        type: 'admin',
      });
      const res = await fetch(`${baseURL}/admin/account`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
        redirect: 'manual',
      });
      return res.status;
    }, { baseURL: BASE_URL, name: `Mass Assign Operator ${uid}` });

    expect(updateStatus, 'the self-update must actually succeed so the field filtering is exercised').toBe(200);

    await userContext.close();

    // Read back the protected fields behaviourally in a fresh session: a login
    // that still succeeds proves status was not flipped to inactive, and a
    // still-denied privileged write proves role_id was not escalated.
    const verifyContext = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    await verifyContext.addInitScript(HIDE_OVERLAYS);
    const verifyPage = await verifyContext.newPage();

    let loggedIn = true;
    try {
      await loginAs(verifyPage, userEmail, userPassword);
    } catch {
      loggedIn = false;
    }
    expect(loggedIn, 'injected status=0 must be dropped — the account must still log in').toBe(true);

    const after = await privilegedWriteStatus(verifyPage);
    expect(after, 'injected role_id must be dropped — the user must still be denied the privileged write').toBe(403);

    await verifyContext.close();

    await removeRecord(adminPage, 'users', userEmail);
    await removeRecord(adminPage, 'roles', roleName);
    await removeRecord(adminPage, 'roles', 'Escalated Role');
    await adminContext.close();
  });
});
