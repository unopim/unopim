const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

// Reaches the role without a full page load — a page.goto() would drop the
// compiled-template cache and hide the bug this covers.
async function openRoleFromGrid(adminPage, name) {
  await searchInDataGrid(adminPage, name);
  await adminPage.locator('#app').locator('span[title="Edit"]').first().click();
  await adminPage.waitForURL(/\/roles\/edit\/\d+$/, { timeout: 20000 });
  await adminPage.locator('#name').waitFor({ state: 'visible', timeout: 20000 });
}

async function backTolisting(adminPage) {
  await adminPage.getByRole('link', { name: /Back/i }).first().click();
  await adminPage.waitForURL(/\/settings\/roles$/, { timeout: 20000 });
}

async function createCustomRole(adminPage, name, permission) {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.waitForLoadState('networkidle');

  const box = adminPage.locator(`input[type="checkbox"][name="permissions[]"][value="${permission}"]`);
  await box.waitFor({ state: 'attached', timeout: 15000 });
  await adminPage.locator(`label[for="${await box.getAttribute('id')}"] span`).first().click();

  await adminPage.getByRole('textbox', { name: 'Name' }).fill(name);
  await adminPage.getByRole('textbox', { name: 'Description' }).fill(`${name} description`);

  await clickSaveAndExpect(adminPage, 'Save changes', /Roles Created Successfully/i);
}

async function deleteRole(adminPage, name) {
  await navigateTo(adminPage, 'roles');
  await searchInDataGrid(adminPage, name);

  const deleteBtn = adminPage.locator('#app').locator('span[title="Delete"]').first();

  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click().catch(() => {});
    await adminPage.waitForLoadState('networkidle');
  }
}

test.describe('Role edit loads its own record', () => {
  test('opening a second role after a first shows the second role data', async ({ adminPage }) => {
    const uid = generateUid();
    const first = `AAA ${uid}`;
    const second = `ZZZ ${uid}`;

    await createCustomRole(adminPage, first, 'dashboard');
    await createCustomRole(adminPage, second, 'catalog');

    await navigateTo(adminPage, 'roles');

    await openRoleFromGrid(adminPage, first);
    await expect(adminPage.locator('#name')).toHaveValue(first);

    await backTolisting(adminPage);

    await openRoleFromGrid(adminPage, second);

    await expect(adminPage.locator('#name')).toHaveValue(second);
    await expect(adminPage.locator('#description')).toHaveValue(`${second} description`);

    await deleteRole(adminPage, first);
    await deleteRole(adminPage, second);
  });
});
