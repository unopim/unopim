const { test, expect } = require('../../utils/fixtures');
test.describe('UnoPim Create Product Test Cases', () => {

test('with empty product type field', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Default' }).first().click();
await adminPage.locator('input[name="sku"]').fill(`acer456_${Date.now()}`);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Type field is required');
});

test('create product with simple alphanumeric SKU (ABC123)', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `ABC123_${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toBeVisible();
});

test('create product with letters only SKU', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `ABCDEFG_${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toBeVisible();
});

test('create product with hyphen separator (PROD-001)', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `PROD-001_${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toBeVisible();
});

test('create product with multiple hyphens', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `PROD-CODE-${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toBeVisible();
});

test('create product with underscore separator', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `ITEM_CODE_${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toBeVisible();
});

test('create product with mixed separators', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `SKU-PROD_${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toBeVisible();
});

test('reject SKU starting with hyphen', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `-PROD001_${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toHaveCount(0);
});

test('reject SKU starting with underscore', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `_INVALID_${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toHaveCount(0);
});

test('reject SKU with consecutive hyphens', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `PROD--${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toHaveCount(0);
});

test('reject SKU with special characters', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `PROD@${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toHaveCount(0);
});

test('reject SKU with spaces', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option').first().click();
const sku = `PROD ${Date.now()}`;
await adminPage.locator('input[name="sku"]').fill(sku);
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(sku)).toHaveCount(0);
});

test('with empty family field', async ({ adminPage }) => {
await adminPage.goto('/admin/catalog/products');
await adminPage.waitForLoadState('load');
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="sku"]').click();
await adminPage.locator('input[name="sku"]').fill('acer456');
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText('The Family field is required')).toBeVisible();
});

test('with empty sku field', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Default' }).first().click();
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
});

test('with empty product type and family field', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="sku"]').click();
await adminPage.locator('input[name="sku"]').fill('acer456');
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
await expect(adminPage.locator('#app').getByText('The Family field is required')).toBeVisible();
});

test('with empty product type and sku field', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Default' }).first().click();
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Type field is required');
await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
});

test('with empty family and sku field', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText('The Family field is required')).toBeVisible();
await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
});

test('with all field empty', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
await expect(adminPage.locator('#app').getByText('The Family field is required')).toBeVisible();
await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
});

test('Create Simple Product with all input', async ({ adminPage }) => {
// Clean up acer456 from previous runs via mass delete
await adminPage.goto('/admin/catalog/products');
await adminPage.waitForLoadState('load');
await adminPage.getByRole('textbox', { name: 'Search' }).fill('acer456');
await adminPage.keyboard.press('Enter');
await adminPage.waitForLoadState('networkidle');
const resultsText = await adminPage.locator('text=/\\d+ Results/').textContent().catch(() => '0 Results');
if (!resultsText.startsWith('0')) {
  await adminPage.click('label[for="mass_action_select_all_records"]');
  await adminPage.locator('select[name="mass_action_name"]').selectOption('delete');
  await adminPage.getByRole('button', { name: 'Submit' }).click();
  await adminPage.getByRole('button', { name: 'Agree' }).click();
  await adminPage.waitForLoadState('networkidle');
}
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Default' }).first().click();
await adminPage.locator('input[name="sku"]').click();
await adminPage.locator('input[name="sku"]').fill('acer456');
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(/Product created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('Create Simple Product with same SKU', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Simple' }).first().click();
await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Default' }).first().click();
await adminPage.locator('input[name="sku"]').click();
await adminPage.locator('input[name="sku"]').fill('acer456');
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The sku has already been taken.');
});

test('should allow product search', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByRole('textbox', { name: 'Search' }).click();
await adminPage.getByRole('textbox', { name: 'Search' }).type('acer');
await adminPage.keyboard.press('Enter');
await expect(adminPage.locator('text=1 Results')).toBeVisible();
await expect(adminPage.locator('text=acer456', {exact:true})).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByText('Filter', { exact: true }).click();
await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
const perPageButton = adminPage.locator('button:has(.icon-chevron-down)').first();
await perPageButton.click();
await adminPage.getByText('20', { exact: true }).click();
await expect(perPageButton).toContainText('20');
});

test('should allow quick export', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByRole('button', { name: 'Quick Export' }).click();
await expect(adminPage.locator('#app').getByText('Download')).toBeVisible();
});

