const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');
const path = require('path');

/**
 * Magic AI prompts — delete with view + delete permission only.
 *
 * The grid builds its actions conditionally on permissions and the view looked
 * them up by their positional fallback index. With edit withheld, the delete
 * action slid into the slot the view treats as edit, so the row and the first
 * icon navigated to the delete URL with GET and the route answered 405. The
 * actions are now addressed by name, so only the delete icon renders and it
 * issues a real DELETE.
 */
test.describe('Magic AI prompt — delete without edit permission', () => {
  test.setTimeout(180000);

  const ADMIN_STATE = path.resolve(__dirname, '../../.state/admin-auth.json');
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
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('Prompt view and delete only');

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
          description: 'Prompt view and delete only',
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

  /**
   * Create the prompt the restricted user will delete. Submitted directly so
   * the fixture does not depend on the create modal's field layout — the modal
   * is not what this test is exercising.
   */
  async function createPrompt(adminPage, title) {
    await adminPage.goto(`${BASE_URL}/admin/magic-ai/prompts`, { waitUntil: 'domcontentloaded' });

    const status = await adminPage.evaluate(
      async ({ baseURL, title }) => {
        const token = document.querySelector('input[name="_token"]')?.value ?? '';
        const body = new URLSearchParams({
          _token: token,
          title,
          prompt: 'Write a short headline for this product.',
          type: 'product',
          purpose: 'text_generation',
        });

        const response = await fetch(`${baseURL}/admin/magic-ai/create-prompt`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body: body.toString(),
        });

        return response.status;
      },
      { baseURL: BASE_URL, title },
    );

    expect(status, 'the prompt fixture must be created').toBeLessThan(400);
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

  test('deletes a prompt without hitting a 405', async ({ browser }) => {
    const uid = generateUid();
    const roleName = `PromptDelete${uid}`;
    const userEmail = `prompt-delete-${uid}@example.com`;
    const userPassword = 'Test@12345';
    const promptTitle = `Repro Prompt ${uid}`;

    const adminContext = await browser.newContext({ storageState: ADMIN_STATE });
    await adminContext.addInitScript(HIDE_OVERLAYS);
    const adminPage = await adminContext.newPage();

    await createPrompt(adminPage, promptTitle);

    await createRole(adminPage, roleName);
    const roleId = await setRolePermissions(adminPage, roleName, [
      'dashboard',
      'ai-agent',
      'ai-agent.prompt',
      'ai-agent.prompt.delete',
    ]);
    await createUserWithRole(adminPage, {
      name: `Prompt Deleter ${uid}`,
      email: userEmail,
      password: userPassword,
      roleId,
    });

    const userContext = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    await userContext.addInitScript(HIDE_OVERLAYS);
    const userPage = await userContext.newPage();

    await userPage.goto(`${BASE_URL}/admin/login`);
    await userPage.locator('input[name="email"]').fill(userEmail);
    await userPage.locator('input[name="password"]').fill(userPassword);
    await userPage.locator('button[aria-label="Sign In"]').click();
    await userPage.waitForURL((url) => !url.pathname.endsWith('/admin/login'), { timeout: 30000 });

    await userPage.goto(`${BASE_URL}/admin/magic-ai/prompts`, { waitUntil: 'domcontentloaded' });

    const row = userPage.locator('div.row.grid').filter({ hasText: promptTitle }).first();
    await expect(row).toBeVisible({ timeout: 20000 });

    // No edit permission — only the delete icon is offered.
    await expect(row.locator('span.icon-edit')).toHaveCount(0);
    await expect(row.locator('span.icon-delete')).toHaveCount(1);

    // Record every response so a 405 cannot slip past unnoticed.
    const statuses = [];
    userPage.on('response', (response) => {
      if (/\/magic-ai\/delete\//.test(response.url())) {
        statuses.push({ method: response.request().method(), status: response.status() });
      }
    });

    await row.locator('span.icon-delete').first().click();

    // Confirm via the modal's agree button by class — the restricted user's UI
    // locale is whatever the install defaults to, so its label is not stable.
    await userPage.locator('button.danger-button:visible').first().click();

    await expect.poll(() => statuses.length, { timeout: 20000 }).toBeGreaterThan(0);

    expect(statuses.every((entry) => entry.status !== 405)).toBe(true);
    expect(statuses.some((entry) => entry.method === 'DELETE' && entry.status < 400)).toBe(true);

    // The prompt is gone from the grid.
    await expect(userPage.locator('div.row.grid').filter({ hasText: promptTitle })).toHaveCount(0, { timeout: 20000 });

    await userContext.close();

    await removeRecord(adminPage, 'users', userEmail);
    await removeRecord(adminPage, 'roles', roleName);
    await adminContext.close();
  });
});
