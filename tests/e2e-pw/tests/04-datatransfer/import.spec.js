const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Import Jobs', () => {
test('Create Import with empty Code field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
});

test('Create Import with empty Type field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The Type field is required')).toBeVisible();
});

test('Create Import with empty File field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The File field is required')).toBeVisible();
});

test('Create Import with empty Code, Type and File field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
  await expect(adminPage.getByText('The Type field is required')).toBeVisible();
});

test('Create Import with empty Action field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.locator('#action').getByRole('combobox').locator('div').filter({ hasText: 'Create/Update' }).click();
  await adminPage.getByRole('option', { name: 'Create/Update' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The Action field is required')).toBeVisible();
});

test('Create Import with empty Validation Strategy field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.locator('#validation_strategy').getByRole('combobox').locator('div').filter({ hasText: 'Stop on Errors' }).click();
  await adminPage.getByText('Stop on Errors').click();
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The Validation Strategy field is required')).toBeVisible();
});

test('Create Import with empty Allowed Errors field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.getByRole('textbox', { name: 'Allowed Errors' }).click();
  await adminPage.getByRole('textbox', { name: 'Allowed Errors' }).fill('');
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The Allowed Errors field is required')).toBeVisible();
});

test('Create Import with empty Field Separator field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.getByRole('textbox', { name: 'Field Separator' }).click();
  await adminPage.getByRole('textbox', { name: 'Field Separator' }).fill('');
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The Field Separator field is required')).toBeVisible();
});

test('Create Import with all required field empty', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  await adminPage.locator('#action').getByRole('combobox').locator('div').filter({ hasText: 'Create/Update' }).click();
  await adminPage.getByRole('option', { name: 'Create/Update' }).locator('span').first().click();
  await adminPage.locator('#validation_strategy').getByRole('combobox').locator('div').filter({ hasText: 'Stop on Errors' }).click();
  await adminPage.getByText('Stop on Errors').click();
  await adminPage.getByRole('textbox', { name: 'Allowed Errors' }).click();
  await adminPage.getByRole('textbox', { name: 'Allowed Errors' }).fill('');
  await adminPage.getByRole('textbox', { name: 'Field Separator' }).click();
  await adminPage.getByRole('textbox', { name: 'Field Separator' }).fill('');
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
  await expect(adminPage.getByText('The Type field is required')).toBeVisible();
  await expect(adminPage.getByText('The Action field is required')).toBeVisible();
  await expect(adminPage.getByText('The Validation Strategy field is required')).toBeVisible();
  await expect(adminPage.getByText('The Allowed Errors field is required')).toBeVisible();
  await expect(adminPage.getByText('The Field Separator field is required')).toBeVisible();
});

test('Create Product Import', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText(/Import created successfully/i)).toBeVisible();
  await expect(adminPage.getByRole('button', { name: 'Import Now' })).toBeVisible();
  await adminPage.getByRole('button', { name: 'Import Now' }).click();
  await expect(adminPage.getByText('Awaiting Job Processing in')).toBeVisible();
});

test('Create Import with same Code', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product Import');
  await adminPage.locator('#import-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText('The code has already been taken.')).toBeVisible();
});

test('should allow Import search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('product');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=product Import', {exact:true})).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a Import job (Edit, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'product Import' });
  await itemRow.locator('span[title="Import"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/import/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports\/edit/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Delete Product Import', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  const itemRow = await adminPage.locator('div', { hasText: 'Product Import' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Import deleted successfully/i)).toBeVisible();
});

test('Create Category Import', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await adminPage.getByRole('link', { name: 'Create Import' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('Category Import');
  const fileInput = adminPage.locator('input[type="file"][id="36_dropzone-file"]');
  await fileInput.setInputFiles('assets/1k_products.xlsx');
  await adminPage.getByRole('button', { name: 'Save Import' }).click();
  await expect(adminPage.getByText(/Import created successfully/i)).toBeVisible();
});

test('Delete Category Import', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  const itemRow = await adminPage.locator('div', { hasText: 'Category Import' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Import deleted successfully/i)).toBeVisible();
});
});

