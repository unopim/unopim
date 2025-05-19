import { test, expect } from '@playwright/test';
test.describe('UnoPim Category', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();

  });
  
  test('Create Categories', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Categories' }).click();
    await page.getByRole('link', { name: 'Create Category' }).click();
    await page.locator('input[name="code"]').click();
    await page.locator('input[name="code"]').fill('television');
    
    // Fill 'name' input
    await page.locator('#name').click();
    await page.locator('#name').type('Television', { delay: 100 });
    
    // Fill description (Rich Text Area in iframe)
    const descriptionFrame = page.frameLocator('#description_ifr');
    await descriptionFrame.locator('body').click();
    await descriptionFrame.locator('body').type('This is television category.', { delay: 100 });
    await page.getByRole('button', { name: 'Save Category' }).click();
    await expect(page.getByText(/Category created successfully/i)).toBeVisible();
  });

  test('Update Categories', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Categories' }).click();
    await page.getByText('televisionTelevision').getByTitle('Edit').click();
    await page.locator('#name').click();
    await page.locator('#name').fill('LG Television');
    await page.getByRole('button', { name: 'Save Category' }).click();
    await expect(page.getByText(/Category updated successfully/i)).toBeVisible();
  });

  test('Delete Category', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Categories' }).click();
    // await page.getByTitle('Delete').nth(1).click();
    await page.getByText('televisionLG Television').getByTitle('Delete').click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/The category has been successfully deleted/i)).toBeVisible();
  });
});