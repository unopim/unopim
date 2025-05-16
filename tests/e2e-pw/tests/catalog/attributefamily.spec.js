import { test, expect } from '@playwright/test';
test.describe('Attribute Family', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
  });

  test('Create Attribute family', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Families' }).click();
    await page.getByRole('link', { name: 'Create Attribute Family' }).click();
    await page.getByRole('textbox', { name: 'Enter Code' }).click();
    await page.getByRole('textbox', { name: 'Enter Code' }).fill('header');
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Header');
    await page.getByRole('button', { name: 'Save Attribute Family' }).click();
    await expect(page.getByText(/Family created successfully/i)).toBeVisible();

  });

  test('Edit Attribute Family', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Families' }).click();
    // await page.getByTitle('Edit').first().click();
    await page.getByText('headerHeader').getByTitle('Edit').click();
    // await page.locator('div').filter({ hasText: /^header$/ }).locator('span').first().click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Footer');
    // Drag an item with ID 'drag-item' into a container with ID 'drop-zone'
    const source = page.locator('//span[contains(text(), "Name")]');
    const target = page.locator('//div[@id="assigned-attribute-groups"]');

    //await source.dragTo(target);
    await page.locator(source).hover();
    await page.mouse.down();
    await page.locator(target).hover();
    await page.mouse.up();
    await page.getByRole('button', { name: 'Save Attribute Family' }).click();
    await expect(page.getByText(/Family updated successfully/i)).toBeVisible();

  });

  test('Delete Attribute Family', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Families' }).click();
    await page.getByText('headerFooter').getByTitle('Delete').click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Family deleted successfully/i)).toBeVisible();

  });
});  