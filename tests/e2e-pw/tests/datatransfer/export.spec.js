import { test, expect } from '@playwright/test';
test.describe('UnoPim Export Jobs', () => {
test.beforeEach(async ({ page }) => {
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
});

test('Create Export with empty Code field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
});

test('Create Export with empty Type field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Category Export CSV');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText('The Type field is required')).toBeVisible();
});

test('Create Export with empty File Format field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Category Export CSV');
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText('The File Format field is required')).toBeVisible();
});

test('Create Export with empty Code, Type and File Format field', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('div').filter({ hasText: /^Categories$/ }).click();
  await page.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
  await expect(page.getByText('The Type field is required')).toBeVisible();
  await expect(page.getByText('The File Format field is required')).toBeVisible();
});

test('Create Category Export (CSV)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Category Export CSV');
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText(/Export created successfully/i)).toBeVisible();
});

test('Create Export with same Code', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Category Export CSV');
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText('The Code has already been taken.')).toBeVisible();
});

test('should allow Export search', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('Category Export CSV');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=Category Export CSV')).toBeVisible();
});

test('should open the filter menu when clicked', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByText('Filter', { exact: true }).click();
  await expect(page.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per page', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('button', { name: '' }).click();
  await page.getByText('20', { exact: true }).click();
  await expect(page.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a Export job (Edit, Delete)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  const itemRow = page.locator('div', { hasText: 'Category Export CSV' });
  await itemRow.locator('span[title="Export"]').first().click();
  await expect(page).toHaveURL(/\/admin\/settings\/data-transfer\/exports\/export/);
  await page.goBack();
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(page).toHaveURL(/\/admin\/settings\/data-transfer\/exports\/edit/);
  await page.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Delete Category Export CSV', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  const itemRow = await page.locator('div', { hasText: 'Category Export CSV' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Category Export (XLS)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Category Export XLS');
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'XLS' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText(/Export created successfully/i)).toBeVisible();
  await expect(page.getByRole('button', { name: 'Export Now' })).toBeVisible();
  await page.getByRole('button', { name: 'Export Now' }).click();
  await expect(page.getByText('Awaiting Job Processing in')).toBeVisible();
});

test('Delete Category Export (XLS)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  const itemRow = await page.locator('div', { hasText: 'Category Export XLS' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Category Export (XLSX)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Category Export XLSX');
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'XLSX' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText(/Export created successfully/i)).toBeVisible();
});

test('Delete Category Export (XLSX)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  const itemRow = await page.locator('div', { hasText: 'Category Export XLSX' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Product Export (CSV)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Product Export CSV');
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText(/Export created successfully/i)).toBeVisible();
});

test('Delete Product Export CSV', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  const itemRow = await page.locator('div', { hasText: 'Product Export CSV' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Product Export (XLS)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Product Export XLS');
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'XLS' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText(/Export created successfully/i)).toBeVisible();
});

test('Delete Product Export (XLS)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  const itemRow = await page.locator('div', { hasText: 'Product Export XLS' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Product Export (XLSX)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await page.getByRole('link', { name: 'Create Export' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('Product Export XLSX');
  await page.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'XLSX' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await page.getByRole('button', { name: 'Save Export' }).click();
  await expect(page.getByText(/Export created successfully/i)).toBeVisible();
});

test('Delete Product Export (XLSX)', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  const itemRow = await page.locator('div', { hasText: 'Product Export XLSX' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
});
});

