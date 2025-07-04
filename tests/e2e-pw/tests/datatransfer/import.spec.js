import { test, expect } from '@playwright/test';
test.describe('UnoPim Import Jobs', () => {
test.beforeEach(async ({ page }) => {
   await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
});

test('Create Import with empty Code field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Products' }).locator('span').first().click();
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
});

test('Create Import with empty Type field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The Type field is required')).toBeVisible();
});

test('Create Import with empty File field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Products' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The File field is required')).toBeVisible();
});

test('Create Import with empty Code, Type and File field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
  await expect(page.getByText('The Type field is required')).toBeVisible();
});

test('Create Import with empty Action field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.locator('div').filter({ hasText: /^Create\/Update$/ }).click();
  await page.getByRole('option', { name: 'Create/Update' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The Action field is required')).toBeVisible();
});

test('Create Import with empty Validation Strategy field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.locator('div').filter({ hasText: /^Stop on Errors$/ }).click();
  await page.getByText('Stop on Errors').click();
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The Validation Strategy field is required')).toBeVisible();
});

test('Create Import with empty Allowed Errors field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.getByRole('textbox', { name: 'Allowed Errors' }).click();
  await page.getByRole('textbox', { name: 'Allowed Errors' }).fill('');
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The Allowed Errors field is required')).toBeVisible();
});

test('Create Import with empty Field Separator field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.getByRole('textbox', { name: 'Field Separator' }).click();
  await page.getByRole('textbox', { name: 'Field Separator' }).fill('');
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The Field Separator field is required')).toBeVisible();
});

test('Create Import with all required field empty', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Create\/Update$/ }).click();
  await page.getByRole('option', { name: 'Create/Update' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Stop on Errors$/ }).click();
  await page.getByText('Stop on Errors').click();
  await page.getByRole('textbox', { name: 'Allowed Errors' }).click();
  await page.getByRole('textbox', { name: 'Allowed Errors' }).fill('');
  await page.getByRole('textbox', { name: 'Field Separator' }).click();
  await page.getByRole('textbox', { name: 'Field Separator' }).fill('');
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
  await expect(page.getByText('The Type field is required')).toBeVisible();
  await expect(page.getByText('The Action field is required')).toBeVisible();
  await expect(page.getByText('The Validation Strategy field is required')).toBeVisible();
  await expect(page.getByText('The Allowed Errors field is required')).toBeVisible();
  await expect(page.getByText('The Field Separator field is required')).toBeVisible();
});

test('Create Product Import', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Products' }).locator('span').first().click();
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText(/Import created successfully/i)).toBeVisible();
  await expect(page.getByRole('button', { name: 'Import Now' })).toBeVisible();
  await page.getByRole('button', { name: 'Import Now' }).click();
  await expect(page.getByText('Awaiting Job Processing in')).toBeVisible();
});

test('Create Import with same Code', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Products' }).locator('span').first().click();
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText('The code has already been taken.')).toBeVisible();
});

test('should allow Export search', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('product Import');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=product Import')).toBeVisible();
});

test('should open the filter menu when clicked', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByText('Filter', { exact: true }).click();
  await expect(page.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per page', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('button', { name: '' }).click();
  await page.getByText('20', { exact: true }).click();
  await expect(page.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a Export job (Edit, Delete)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  const itemRow = page.locator('div', { hasText: 'product Import' });
  await itemRow.locator('span[title="Import"]').first().click();
  await expect(page).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/import/);
  await page.goBack();
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(page).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/edit/);
  await page.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Delete Product Import', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  const itemRow = await page.locator('div', { hasText: 'Product Import' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Import deleted successfully/i)).toBeVisible();
});

test('Create Category Import', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await page.getByRole('link', { name: 'Create Import' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Category Import');
  const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1k_products.xlsx');
  await page.getByRole('button', { name: 'Save Import' }).click();
  await expect(page.getByText(/Import created successfully/i)).toBeVisible();
});

test('Delete Category Import', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  const itemRow = await page.locator('div', { hasText: 'Category Import' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Import deleted successfully/i)).toBeVisible();
});
});

