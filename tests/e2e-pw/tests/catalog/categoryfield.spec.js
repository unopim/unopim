import { test, expect } from '@playwright/test';
test.describe('UnoPim Category Field', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();

  });

  test('Create category field with empty Code', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByRole('link', { name: 'Create Category Field' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('');
    await page.locator('div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Text' }).locator('span').first().click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
    await page.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(page.getByText('The Code field is required ')).toBeVisible();
  });

  test('Create category field with empty Type', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByRole('link', { name: 'Create Category Field' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('suggestion');
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
    await page.getByRole('button', { name: 'Save Category Field' }).click();
     await expect(page.getByText('The Type field is required ')).toBeVisible();
  });

  test('Create category field with empty Code and Type', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByRole('link', { name: 'Create Category Field' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('');
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
    await page.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(page.getByText('The Code field is required ')).toBeVisible();
    await expect(page.getByText('The Type field is required ')).toBeVisible();
  });

  test('Create category field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByRole('link', { name: 'Create Category Field' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('test1');
    await page.locator('div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Text' }).locator('span').first().click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
    await page.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(page.getByText(/Category Field Created Successfully/i)).toBeVisible();
  });

  test('should allow category field search', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
   await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByRole('textbox', { name: 'Search' }).click();
    await page.getByRole('textbox', { name: 'Search' }).type('test1');
    await page.keyboard.press('Enter');
    await expect(page.locator('text=1 Results')).toBeVisible();
    await expect(page.locator('text=test1')).toBeVisible();
  });


  test('should open the filter menu when clicked', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByText('Filter', { exact: true }).click();
    await expect(page.getByText('Apply Filters')).toBeVisible();
  });

   test('should allow setting items per page', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
   await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByRole('button', { name: '' }).click();
    await page.getByText('20', { exact: true }).click();
    await expect(page.getByRole('button', { name: '' })).toContainText('20');
  });

  test('should perform actions on a category (Edit, Delete)', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
   await page.getByRole('link', { name: 'Category Fields' }).click();
    const itemRow = page.locator('div', { hasText: 'name' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(page).toHaveURL(/\/admin\/catalog\/category-fields\/edit/);
     await page.goBack();
    await itemRow.locator('span[title="Delete"]').first().click();
    await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
  });

   test('should allow selecting all category field with the mass action checkbox', async ({ page }) => {
     await page.getByRole('link', { name: ' Catalog' }).click();
   await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.click('label[for="mass_action_select_all_records"]');
    await expect(page.locator('#mass_action_select_all_records')).toBeChecked();
  });

  test('update category field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByText('test1Suggestion').getByTitle('Edit').click();
    await page.locator('input[name="en_US\\[name\\]"]').click();
    await page.locator('input[name="en_US\\[name\\]"]').fill('soogesan');
    await page.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(page.getByText(/Category Field Updated Successfully/i)).toBeVisible();
  });

  test('delete category field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByText('test1soogesan').getByTitle('Delete').click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Category Field Deleted Successfully/i)).toBeVisible();
  });

  test('delete default category field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Category Fields' }).click();
    await page.getByText('nameName').getByTitle('Delete').click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/This category field can not be deleted./i)).toBeVisible();
  });
});