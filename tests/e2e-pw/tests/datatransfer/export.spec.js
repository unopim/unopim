import { test, expect } from '@playwright/test';
test.describe('UnoPim Export Jobs', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();

  });

  test('Create WooCommerce Product Export', async ({ page }) => {
    await page.getByRole('link', { name: ' Data Transfer' }).click();
    await page.getByRole('link', { name: 'Exports' }).click();
    await page.getByRole('link', { name: 'Create Export' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('WooCoomerce Product export');
    await page.locator('div').filter({ hasText: /^Categories$/ }).locator('span').click();
    await page.getByText('WooCommerce Product Export').click();
    await page.locator('input[name="filters[credential]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByText('http://192.168.15.238/').click();
    await page.locator('input[name="filters[channel]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="filters[locale]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByText('English (United States)').click();
    await page.locator('input[name="filters[currency]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Indian Rupee' }).locator('span').first().click();
    await page.locator('input[name="filters[productSKU]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByText('SAMS22-256-BLK').click();
    await page.getByRole('button', { name: 'Save Export' }).click();
    await expect(page.getByText(/Export created successfully/i)).toBeVisible();

  });


  test('Delete WooCommerce Product Export', async ({ page }) => {
    await page.getByRole('link', { name: ' Data Transfer' }).click();
    await page.getByRole('link', { name: 'Exports' }).click();
    const itemRow = await page.locator('div', { hasText: 'WooCommerce Product Export' });
    await itemRow.locator('span[title="Delete"]').first().click();        
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
  });



  test('Create WooCommerce Category Export', async ({ page }) => {
    await page.getByRole('link', { name: ' Data Transfer' }).click();
    await page.getByRole('link', { name: 'Exports' }).click();
    await page.getByRole('link', { name: 'Create Export' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('WooCommerce Catagary Export');
    await page.locator('div').filter({ hasText: /^Categories$/ }).locator('span').click();
    await page.getByRole('option', { name: 'WooCommerce Category Export' }).locator('span').first().click();
    await page.locator('input[name="filters[credential]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByText('http://192.168.15.238/').click();
    await page.locator('input[name="filters[channel]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="filters[locale]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
    await page.getByRole('button', { name: 'Save Export' }).click();
    await expect(page.getByText(/Export created successfully/i)).toBeVisible();

  });

  test('Delete WooCommerce Category Export', async ({ page }) => {
    await page.getByRole('link', { name: ' Data Transfer' }).click();
    await page.getByRole('link', { name: 'Exports' }).click();
    const itemRow = await page.locator('div', { hasText: 'WooCommerce Category Export' });
    await itemRow.locator('span[title="Delete"]').first().click();        
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
  });

  test('Create WooCommerce Attribute Export', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: ' Data Transfer' }).click();
    await page.getByRole('link', { name: 'Exports' }).click();
    await page.getByRole('link', { name: 'Create Export' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('WooCommerce Attribute Export');
    await page.locator('div').filter({ hasText: /^Categories$/ }).locator('span').click();
    await page.getByRole('option', { name: 'WooCommerce Attribute Export' }).locator('span').first().click();
    await page.locator('input[name="filters[credential]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'http://192.168.15.238/' }).locator('span').first().click();
    await page.locator('input[name="filters[channel]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="filters[locale]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
    await page.locator('input[name="filters[attributes]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Color' }).locator('span').first().click();
    await page.locator('div').filter({ hasText: /^Color$/ }).first().click();
    await page.getByRole('option', { name: 'Size' }).locator('span').first().click();
    await page.locator('div').filter({ hasText: /^ColorSize$/ }).first().click();
    await page.getByRole('option', { name: 'Brand' }).locator('span').first().click();
    await page.getByRole('button', { name: 'Save Export' }).click();
    await expect(page.getByText(/Export created successfully/i)).toBeVisible();
  });

  test('Delete WooCommerce Attribute Export', async ({ page }) => {
    await page.getByRole('link', { name: ' Data Transfer' }).click();
    await page.getByRole('link', { name: 'Exports' }).click();
    const itemRow = await page.locator('div', { hasText: 'WooCommerce Atribute Export' });
    await itemRow.locator('span[title="Delete"]').first().click();        
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Export deleted successfully/i)).toBeVisible();
  });
});