test('should perform actions on a product (Edit, Copy, Delete)', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
const itemRow = adminPage.locator('div', { hasText: 'acer456' });
await itemRow.locator('span[title="Edit"]').first().click();
await expect(adminPage).toHaveURL(/\/admin\/catalog\/products\/edit/);
await adminPage.goBack();
await adminPage.waitForLoadState('networkidle');
await itemRow.locator('span[title="Copy"]').first().click();
await expect(adminPage.locator('#app').getByText('Are you sure?')).toBeVisible();
await adminPage.getByRole('button', { name: 'Agree', exact: true }).click();
await expect(adminPage.locator('text=Product copied successfully')).toBeVisible();
await adminPage.locator('a:has-text("Back")').click();
await adminPage.waitForLoadState('networkidle');
const itemNRow = await adminPage.locator('div', { hasText: /temporary/ });
await itemNRow.locator('span[title="Delete"]').first().click();
await adminPage.getByRole('button', { name: 'Delete' }).click();
await expect(adminPage.locator('#app').getByText(/Product deleted successfully/i)).toBeVisible();
await itemRow.locator('span[title="Delete"]').first().click();
await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('should allow selecting all products with the mass action checkbox', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.click('label[for="mass_action_select_all_records"]');
await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
});
});

test.describe('UnoPim Update Product Test cases', () => {
test('Update simple product', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
const itemRow = adminPage.locator('div', { hasText: 'acer456' });
await itemRow.locator('span[title="Edit"]').first().click();
await adminPage.waitForLoadState('load');
await adminPage.locator('input[name="values[common][product_number]"]').click();
await adminPage.locator('input[name="values[common][product_number]"]').fill('456');
await adminPage.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
await adminPage.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').type('Acer Laptop');
await adminPage.locator('input[name="values[common][url_key]"]').click();
await adminPage.locator('input[name="values[common][url_key]"]').type('laptop');
await adminPage.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'White' }).first().click();
const shortDescFrame = adminPage.frameLocator('#short_description_ifr');
await shortDescFrame.locator('body').click();
await shortDescFrame.locator('body').type('This laptop is best in the market');
const mainDescFrame = adminPage.frameLocator('#description_ifr');
await mainDescFrame.locator('body').click();
await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality');
await adminPage.locator('#meta_title').fill('thakubali');
await adminPage.locator('#price').click();
await adminPage.locator('#price').fill('40000');
await adminPage.getByRole('button', { name: 'Save Product' }).click({ noWaitAfter: true });
await expect(adminPage.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 30000 });
});

test('Delete simple product', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
const itemRow = await adminPage.locator('div', { hasText: 'acer456' });
await itemRow.locator('span[title="Delete"]').first().click();
await adminPage.getByRole('button', { name: 'Delete' }).click();
await expect(adminPage.locator('#app').getByText(/Product deleted successfully/i)).toBeVisible();
});

test('Create Configurable Product', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('button', { name: 'Create Product' }).click();
await adminPage.locator('div').filter({ hasText: /^Select option$/ }).first().click();
await adminPage.getByRole('option', { name: 'Configurable' }).first().click();
await adminPage.getByRole('textbox', {name: 'attribute_family_id'}).locator('..').locator('.multiselect__placeholder').click();
await adminPage.getByRole('option', { name: 'Default' }).first().click();
await adminPage.locator('input[name="sku"]').click();
await adminPage.locator('input[name="sku"]').fill('realme1245');
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await adminPage.getByRole('paragraph').filter({ hasText: 'Brand' }).locator('span').click();
await adminPage.getByRole('button', { name: 'Save Product' }).click();
await expect(adminPage.locator('#app').getByText(/Product created successfully/i)).toBeVisible();
});

test('Update Configurable Product', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
const itemRow = await adminPage.locator('div', { hasText: 'realme1245' });
await itemRow.locator('span[title="Edit"]').first().click();
await adminPage.waitForLoadState('load');
await adminPage.locator('#product_number').click();
await adminPage.locator('#product_number').type('12345');
await adminPage.locator('#name').click();
await adminPage.locator('#name').type('Realme 7pro');
await adminPage.locator('#url_key').click();
await adminPage.locator('#url_key').type('Mobile');
const shortDescFrame = adminPage.frameLocator('#short_description_ifr');
await shortDescFrame.locator('body').click();
await shortDescFrame.locator('body').type('This smart phone is best in the market');
const mainDescFrame = adminPage.frameLocator('#description_ifr');
await mainDescFrame.locator('body').click();
await mainDescFrame.locator('body').type('This is the Realme 7pro phone with 7500mah batttery and 200mp camera');
await adminPage.locator('#meta_title').click();
await adminPage.locator('#meta_title').fill('best mobile');
await adminPage.locator('#price').click();
await adminPage.locator('#price').fill('25000');
await adminPage.getByRole('button', { name: 'Save Product' }).click({ noWaitAfter: true });
await expect(adminPage.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 30000 });
});

