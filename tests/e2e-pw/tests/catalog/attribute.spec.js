import { test, expect } from '@playwright/test';
test.describe('UnoPim Attribute', () => {
test.beforeEach(async ({ page }) => {
   await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
});

test('Create attribute with empty code field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('link', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
});

test('Create attribute with empty Type field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('link', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product_name');
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText('The Type field is required')).toBeVisible();
});

test('Create attribute with empty Code and Type field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('link', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
  await expect(page.getByText('The Type field is required')).toBeVisible();
});

test('Create attribute', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('link', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product_name');
  await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText(/Attribute Created Successfully/i)).toBeVisible();
});

test('should allow attribute search', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('product_name');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=product_name')).toBeVisible();
});

test('should open the filter menu when clicked', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByText('Filter', { exact: true }).click();
  await expect(page.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per page', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('button', { name: '' }).click();
  await page.getByText('20', { exact: true }).click();
  await expect(page.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a category (Edit, Delete)', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = page.locator('div', { hasText: 'product_name' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(page).toHaveURL(/\/admin\/catalog\/attributes\/edit/);
  await page.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('should allow selecting all category with the mass action checkbox', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.click('label[for="mass_action_select_all_records"]');
  await expect(page.locator('#mass_action_select_all_records')).toBeChecked();
});

test('Update attribute', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = page.locator('div', { hasText: 'product_name' });
  await itemRow.locator('span[title="Edit"]').first().click();
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

