import { test, expect } from '@playwright/test';
test.describe('UnoPim Category', () => {
test.beforeEach(async ({ page }) => {
   await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
});

test('Create Categories with empty Code field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByRole('link', { name: 'Create Category' }).click();
  await page.locator('input[name="code"]').click();
  await page.locator('input[name="code"]').fill('');
  await page.locator('#name').click();
  await page.locator('#name').type('Television');
  await page.getByRole('button', { name: 'Save Category' }).click();
  await expect(page.getByText('The code field is required')).toBeVisible();
});

test('Create Categories with empty Name field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByRole('link', { name: 'Create Category' }).click();
  await page.locator('input[name="code"]').click();
  await page.locator('input[name="code"]').fill('television');
  await page.locator('#name').click();
  await page.locator('#name').fill('');
  await page.getByRole('button', { name: 'Save Category' }).click();
  await expect(page.getByText('The Name field is required')).toBeVisible();
});

test('Create Categories with empty Code and Name field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByRole('link', { name: 'Create Category' }).click();
  await page.locator('input[name="code"]').click();
  await page.locator('input[name="code"]').fill('');
  await page.locator('#name').click();
  await page.locator('#name').type('');
  await page.getByRole('button', { name: 'Save Category' }).click();
  await expect(page.getByText('The code field is required')).toBeVisible();
  await expect(page.getByText('The Name field is required')).toBeVisible();});

test('Create Categories with all field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByRole('link', { name: 'Create Category' }).click();
  await page.locator('input[name="code"]').click();
  await page.locator('input[name="code"]').type('test1');
  await page.waitForTimeout(100);
  await page.locator('#name').click();
  await page.locator('#name').type('Television');
  await page.waitForTimeout(100);
  await page.getByRole('button', { name: 'Save Category' }).click();
  await expect(page.getByText(/Category created successfully/i)).toBeVisible();
});

test('should allow category search', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('test1');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=TelevisionTelevisiontest1')).toBeVisible();
});

test('should open the filter menu when clicked', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByText('Filter', { exact: true }).click();
  await expect(page.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per page', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByRole('button', { name: '' }).click();
  await page.getByText('20', { exact: true }).click();
  await expect(page.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a category (Edit, Delete)', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  const itemRow = page.locator('div', { hasText: 'root' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(page).toHaveURL(/\/admin\/catalog\/categories\/edit/);
  await page.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('should allow selecting all category with the mass action checkbox', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.click('label[for="mass_action_select_all_records"]');
  await expect(page.locator('#mass_action_select_all_records')).toBeChecked();
});

test('Update Categories', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('test1');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=TelevisionTelevisiontest1')).toBeVisible();
  const itemRow = page.locator('div', { hasText: 'TelevisionTelevisiontest1' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await page.locator('#name').click();
  await page.locator('#name').fill('LG Television');
  await page.waitForTimeout(100);
  await page.getByRole('button', { name: 'Save Category' }).click();
  await expect(page.getByText(/Category updated successfully/i)).toBeVisible();
});

test('Delete Category', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await page.getByText('LG TelevisionLG Televisiontest1').getByTitle('Delete').click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/The category has been successfully deleted/i)).toBeVisible();
});

test('Delete Root Category', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  const itemRow = page.locator('div', { hasText: '[root][root]' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/You cannot delete the root category that is associated with a channel./i)).toBeVisible();
});
});

