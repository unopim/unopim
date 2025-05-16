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
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = await page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('#product_number').click();
    await page.locator('#product_number').fill('456');
    await page.locator('#name').click();
    await page.locator('#name').fill('Acer Laptop');
    await page.locator('#url_key').click();
    await page.locator('#url_key').fill('laptop');
    await page.locator('#color div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Black' }).locator('span').first().click();
    await page.locator('#brand div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Redmi' }).locator('span').first().click();
    await page.locator('#quantity').click();
    await page.locator('#quantity').fill('500');
    const shortDescFrame = await page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body p').click();
    await shortDescFrame.locator('body').fill('This laptop is best');

    // Fill main description
    const mainDescFrame = await page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body p').click();
    await mainDescFrame.locator('body').fill('This is the ACER Laptop');
    await page.locator('#meta_title').click();
    await page.locator('#meta_title').fill('thakubali');
    await page.locator('#price').click();
    await page.locator('#price').fill('20000');
    await page.locator('label:has-text("Add Image")').click();

    // Wait for the file input to appear and set the file
    const fileInput = await page.locator('input[type="file"]');
    await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/download.jpeg');
    await page.getByRole('button', { name: 'Save Product' }).click();
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
    await page.locator('#name').click();
    await page.locator('#name').fill('Realme 7pro');
    await page.locator('#product_number').click();
    await page.locator('#product_number').fill('7pro');
    const shortDescFrame = await page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body p').click();
    await shortDescFrame.locator('body').fill('This smart phone is best');

    // Fill main description
    const mainDescFrame = await page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body p').click();
    await mainDescFrame.locator('body').fill('This is Realme 7pro smart phone');
    await page.locator('#meta_title').click();
    await page.locator('#meta_title').fill('best mobile');
    await page.locator('#price').click();
    await page.locator('#price').fill('20000');
    await page.locator('label:has-text("Add Image")').click();

    // Wait for the file input to appear and set the file
    const fileInput = await page.locator('input[type="file"]');
    await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/realme.jpeg');
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