const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Delete ALL existing integrations so admin_id is free for new ones.
 * The admin_id column is unique, so only one integration per admin user is allowed.
 */
async function deleteAllIntegrations(adminPage) {
  await navigateTo(adminPage, 'integrations');
  for (let i = 0; i < 20; i++) {
    // Wait for page to stabilize before checking for delete buttons
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    const deleteBtn = adminPage.locator('span[title="Delete"]').first();
    if (!(await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false))) break;
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    // Wait for any toast/modal to disappear before next iteration
    await adminPage.waitForTimeout(500);
  }
}

/**
 * Helper: Create a new integration with a given name.
 * Assumes admin_id is available (call deleteAllIntegrations first).
 */
async function createIntegration(adminPage, name) {
  await navigateTo(adminPage, 'integrations');
  await adminPage.getByRole('link', { name: 'Create' }).click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('textbox', { name: 'Name' }).fill(name);
  await adminPage.locator('input[name="admin_id"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option').first().click();
  await clickSaveAndExpect(adminPage, 'Save', /API Integration is created successfully/i, /\/admin\/integrations\/api-keys\/edit\//);
}

/**
 * Helper: Delete an integration by searching for its name.
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
    await navigateTo(adminPage, 'integrations');
    await adminPage.getByRole('link', { name: 'Create' }).click();
    await adminPage.waitForLoadState('load');
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
    await adminPage.locator('input[name="admin_id"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
    await adminPage.getByRole('option').first().click();
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
  });

  test('Create Integration with empty Assign User field shows validation', async ({ adminPage }) => {
    await navigateTo(adminPage, 'integrations');
    await adminPage.getByRole('link', { name: 'Create' }).click();
    await adminPage.waitForLoadState('load');
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('Validation Test');
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.locator('#app').getByText('The Assign User field is required')).toBeVisible();
  });

  test('Create Integration with all empty fields shows validation', async ({ adminPage }) => {
    await navigateTo(adminPage, 'integrations');
    await adminPage.getByRole('link', { name: 'Create' }).click();
    await adminPage.waitForLoadState('load');
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Assign User field is required')).toBeVisible();
  });

  test('Create and delete an Integration successfully', async ({ adminPage }) => {
    const uid = generateUid();
    const name = `Integration ${uid}`;

    await deleteAllIntegrations(adminPage);
    await createIntegration(adminPage, name);
    await deleteIntegration(adminPage, name);
  });

  test('Search for an Integration in the datagrid', async ({ adminPage }) => {
    const uid = generateUid();
    const name = `Integration ${uid}`;

    await deleteAllIntegrations(adminPage);
    await createIntegration(adminPage, name);

    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    await expect(adminPage.locator('#app').locator(`text=${name}`)).toBeVisible();

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
    const uid = generateUid();
    const name = `Integration ${uid}`;

    await deleteAllIntegrations(adminPage);
    await createIntegration(adminPage, name);

    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    const row = adminPage.locator('div', { hasText: name });
    await row.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/integrations\/api-keys\/edit/);

    await deleteIntegration(adminPage, name);
  });

  test('Delete action shows confirmation dialog', async ({ adminPage }) => {
    const uid = generateUid();
    const name = `Integration ${uid}`;

    await deleteAllIntegrations(adminPage);
    await createIntegration(adminPage, name);

    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    const row = adminPage.locator('div', { hasText: name });
    await row.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('#app').locator('text=Are you sure you want to delete?')).toBeVisible();

    // Confirm delete for cleanup
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/API Integration is deleted successfully/i)).toBeVisible({ timeout: 20000 });
  });

  test('Generate API key for an Integration', async ({ adminPage }) => {
    const uid = generateUid();
    const name = `Integration ${uid}`;

    await deleteAllIntegrations(adminPage);
    await createIntegration(adminPage, name);

    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    const row = adminPage.locator('div', { hasText: name });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('load');
    await adminPage.getByRole('button', { name: 'Generate' }).click();
    await expect(adminPage.locator('#app').getByText(/API key is generated successfully/i)).toBeVisible({ timeout: 20000 });
    await expect(adminPage.locator('#client_id')).not.toHaveValue('');
    await expect(adminPage.locator('#secret_key')).not.toHaveValue('');

    await deleteIntegration(adminPage, name);
  });

  test('Regenerate API secret key for an Integration', async ({ adminPage }) => {
    const uid = generateUid();
    const name = `Integration ${uid}`;

    await deleteAllIntegrations(adminPage);
    await createIntegration(adminPage, name);

    // Generate key first
    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    const row = adminPage.locator('div', { hasText: name });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('load');
    await adminPage.getByRole('button', { name: 'Generate' }).click();
    await expect(adminPage.locator('#app').getByText(/API key is generated successfully/i)).toBeVisible({ timeout: 20000 });

    // Act: regenerate
    await adminPage.getByRole('button', { name: 'Re-Generate Secret Key' }).click();
    await expect(adminPage.locator('#app').getByText(/API secret key is regenerated successfully/i)).toBeVisible({ timeout: 20000 });
    await expect(adminPage.locator('#client_id')).not.toHaveValue('');
    await expect(adminPage.locator('#secret_key')).not.toHaveValue('');

    await deleteIntegration(adminPage, name);
  });

  test('Update Integration name', async ({ adminPage }) => {
    const uid = generateUid();
    const name = `Integration ${uid}`;
    const updatedName = `Integration Updated ${uid}`;

    await deleteAllIntegrations(adminPage);
    await createIntegration(adminPage, name);

    await navigateTo(adminPage, 'integrations');
    await searchInDataGrid(adminPage, name);
    const row = adminPage.locator('div', { hasText: name });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('load');
    await adminPage.getByRole('textbox', { name: 'Name' }).fill(updatedName);
    await clickSaveAndExpect(adminPage, 'Save', /API Integration is updated successfully/i);

    await deleteIntegration(adminPage, updatedName);
  });

});
