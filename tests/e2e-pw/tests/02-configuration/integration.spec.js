const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Test cases', () => {
test('Create Integration with empty Name field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  await adminPage.getByRole('link', { name: 'Create' }).click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
  await adminPage.locator('input[name="admin_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
});

test('Create Integration field with empty Assign User field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  await adminPage.getByRole('link', { name: 'Create' }).click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Admin User');
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText('The Assign User field is required')).toBeVisible();
});

test('Create Integration field with empty Name and Assign User field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  await adminPage.getByRole('link', { name: 'Create' }).click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The Assign User field is required')).toBeVisible();
});

test('Create Integration field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  await adminPage.getByRole('link', { name: 'Create' }).click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Admin User');
  await adminPage.locator('input[name="admin_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText(/API Integration Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('should allow Integration search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('Admin');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('#app').locator('text=Admin User')).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
  await perPageBtn.click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(perPageBtn).toContainText('20');
});

test('should perform actions on a Integration (Edit, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Admin User' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/integrations\/api-keys\/edit/);
  await adminPage.goBack();
  await adminPage.waitForLoadState('networkidle');
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('#app').locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Generate API key', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Admin User' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('button', { name: 'Generate' }).click();
  await expect(adminPage.locator('#app').getByText(/API key is generated successfully/i)).toBeVisible({ timeout: 15000 });
  const clientIdInput = adminPage.locator('#client_id');
  await expect(clientIdInput).not.toHaveValue('');
  const secretkeyInput = adminPage.locator('#secret_key');
  await expect(secretkeyInput).not.toHaveValue('');
});

test('Regenerate API key', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Admin User' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('button', { name: 'Re-Generate Secret Key' }).click();
  await expect(adminPage.locator('#app').getByText(/API secret key is regenerated successfully/i)).toBeVisible({ timeout: 15000 });
  const clientIdInput = adminPage.locator('#client_id');
  await expect(clientIdInput).not.toHaveValue('');
  const secretkeyInput = adminPage.locator('#secret_key');
  await expect(secretkeyInput).not.toHaveValue('');
});

test('Update Integration', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Admin User' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Admin Testing');
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText(/API Integration is updated successfully/i)).toBeVisible({ timeout: 15000 });
});

test('Delete Integration', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Admin Testing' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/API Integration is deleted successfully/i)).toBeVisible({ timeout: 15000 });
});
});
