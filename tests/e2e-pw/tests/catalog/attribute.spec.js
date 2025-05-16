import { test, expect } from '@playwright/test';
test.describe('UnoPim Attribute', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();

  });
  test('Create attribute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    await page.getByRole('link', { name: 'Create Attribute' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('product_name');
    await page.locator('div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Text' }).locator('span').first().click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
    await page.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(page.getByText(/Attribute Created Successfully/i)).toBeVisible();
  });


  test('Update attribute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    await page.getByText('product_nameProduct Name').getByTitle('Edit').click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('prudact nem');
    await page.locator('#is_required').nth(1).click();
    await page.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(page.getByText(/Attribute Updated Successfully/i)).toBeVisible();
  });

  test('Delete Attribute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    await page.getByText('product_nameprudact nem').getByTitle('Delete').click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Attribute Deleted Successfully/i)).toBeVisible();
  });
});