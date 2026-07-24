const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Open the index create modal (a header button, not a link) and wait for it to render.
 */
async function openCreateModal(adminPage) {
  await navigateTo(adminPage, 'integrations');
  await adminPage.getByRole('button', { name: 'Create', exact: true }).click();
  await adminPage.locator('input[name="name"]').waitFor({ state: 'visible', timeout: 15000 });
}

/**
 * Pick an option in the create modal's permission-type vue-multiselect by visible label.
 */
async function selectPermissionType(adminPage, label) {
  const wrapper = adminPage.locator('input[name="permission_type"]')
    .locator('xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " multiselect ")][1]');
  await wrapper.locator('.multiselect__tags').click();
  await wrapper.locator('.multiselect__option', { hasText: label }).first().click();
}

/**
 * Create an integration (name + permission type) via the modal. Lands on the edit page.
 */
async function createIntegration(adminPage, name) {
  await openCreateModal(adminPage);
  await adminPage.locator('input[name="name"]').fill(name);
  await selectPermissionType(adminPage, 'Custom');
  await clickSaveAndExpect(adminPage, 'Save', /API Integration Created Successfully/i, /\/admin\/configuration\/integrations\/edit\//);
}

/**
 * Delete an integration by searching for its name in the datagrid.
 */
async function deleteIntegration(adminPage, name) {
  await navigateTo(adminPage, 'integrations');
  await searchInDataGrid(adminPage, name);
  const row = adminPage.locator('div', { hasText: name });
  await row.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/API Integration is deleted successfully/i)).toBeVisible({ timeout: 20000 });
}

test.describe('UnoPim Integration API Keys', () => {

  test('Create Integration with empty Name field shows validation', async ({ adminPage }) => {
    await openCreateModal(adminPage);
    await selectPermissionType(adminPage, 'Custom');
    await clickSave(adminPage, 'Save');
    await expect(adminPage.locator('#app').getByText(/The Name field is required/i)).toBeVisible();
  });

  test('Create Integration with empty Permission field shows validation', async ({ adminPage }) => {
    await openCreateModal(adminPage);
    await adminPage.locator('input[name="name"]').fill('Validation Test');
    await clickSave(adminPage, 'Save');
    await expect(adminPage.locator('#app').getByText(/The Permissions field is required/i)).toBeVisible();
  });

  test('Create Integration with all empty fields shows validation', async ({ adminPage }) => {
    await openCreateModal(adminPage);
    await clickSave(adminPage, 'Save');
    await expect(adminPage.locator('#app').getByText(/The Name field is required/i)).toBeVisible();
    await expect(adminPage.locator('#app').getByText(/The Permissions field is required/i)).toBeVisible();
  });

  test('Create and delete an Integration successfully', async ({ adminPage }) => {
    const name = `Integration ${generateUid()}`;

    await createIntegration(adminPage, name);
    await deleteIntegration(adminPage, name);
  });

  test('Search for an Integration in the datagrid', async ({ adminPage }) => {
    const name = `Integration ${generateUid()}`;

    await createIntegration(adminPage, name);

    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    await expect(adminPage.locator('#app').locator(`text=${name}`).first()).toBeVisible();

    await deleteIntegration(adminPage, name);
  });

  test('Filter menu opens when clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'integrations');
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.getByText('Apply Filters')).toBeVisible();
  });

  test('Items per page can be changed', async ({ adminPage }) => {
    await navigateTo(adminPage, 'integrations');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
    await adminPage.locator('#app').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('Edit action navigates to edit page', async ({ adminPage }) => {
    const name = `Integration ${generateUid()}`;

    await createIntegration(adminPage, name);

    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    const row = adminPage.locator('div', { hasText: name });
    await row.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/configuration\/integrations\/edit/);

    await deleteIntegration(adminPage, name);
  });

  test('Delete action shows confirmation dialog', async ({ adminPage }) => {
    const name = `Integration ${generateUid()}`;

    await createIntegration(adminPage, name);

    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    const row = adminPage.locator('div', { hasText: name });
    await row.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('#app').locator('text=Are you sure you want to delete?')).toBeVisible();

    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/API Integration is deleted successfully/i)).toBeVisible({ timeout: 20000 });
  });

  test('Generate API key for an Integration', async ({ adminPage }) => {
    const name = `Integration ${generateUid()}`;

    await createIntegration(adminPage, name);

    await adminPage.getByRole('button', { name: 'Generate', exact: true }).click();
    await expect(adminPage.locator('#app').getByText(/API key is generated successfully/i)).toBeVisible({ timeout: 20000 });
    // Post-generate the OAuth credentials render as read-only text rows, not inputs.
    await expect(adminPage.getByTitle('Re-Generate Secret Key')).toBeVisible({ timeout: 10000 });

    await deleteIntegration(adminPage, name);
  });

  test('Regenerate API secret key for an Integration', async ({ adminPage }) => {
    const name = `Integration ${generateUid()}`;

    await createIntegration(adminPage, name);

    await adminPage.getByRole('button', { name: 'Generate', exact: true }).click();
    await expect(adminPage.locator('#app').getByText(/API key is generated successfully/i)).toBeVisible({ timeout: 20000 });

    await adminPage.getByTitle('Re-Generate Secret Key').click();
    await expect(adminPage.locator('#app').getByText(/API secret key is regenerated successfully/i)).toBeVisible({ timeout: 20000 });

    await deleteIntegration(adminPage, name);
  });

  test('Update Integration name', async ({ adminPage }) => {
    const uid = generateUid();
    const name = `Integration ${uid}`;
    const updatedName = `Integration Updated ${uid}`;

    await createIntegration(adminPage, name);

    // The edit form is dirty-tracked, so the real save is the "Save changes" bar.
    await adminPage.waitForTimeout(1000);
    await adminPage.locator('input[name="name"]').fill(updatedName);
    await clickSaveAndExpect(adminPage, 'Save changes', /API Integration is updated successfully/i);

    await deleteIntegration(adminPage, updatedName);
  });

});
