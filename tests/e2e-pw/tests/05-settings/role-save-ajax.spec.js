const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

async function togglePermission(adminPage, value) {
  const box = adminPage.locator(`input[type="checkbox"][name="permissions[]"][value="${value}"]`);

  await box.waitFor({ state: 'attached', timeout: 15000 });

  const id = await box.getAttribute('id');

  await adminPage.locator(`label[for="${id}"] span`).first().click();
}

async function instrumentFlashes(adminPage) {
  await adminPage.evaluate(() => {
    sessionStorage.setItem('pwFlashes', '[]');

    window.__pwNoReload = true;

    const record = (node) => {
      if (node.nodeType !== 1 || !node.matches('[role="alert"]')) {
        return;
      }

      const seen = JSON.parse(sessionStorage.getItem('pwFlashes') || '[]');

      seen.push({
        type: /border-l-red|border-r-red/.test(node.className) ? 'error' : 'other',
        message: (node.textContent || '').replace(/\s+/g, ' ').trim(),
      });

      sessionStorage.setItem('pwFlashes', JSON.stringify(seen));
    };

    new MutationObserver((mutations) => {
      mutations.forEach((mutation) => mutation.addedNodes.forEach(record));
    }).observe(document.body, { childList: true, subtree: true });
  });
}

async function readFlashes(adminPage) {
  return adminPage.evaluate(() => JSON.parse(sessionStorage.getItem('pwFlashes') || '[]'));
}

async function delaySaveResponse(adminPage, ms = 1500) {
  await adminPage.route('**/admin/settings/roles/edit/**', async (route) => {
    if (route.request().method() === 'GET') {
      return route.continue();
    }

    await new Promise((resolve) => setTimeout(resolve, ms));

    return route.continue();
  });
}

async function saveRoleUpdateAndWait(adminPage) {
  const saveResponse = adminPage.waitForResponse(
    (response) => response.request().method() === 'POST' && /\/admin\/settings\/roles\/edit\/\d+/.test(response.url()),
    { timeout: 30000 }
  );

  await adminPage.getByRole('button', { name: 'Save changes' }).click();

  await saveResponse;
}

test.describe('Role save posts over AJAX', () => {
  test('updating a role raises no error toast and does not reload the page', async ({ adminPage }) => {
    const name = `AjaxRole ${generateUid()}`;

    await navigateTo(adminPage, 'roles');
    await adminPage.getByRole('link', { name: 'Create Role' }).click();
    await adminPage.waitForLoadState('networkidle');

    await togglePermission(adminPage, 'dashboard');
    await togglePermission(adminPage, 'settings');

    await adminPage.getByRole('textbox', { name: 'Name' }).fill(name);
    await adminPage.getByRole('textbox', { name: 'Description' }).fill('ajax submit regression');

    await clickSaveAndExpect(adminPage, 'Save changes', /Roles Created Successfully/i);

    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, name);

    await adminPage.locator('#app').locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');

    await instrumentFlashes(adminPage);
    await delaySaveResponse(adminPage);

    await togglePermission(adminPage, 'settings');

    const beforeUrl = adminPage.url();

    await saveRoleUpdateAndWait(adminPage);

    const errors = (await readFlashes(adminPage)).filter((flash) => flash.type === 'error');

    expect(errors, `unexpected error toast(s): ${JSON.stringify(errors)}`).toEqual([]);

    await expect.poll(() => adminPage.url(), { timeout: 30000 }).toBe(beforeUrl);

    await navigateTo(adminPage, 'roles');
    await searchInDataGrid(adminPage, name);

    const deleteBtn = adminPage.locator('#app').locator('span[title="Delete"]').first();

    await deleteBtn.click({ timeout: 10000 }).catch(() => {});
    await adminPage.getByRole('button', { name: 'Delete' }).click().catch(() => {});
  });
});