test('Delete configurable product', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
const itemRow = await adminPage.locator('div', { hasText: 'realme1245' });
await itemRow.locator('span[title="Delete"]').first().click();
await adminPage.click('button:has-text("Delete")');
await expect(adminPage.locator('#app').getByText(/Product deleted successfully/i)).toBeVisible();
});
});

test.describe('UnoPim Test cases dynamic column', () => {
test('Dynamic Column should be clickable', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await expect(adminPage.locator('#app').getByText(/Columns/)).toBeVisible();
await adminPage.getByText('Columns', { exact: true }).click();
await expect(adminPage.locator('#app').getByText('Manage columns')).toBeVisible();
});

test('Dynamic Column search bar should be visible and clickable', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByText('Columns', { exact: true }).click();
await adminPage.locator('form').filter({ hasText: 'Columns Manage columns' }).getByPlaceholder('Search').click();
await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' }).getByPlaceholder('Search')).toBeEnabled();
});

test('Dynamic Column search the default fields', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByText('Columns', { exact: true }).click();
await adminPage.locator('form').filter({ hasText: 'Columns Manage columns' }).getByPlaceholder('Search').click();
await adminPage.locator('form').filter({ hasText: 'Columns Manage columns' }).getByPlaceholder('Search').fill('parent');
await adminPage.keyboard.press('Enter');
await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' })).toHaveText(/Parent/);
});

test('Attributes should be visible', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByText('Columns', { exact: true }).click();
await expect(adminPage.locator('#app').getByText('Manage columns')).toBeVisible();
await expect(adminPage.locator('#app').getByText('Available Columns')).toBeVisible();
await expect(adminPage.locator('#app').getByText('Selected Columns')).toBeVisible();
await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' })).toHaveText(/Attribute Family/);
await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' })).toHaveText(/Meta Title/);
await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' })).toHaveText(/Name/);
});

test('check Is Filterable', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByText('Filter', { exact: true }).click();
await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
// Navigate directly to attributes instead of closing drawer
await adminPage.goto('/admin/catalog/attributes');
await adminPage.waitForLoadState('load');
await adminPage.getByRole('textbox', { name: 'Search' }).click();
await adminPage.getByRole('textbox', { name: 'Search' }).fill('Image');
await adminPage.keyboard.press('Enter');
const itemRow = adminPage.locator('div', { hasText: 'image' }).nth(1);
await itemRow.locator('span[title="Edit"]').nth(1).click();
const isFilterableCheckbox = adminPage.getByRole('checkbox', { name: /Is Filterable/i });
if (!(await isFilterableCheckbox.isChecked())) {
  await adminPage.locator('label[for="is_filterable"]').first().click();
}
await expect(isFilterableCheckbox).toBeChecked();
await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
await expect(adminPage.locator('#app').getByText(/Attribute Updated Successfully/)).toBeVisible();
await adminPage.locator('a:has-text("Back")').click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByText('Filter', { exact: true }).click();
await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
const filterDrawer = adminPage.locator('div[class*="overflow-auto"]');
await expect(filterDrawer.getByText('Image')).toBeVisible();
});

test('Add Column to the product data grid and verify', async ({ adminPage }) => {
await adminPage.getByRole('link', { name: ' Catalog' }).click();
await adminPage.getByRole('link', { name: 'Products' }).click();
await adminPage.getByText('Columns', { exact: true }).click();
const dragHandle = await adminPage.locator('div:has(span:text("Parent")) >> i.icon-drag').first();
const dropTarget = await adminPage.locator('div:has-text("Selected Columns")').first(); // Adjust selector as needed
const dragBox = await dragHandle.boundingBox();
const dropBox = await dropTarget.boundingBox();

if (dragBox && dropBox) {
await adminPage.mouse.move(dragBox.x + dragBox.width / 2, dragBox.y + dragBox.height / 2);
await adminPage.mouse.down();
await adminPage.mouse.move(
dropBox.x + dropBox.width / 2,
dropBox.y + dropBox.height / 2,
{ steps: 50 }
);
await adminPage.mouse.up();
};
await expect(adminPage.locator('div:has-text("Selected Columns") >> text=Parent')).toBeVisible();
await adminPage.getByRole('button', {name: 'Apply'}).click();
});
});
