import { test, expect } from '@playwright/test';
test.describe('UnoPim Category Field', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();

  });
  test('Create category field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByRole('link', { name: 'Create Category Field' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('suggestion');
    await page.locator('div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Text' }).locator('span').first().click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
    await page.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(page.getByText(/Category Field Created Successfully/i)).toBeVisible();
  });

  test('update category field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByText('suggestionSuggestion').getByTitle('Edit').click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('soogesan');
    await page.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(page.getByText(/Category Field Updated Successfully/i)).toBeVisible();
  });

  test('delete category field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByText('suggestionsoogesan').getByTitle('Delete').click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Category Field Deleted Successfully/i)).toBeVisible();
  });
});