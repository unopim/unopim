const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim  Create Product Test cases', () => {
test('with empty product type field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
  await adminPage.locator('input[name="sku"]').click();
  await adminPage.locator('input[name="sku"]').fill('acer456');
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Type field is required');
});

test('with empty family field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Simple' }).locator('span').first().click();
  await adminPage.locator('input[name="sku"]').click();
  await adminPage.locator('input[name="sku"]').fill('acer456');
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Family field is required');
});

test('with empty sku field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Simple' }).locator('span').first().click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
});

test('with empty product type and family field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('input[name="sku"]').click();
  await adminPage.locator('input[name="sku"]').fill('acer456');
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.getByText('The Type field is required')).toBeVisible();
  await expect(adminPage.getByText('The Family field is required')).toBeVisible();
});

test('with empty product type and sku field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Type field is required');
  await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
});

test('with empty family and sku field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Simple' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Family field is required');
  await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
});

test('with all field empty', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.getByText('The Type field is required')).toBeVisible();
  await expect(adminPage.getByText('The Family field is required')).toBeVisible();
  await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
});

test('Create Simple Product with all input', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Simple' }).locator('span').first().click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
  await adminPage.locator('input[name="sku"]').click();
  await adminPage.locator('input[name="sku"]').fill('acer456');
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.getByText(/Product created successfully/i)).toBeVisible();
});

test('Create Simple Product with same SKU', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Simple' }).locator('span').first().click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
  await adminPage.locator('input[name="sku"]').click();
  await adminPage.locator('input[name="sku"]').fill('acer456');
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('input[name="sku"] + p.text-red-600')).toHaveText('The sku has already been taken.');
});

test('should allow product search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('acer');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=1 Results')).toBeVisible();
  await expect(adminPage.locator('text=acer456', {exact:true})).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should allow quick export', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByRole('button', { name: 'Quick Export' }).click();
  await expect(adminPage.getByText('Download')).toBeVisible();
});

test('should perform actions on a product (Edit, Copy, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'acer456' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/products\/edit/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Copy"]').first().click();
  await expect(adminPage.getByText('Are you sure?')).toBeVisible();
  await adminPage.getByRole('button', { name: 'Agree', exact: true }).click();
  await expect(adminPage.locator('text=Product copied successfully')).toBeVisible();
  await adminPage.locator('a:has-text("Back")').click();
  const itemNRow = await adminPage.locator('div', { hasText: 'temporary-sku' });
  await itemNRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Product deleted successfully/i)).toBeVisible();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('should allow selecting all products with the mass action checkbox', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.click('label[for="mass_action_select_all_records"]');
  await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
});
});

test.describe('UnoPim Update Product Test cases', () => {
test('Update simple product', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'acer456' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('input[name="values[common][product_number]"]').click();
  await adminPage.locator('input[name="values[common][product_number]"]').fill('456');
  await adminPage.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').type('Acer Laptop');
  await adminPage.waitForTimeout(500);
  await adminPage.locator('input[name="values[common][url_key]"]').click();
  await adminPage.locator('input[name="values[common][url_key]"]').type('laptop');
  await adminPage.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'White' }).locator('span').first().click();
  const shortDescFrame = adminPage.frameLocator('#short_description_ifr');
  await shortDescFrame.locator('body').click();
  await shortDescFrame.locator('body').type('This laptop is best in the market');
  const mainDescFrame = adminPage.frameLocator('#description_ifr');
  await mainDescFrame.locator('body').click();
  await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality');
  await adminPage.locator('#meta_title').fill('thakubali');
  await adminPage.locator('#price').click();
  await adminPage.locator('#price').fill('40000');
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Product updated successfully/i)).toBeVisible();
  await adminPage.waitForTimeout(500);
});

test('Delete simple product', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  const itemRow = await adminPage.locator('div', { hasText: 'acer456' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Product deleted successfully/i)).toBeVisible();
});

