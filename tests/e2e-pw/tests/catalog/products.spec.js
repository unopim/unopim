import { test, expect } from '@playwright/test';
test.describe('UnoPim Test cases', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();

  });

  test('Create Simple Product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('div').filter({ hasText: /^Select option$/ }).first().click();
    await page.getByRole('option', { name: 'Simple Press enter to select' }).locator('span').first().click();
    await page.locator('div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="sku"]').click();
    await page.locator('input[name="sku"]').fill('acer456');
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText(/Product created successfully/i)).toBeVisible();
  });

  test('Update simple product', async ({ page }) => {
    // Navigate to Catalog
    await page.getByRole('link', { name: ' Catalog' }).click();
  
    // Select the product row by name
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
  
    // Basic field updates
    await page.locator('#product_number').click();
    await page.locator('#product_number').type('456', { delay: 100 });
    
    await page.locator('#name').click();
    await page.locator('#name').type('Acer Laptop', { delay: 100 });
    
    await page.locator('#url_key').click();
    await page.locator('#url_key').type('laptop', { delay: 100 });
  
    // Select color
    await page.locator('#color div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Black' }).locator('span').first().click();
  
    // Fill short description (iframe)
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This laptop is best in the market', { delay: 100 });
    
    // Fill main description (iframe)
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality', { delay: 100 });
  
    // Meta title and price
    await page.locator('#meta_title').fill('thakubali');
    await page.locator('#price').fill('40000');
  
    // Save product
    await page.locator('.relative > .rounded-full').click();
    await page.getByRole('button', { name: 'Save Product' }).click();
  
    // Assertion for success message
    await expect(page.getByText(/Product updated successfully/i)).toBeVisible();
  });
  

  test('Delete simple product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = await page.locator('div', { hasText: 'acer456' });
    // Click the Edit button inside that container
    await itemRow.locator('span[title="Delete"]').first().click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Product deleted successfully/i)).toBeVisible();

  });

  test('Create Configurable Product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('div').filter({ hasText: /^Select option$/ }).first().click();
    await page.getByRole('option', { name: 'Configurable' }).locator('span').first().click();
    await page.locator('div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="sku"]').click();
    await page.locator('input[name="sku"]').fill('realme1245');
    await page.getByRole('button', { name: 'Save Product' }).click();
    await page.getByRole('paragraph').filter({ hasText: 'Brand' }).locator('span').click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText(/Product created successfully/i)).toBeVisible();
  });

  test('Update Configurable Product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = await page.locator('div', { hasText: 'realme1245' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('#product_number').click();
    await page.locator('#product_number').type('12345', { delay: 100 });
    
    await page.locator('#name').click();
    await page.locator('#name').type('Realme 7pro', { delay: 100 });
    
    await page.locator('#url_key').click();
    await page.locator('#url_key').type('Mobile', { delay: 100 });
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This smart phone is best in the market', { delay: 100 });
    
    // Fill main description (iframe)
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the Realme 7pro phone with 7500mah batttery and 200mp camera', { delay: 100 });
    await page.locator('#meta_title').click();
    await page.locator('#meta_title').fill('best mobile');
    await page.locator('#price').click();
    await page.locator('#price').fill('25000');

    await page.locator('.relative > .rounded-full').click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText(/Product updated successfully/i)).toBeVisible();
  });

  test('Delete configurable product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = await page.locator('div', { hasText: 'realme1245' });
    // Click the Edit button inside that container
    await itemRow.locator('span[title="Delete"]').first().click();
    await page.click('button:has-text("Delete")');
    await expect(page.getByText(/Product deleted successfully/i)).toBeVisible();
  });
});