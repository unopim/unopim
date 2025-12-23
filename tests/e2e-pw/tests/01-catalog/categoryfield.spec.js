const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Category Field', () => {
test('Create category field with empty Code', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('div').filter({ hasText: /^Select option$/ }).click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText('The Code field is required ')).toBeVisible();
});

test('Create category field with empty Type', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('suggestion');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText('The Type field is required ')).toBeVisible();
});

test('Create category field with empty Code and Type', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText('The Code field is required ')).toBeVisible();
  await expect(adminPage.getByText('The Type field is required ')).toBeVisible();
});

test('Create category field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('test1');
  await adminPage.locator('div').filter({ hasText: /^Select option$/ }).click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Category Field Created Successfully/i)).toBeVisible();
});

test('should allow category field search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('tes');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=1 Results')).toBeVisible();
  await expect(adminPage.locator('text=test1', {exact:true})).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a category (Edit, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'name' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/category-fields\/edit/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('should allow selecting all category field with the mass action checkbox', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.click('label[for="mass_action_select_all_records"]');
  await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
});

test('update category field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByText('test1Suggestion').getByTitle('Edit').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('soogesan');
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Category Field Updated Successfully/i)).toBeVisible();
});

test('delete category field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByText('test1soogesan').getByTitle('Delete').click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Category Field Deleted Successfully/i)).toBeVisible();
});

test('delete default category field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByText('nameName').getByTitle('Delete').click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/This category field can not be deleted./i)).toBeVisible();
});
});

