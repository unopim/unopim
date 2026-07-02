const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

async function createRoleImport(adminPage, code, filePath = 'assets/roles.csv') {
  await navigateTo(adminPage, 'imports');
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  
  await adminPage.locator('#import-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
  await adminPage.getByRole('option', { name: 'Roles' }).locator('span').first().click();

  const fileInput = adminPage.locator('input[type="file"]').first();
  await fileInput.setInputFiles(filePath);
  
  await clickSaveAndExpect(adminPage, 'Save Import', /Import created successfully/i);
}

async function deleteImport(adminPage, code) {
  await navigateTo(adminPage, 'imports');
  await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const deleteBtn = adminPage.locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible({ timeout: 20000 });
  }
}

test.describe('Role Import Jobs', () => {
  test('Create Role Import and delete it', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `role-imp-${uid}`;
    await createRoleImport(adminPage, code);

    await deleteImport(adminPage, code);
  });

  test('Create Role Import and run Import Now', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `role-run-${uid}`;
    await createRoleImport(adminPage, code);

    await adminPage.getByRole('button', { name: 'Import Now' }).click();
    await expect(adminPage.locator('#app').getByText('Job queued')).toBeVisible();

    await deleteImport(adminPage, code);
  });
});