test('Create Configurable Product', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.locator('div').filter({ hasText: /^Select option$/ }).first().click();
  await adminPage.getByRole('option', { name: 'Configurable' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Select option$/ }).click();
  await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
  await adminPage.locator('input[name="sku"]').click();
  await adminPage.locator('input[name="sku"]').fill('realme1245');
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await adminPage.getByRole('paragraph').filter({ hasText: 'Brand' }).locator('span').click();
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.getByText(/Product created successfully/i)).toBeVisible();
});

test('Update Configurable Product', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  const itemRow = await adminPage.locator('div', { hasText: 'realme1245' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('#product_number').click();
  await adminPage.locator('#product_number').type('12345');
  await adminPage.locator('#name').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#name').type('Realme 7pro');
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#url_key').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#url_key').type('Mobile');
  await adminPage.waitForTimeout(500);
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
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Product updated successfully/i)).toBeVisible();
  await adminPage.waitForTimeout(500);
});

test('Delete configurable product', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  const itemRow = await adminPage.locator('div', { hasText: 'realme1245' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.getByText(/Product deleted successfully/i)).toBeVisible();
});
});

test.describe('UnoPim Test cases dynamic column', () => {
test('Dynamic Column should be clickable', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await expect(adminPage.getByText(/Columns/)).toBeVisible();
  await adminPage.getByText('Columns', { exact: true }).click();
  await expect(adminPage.getByText('Manage columns')).toBeVisible();
});

test('Dynamic Column search bar should be visible and clickable', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByText('Columns', { exact: true }).click();
  await adminPage.locator('form').filter({ hasText: 'Columns Manage columns' }).getByPlaceholder('Search').click();
  await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' }).getByPlaceholder('Search')).toBeEnabled();
});

test('Dynamic Column search the default fields', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByText('Columns', { exact: true }).click();
  await adminPage.locator('form').filter({ hasText: 'Columns Manage columns' }).getByPlaceholder('Search').click();
  await adminPage.locator('form').filter({ hasText: 'Columns Manage columns' }).getByPlaceholder('Search').fill('parent');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' })).toHaveText(/Parent/);
});

test('Attributes should be visible', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByText('Columns', { exact: true }).click();
  await expect(adminPage.getByText('Manage columns')).toBeVisible();
  await expect(adminPage.getByText('Available Columns')).toBeVisible();
  await expect(adminPage.getByText('Selected Columns')).toBeVisible();
  await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' })).toHaveText(/Attribute Family/);
  await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' })).toHaveText(/Meta Title/);
  await expect(adminPage.locator('form').filter({ hasText: 'Columns Manage columns' })).toHaveText(/Name/);
});

test('check Is Filterable', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
  const filterDrawer = adminPage.locator('div[class*="overflow-auto"]');
  await expect(filterDrawer.getByText('Image')).toHaveCount(0);
  await adminPage.getByText('Save').click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).fill('Image');
  await adminPage.keyboard.press('Enter');
  const itemRow = adminPage.locator('div', { hasText: 'image' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Is Filterable').check();
  await expect(adminPage.getByText('Is Filterable')).toBeChecked();
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Updated Successfully/)).toBeVisible();
  await adminPage.locator('a:has-text("Back")').click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
  await expect(filterDrawer.getByText('Image')).toBeVisible();
});

test('Add Column to the product data grid and verify', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await adminPage.getByText('Columns', { exact: true }).click();
  const dragHandle = await adminPage.locator('div:has(span:text("Parent")) >> i.icon-drag').first();
  const dropTarget = await adminPage.locator('div:has-text("Selected Columns")').first(); // Adjust selector as needed
  const dragBox = await dragHandle.boundingBox();
  const dropBox = await dropTarget.boundingBox();

  if (dragBox && dropBox) {
  await adminPage.mouse.move(dragBox.x + dragBox.width / 2, dragBox.y + dragBox.height / 2);
  await adminPage.mouse.down();
  await adminPage.waitForTimeout(100);
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
