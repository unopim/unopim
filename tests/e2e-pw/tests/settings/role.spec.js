import { test, expect } from '@playwright/test';
test.describe('UnoPim Test cases(Administrator Role)', () => {
test.beforeEach(async ({ page }) => {
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
});

test('Create role with empty permission field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  await page.getByRole('link', { name: 'Create Role' }).click();
  await page.locator('div').filter({ hasText: /^Custom$/ }).click();
  await page.getByRole('option', { name: 'Custom' }).locator('span').first().click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Admin');
  await page.getByRole('textbox', { name: 'Description' }).click();
  await page.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await page.getByRole('button', { name: 'Save Role' }).click();
  await expect(page.getByText(/The Permissions field is required/i)).toBeVisible();
});

test('Create role with empty Name field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  await page.getByRole('link', { name: 'Create Role' }).click();
  await page.locator('div').filter({ hasText: /^Custom$/ }).click();
  await page.getByRole('option', { name: 'All' }).locator('span').first().click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('');
  await page.getByRole('textbox', { name: 'Description' }).click();
  await page.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await page.getByRole('button', { name: 'Save Role' }).click();
  await expect(page.getByText(/The Name field is required/i)).toBeVisible();
});

test('Create role with empty description field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  await page.getByRole('link', { name: 'Create Role' }).click();
  await page.locator('div').filter({ hasText: /^Custom$/ }).click();
  await page.getByRole('option', { name: 'All' }).locator('span').first().click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Admin');
  await page.getByRole('textbox', { name: 'Description' }).click();
  await page.getByRole('textbox', { name: 'Description' }).fill('');
  await page.getByRole('button', { name: 'Save Role' }).click();
  await expect(page.getByText(/The Description field is required/i)).toBeVisible();
});

test('Create role with all required field empty', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  await page.getByRole('link', { name: 'Create Role' }).click();
  await page.locator('div').filter({ hasText: /^Custom$/ }).click();
  await page.getByRole('option', { name: 'Custom' }).locator('span').first().click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('');
  await page.getByRole('textbox', { name: 'Description' }).click();
  await page.getByRole('textbox', { name: 'Description' }).fill('');
  await page.getByRole('button', { name: 'Save Role' }).click();
  await expect(page.getByText(/The Permissions field is required/i)).toBeVisible();
  await expect(page.getByText(/The Name field is required/i)).toBeVisible();
  await expect(page.getByText(/The Description field is required/i)).toBeVisible();
});

test('Create Administrator role', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  await page.getByRole('link', { name: 'Create Role' }).click();
  await page.locator('div').filter({ hasText: /^Custom$/ }).click();
  await page.getByRole('option', { name: 'All' }).locator('span').first().click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Admin');
  await page.getByRole('textbox', { name: 'Description' }).click();
  await page.getByRole('textbox', { name: 'Description' }).fill('The admin have full access');
  await page.getByRole('button', { name: 'Save Role' }).click();
  await expect(page.getByText(/Roles Created Successfully/i)).toBeVisible();
});

test('should allow Export search', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('Administrator');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=AdministratorAll')).toBeVisible();
});

test('Update Administrator role', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  const itemRow = page.locator('div', { hasText: 'Admin' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await page.getByRole('textbox', { name: 'Description' }).click();
  await page.getByRole('textbox', { name: 'Description' }).fill('The admin have full access of UnoPim');
  await page.getByRole('button', { name: 'Save Role' }).click();
  await expect(page.getByText(/Roles is updated successfully./i).first()).toBeVisible();
});

test('Delete Administrator role', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  const itemRow = page.locator('div', { hasText: 'Admin' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.click('button:has-text("Delete")');
  await expect(page.getByText(/Roles is deleted successfully/i)).toBeVisible();
});
});

test.describe('UnoPim Test cases(Custom Role)', () => {
test.beforeEach(async ({ page }) => {
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
});

test('Create Custom Roles', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  await page.getByRole('link', { name: 'Create Role' }).click();
  await page.locator('label').filter({ hasText: 'Dashboard' }).locator('span').click();
  await page.locator('label').filter({ hasText: 'Catalog' }).locator('span').click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Catalog Manager');
  await page.getByRole('textbox', { name: 'Description' }).click();
  await page.getByRole('textbox', { name: 'Description' }).fill('The catalog manager have access to the catalog section only');
  await page.getByRole('button', { name: 'Save Role' }).click();
  await expect(page.getByText(/Roles Created Successfully/i)).toBeVisible();
});

test('Update Custom Roles', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  const itemRow = page.locator('div', { hasText: 'Catalog Manager' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await page.locator('label').filter({ hasText: 'Categories' }).locator('span').click();
  await page.getByRole('button', { name: 'Save Role' }).click();
  await expect(page.getByText(/Roles is updated successfully./i).first()).toBeVisible();
});

test('Delete Custom Roles', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  const itemRow = page.locator('div', { hasText: 'Catalog Manager' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.click('button:has-text("Delete")');
  await expect(page.getByText(/Roles is deleted successfully./i).first()).toBeVisible();
});

test('Delete Default Roles', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  const itemRow = page.locator('div', { hasText: 'Administrator' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.click('button:has-text("Delete")');
  await expect(page.getByText(/Role is already used by Example User/i).first()).toBeVisible();
});
});

