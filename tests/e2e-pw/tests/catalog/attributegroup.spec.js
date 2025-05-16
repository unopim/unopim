import { test, expect } from '@playwright/test';
test.describe('UnoPim Test cases', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();

  });
  test('Create Attribute Group', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
    await page.getByRole('link', { name: 'Create Attribute Group' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('product_description');
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Product Description');
    await page.getByRole('button', { name: 'Save Attribute Group' }).click();
    await expect(page.getByText(/Attribute Group Created Successfully/i)).toBeVisible();
  });

  test('Update attribute group', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
    //await page.getByTitle('Edit').first().click();
    await page.getByText('product_descriptionProduct Description').getByTitle('Edit').click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('prudact discripsan');
    await page.getByRole('button', { name: 'Save Attribute Group' }).click();
    await expect(page.getByText(/Attribute Group Updated Successfully/i)).toBeVisible();
  });

  test('Delete Attribute Group', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
    await page.getByText('product_descriptionprudact discripsan').getByTitle('Delete').click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Attribute Group Deleted Successfully/i)).toBeVisible();
  });
});