const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

const uid = generateUid();

test.describe.serial('UnoPim Test cases(Administrator Role)', () => {
test('Create role with empty permission field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'Custom' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill(`${uid} Admin`);
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.locator('#app').getByText(/The Permissions field is required/i)).toBeVisible();
});

test('Create role with empty Name field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.locator('#app').getByText(/The Name field is required/i)).toBeVisible();
});

test('Create role with empty description field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill(`${uid} Admin`);
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.locator('#app').getByText(/The Description field is required/i)).toBeVisible();
});

test('Create role with all required field empty', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'Custom' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.locator('#app').getByText(/The Permissions field is required/i)).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/The Name field is required/i)).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/The Description field is required/i)).toBeVisible();
});

test('Create Administrator role', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill(`${uid} Admin`);
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.locator('#app').getByText(/Roles Created Successfully/i)).toBeVisible();
});

test('should allow role search', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('Administrator');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=AdministratorAll', {exact:true})).toBeVisible();
});

test('Update Administrator role', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  const itemRow = adminPage.locator('div', { hasText: `${uid} Admin` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The admin have full access of UnoPim');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.locator('#app').getByText(/Roles is updated successfully./i)).toBeVisible();
});

test('Delete Administrator role', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  const itemRow = adminPage.locator('div', { hasText: `${uid} Admin` });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.locator('#app').getByText(/Roles is deleted successfully/i)).toBeVisible();
});
});

test.describe.serial('UnoPim Test cases(Custom Role)', () => {
test('Create Custom Roles', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.waitForLoadState('networkidle');
  const dashboardLabel = adminPage.locator('label').filter({ hasText: 'Dashboard' }).locator('span').first();
  await dashboardLabel.waitFor({ state: 'visible', timeout: 10000 });
  await dashboardLabel.click();
  await adminPage.locator('label').filter({ hasText: 'Catalog' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill(`${uid} Catalog Manager`);
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The catalog manager have access to the catalog section only');
  const saveResponse = adminPage.waitForResponse(resp => resp.url().includes('/roles') && resp.request().method() === 'POST');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await saveResponse;
  await expect(adminPage.locator('#app').getByText(/Roles Created Successfully/i)).toBeVisible();
});

test('Update Custom Roles', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  const itemRow = adminPage.locator('div', { hasText: `${uid} Catalog Manager` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('networkidle');
  const categoriesLabel = adminPage.locator('label').filter({ hasText: 'Categories' }).locator('span').first();
  await categoriesLabel.waitFor({ state: 'visible', timeout: 10000 });
  await categoriesLabel.click();
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.locator('#app').getByText(/Roles is updated successfully./i)).toBeVisible();
});

test('Delete Custom Roles', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  const itemRow = adminPage.locator('div', { hasText: `${uid} Catalog Manager` });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.locator('#app').getByText(/Roles is deleted successfully./i)).toBeVisible();
});

test('Delete Default Roles', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  const itemRow = adminPage.locator('div', { hasText: 'Administrator' });
  await itemRow.locator('span[title="Delete"]').first().click();
  const deleteResponse = adminPage.waitForResponse(resp => resp.url().includes('/roles/') && resp.request().method() === 'DELETE');
  await adminPage.click('button:has-text("Delete")');
  await deleteResponse;
  await expect(adminPage.locator('#app').getByText(/Role is already used by Example User/i)).toBeVisible();
});
});
