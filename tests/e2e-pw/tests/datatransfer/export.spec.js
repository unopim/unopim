import { test, expect } from '@playwright/test';
test.describe('UnoPim Export Jobs', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();

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