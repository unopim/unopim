import { test, expect } from '@playwright/test';
test.describe('Attribute Family', () => {
test.beforeEach(async ({ page }) => {
   await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
});

test('Create Attribute family with empty code field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attribute Families' }).click();
  await page.getByRole('link', { name: 'Create Attribute Family' }).click();
  await page.getByRole('textbox', { name: 'Enter Code' }).click();
  await page.getByRole('textbox', { name: 'Enter Code' }).fill('');
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Header');
  await page.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
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

test('should allow attribute family search', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attribute Families' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('header');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=headerHeader')).toBeVisible();
});

test('should open the filter menu when clicked', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attribute Families' }).click();
  await page.getByText('Filter', { exact: true }).click();
  await expect(page.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per page', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attribute Families' }).click();
  await page.getByRole('button', { name: '' }).click();
  await page.getByText('20', { exact: true }).click();
  await expect(page.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a attribute family (Edit, Copy, Delete)', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attribute Families' }).click();
  const itemRow = page.locator('div', { hasText: 'header' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(page).toHaveURL(/\/admin\/catalog\/families\/edit/);
  await page.goBack();
  await itemRow.locator('span[title="Copy"]').first().click();
  await expect(page).toHaveURL(/\/admin\/catalog\/families\/copy/);
  await page.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Edit Attribute Family', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attribute Families' }).click();
  await page.getByText('headerHeader').getByTitle('Edit').click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Footer');
  await page.locator('.secondary-button', { hasText: 'Assign Attribute Group' }).click();
  await page.locator('input[name="group"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('textbox', { name: 'group-searchbox' }).fill('General');
  await page.getByRole('option', { name: 'General' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Assign Attribute Group' }).click();
  const dragHandle = await page.locator('#unassigned-attributes i.icon-drag:near(:text("SKU"))').first();
  const dropTarget = await page.locator('#assigned-attribute-groups .group_node').first();
  const dragBox = await dragHandle.boundingBox();
  const dropBox = await dropTarget.boundingBox();
  if (dragBox && dropBox) {
  await page.mouse.move(dragBox.x + dragBox.width / 2, dragBox.y + dragBox.height / 2);
  await page.mouse.down();
  await page.mouse.move(dropBox.x + dropBox.width / 2, dropBox.y + dropBox.height / 2, { steps: 10 });
  await page.mouse.up();
  }
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

