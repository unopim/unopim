const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Test cases(Administrator Role)', () => {
test('Create role with empty permission field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'Custom' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Admin');
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.getByText(/The Permissions field is required/i)).toBeVisible();
});

test('Create role with empty Name field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.getByText(/The Name field is required/i)).toBeVisible();
});

test('Create role with empty description field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Admin');
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.getByText(/The Description field is required/i)).toBeVisible();
});

test('Create role with all required field empty', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'Custom' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.getByText(/The Permissions field is required/i)).toBeVisible();
  await expect(adminPage.getByText(/The Name field is required/i)).toBeVisible();
  await expect(adminPage.getByText(/The Description field is required/i)).toBeVisible();
});

test('Create Administrator role', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Custom' }).click();
  await adminPage.getByRole('option', { name: 'All' }).locator('span').first().click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Admin');
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.getByText(/Roles Created Successfully/i)).toBeVisible();
});

test('should allow role search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('Administrator');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=AdministratorAll', {exact:true})).toBeVisible();
});

test('Update Administrator role', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Admin' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The admin have full access of UnoPim');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.getByText(/Roles is updated successfully./i).first()).toBeVisible();
});

test('Delete Administrator role', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Admin' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.getByText(/Roles is deleted successfully/i)).toBeVisible();
});
});

test.describe('UnoPim Test cases(Custom Role)', () => {
test('Create Custom Roles', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.locator('label').filter({ hasText: 'Dashboard' }).locator('span').click();
  await adminPage.locator('label').filter({ hasText: 'Catalog' }).locator('span').click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Catalog Manager');
  await adminPage.getByRole('textbox', { name: 'Description' }).click();
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('The catalog manager have access to the catalog section only');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.getByText(/Roles Created Successfully/i)).toBeVisible();
});

test('Update Custom Roles', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Catalog Manager' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('label').filter({ hasText: 'Categories' }).locator('span').click();
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.getByText(/Roles is updated successfully./i).first()).toBeVisible();
});

test('Delete Custom Roles', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Catalog Manager' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.getByText(/Roles is deleted successfully./i).first()).toBeVisible();
});

test('Delete Default Roles', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Administrator' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.getByText(/Role is already used by Example User/i).first()).toBeVisible();
});
});

