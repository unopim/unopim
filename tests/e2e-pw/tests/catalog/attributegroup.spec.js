import { test, expect } from '@playwright/test';
test.describe('UnoPim Test cases', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
  });

  test('Create Attribute Group with empty Code field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
    await page.getByRole('link', { name: 'Create Attribute Group' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('');
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Product Description');
    await page.getByRole('button', { name: 'Save Attribute Group' }).click();
    await expect(page.getByText('The Code field is required')).toBeVisible();
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

  test('should allow category search', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
    await page.getByRole('textbox', { name: 'Search' }).click();
    await page.getByRole('textbox', { name: 'Search' }).type('product_description');
    await page.keyboard.press('Enter');
    await expect(page.locator('text=product_description')).toBeVisible();
  });


  test('should open the filter menu when clicked', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
    await page.getByText('Filter', { exact: true }).click();
    await expect(page.getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per page', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
    await page.getByRole('button', { name: '' }).click();
    await page.getByText('20', { exact: true }).click();
    await expect(page.getByRole('button', { name: '' })).toContainText('20');
  });

  test('should perform actions on a category (Edit, Delete)', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
    const itemRow = page.locator('div', { hasText: 'product_description' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(page).toHaveURL(/\/admin\/catalog\/attributegroups\/edit/);
    await page.goBack();
    await itemRow.locator('span[title="Delete"]').first().click();
    await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
  });

  test('Update attribute group', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attribute Groups' }).click();
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