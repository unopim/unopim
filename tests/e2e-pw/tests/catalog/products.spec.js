import { test, expect } from '@playwright/test';
test.describe('UnoPim  Create Product Test cases', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
  });

  test('with empty product type field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="sku"]').click();
    await page.locator('input[name="sku"]').fill('acer456');
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Type field is required');
  });

  test('with empty family field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Simple' }).locator('span').first().click();
    await page.locator('input[name="sku"]').click();
    await page.locator('input[name="sku"]').fill('acer456');
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Family field is required');
  });

  test('with empty sku field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Simple' }).locator('span').first().click();
    await page.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
  });

  test('with empty product type and family field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('input[name="sku"]').click();
    await page.locator('input[name="sku"]').fill('acer456');
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText('The Type field is required')).toBeVisible();
    await expect(page.getByText('The Family field is required')).toBeVisible();
  });

  test('with empty product type and sku field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Type field is required');
    await expect(page.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
  });

  test('with empty family and sku field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Simple' }).locator('span').first().click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.locator('div.border-red-500 + p.text-red-600')).toHaveText('The Family field is required');
    await expect(page.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
  });

  test('with all field empty', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText('The Type field is required')).toBeVisible();
    await expect(page.getByText('The Family field is required')).toBeVisible();
    await expect(page.locator('input[name="sku"] + p.text-red-600')).toHaveText('The SKU field is required');
  });

  test('Create Simple Product with all input', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Simple' }).locator('span').first().click();
    await page.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="sku"]').click();
    await page.locator('input[name="sku"]').fill('acer456');
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText(/Product created successfully/i)).toBeVisible();
  });

  test('Create Simple Product with same SKU', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Simple' }).locator('span').first().click();
    await page.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="sku"]').click();
    await page.locator('input[name="sku"]').fill('acer456');
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.locator('input[name="sku"] + p.text-red-600')).toHaveText('The sku has already been taken.');
  });

  test('should allow product search', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Products' }).click();
    await page.getByRole('textbox', { name: 'Search' }).click();
    await page.getByRole('textbox', { name: 'Search' }).type('acer456');
    await page.keyboard.press('Enter');
    await expect(page.locator('text=1 Results')).toBeVisible();
    await expect(page.locator('text=SKU - acer456')).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Products' }).click();
    await page.getByText('Filter', { exact: true }).click();
    await expect(page.getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per page', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Products' }).click();
    await page.getByRole('button', { name: '' }).click();
    await page.getByText('20', { exact: true }).click();
    await expect(page.getByRole('button', { name: '' })).toContainText('20');
  });

  test('should allow quick export', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Products' }).click();
    await page.getByRole('button', { name: 'Quick Export' }).click();
    await expect(page.getByText('Download')).toBeVisible();
  });

  test('should perform actions on a product (Edit, Copy, Delete)', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Products' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(page).toHaveURL(/\/admin\/catalog\/products\/edit/);
    await page.goBack();
    await itemRow.locator('span[title="Copy"]').first().click();
    await expect(page.locator('text=Product copied successfully')).toBeVisible();
    await page.locator('a:has-text("Back")').click();
    const itemNRow = await page.locator('div', { hasText: 'temporary-sku' });
    await itemNRow.locator('span[title="Delete"]').first().click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Product deleted successfully/i)).toBeVisible();
    await itemRow.locator('span[title="Delete"]').first().click();
    await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
  });

  test('should allow selecting all products with the mass action checkbox', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Products' }).click();
    await page.click('label[for="mass_action_select_all_records"]');
    await expect(page.locator('#mass_action_select_all_records')).toBeChecked();
  });
});

test.describe('UnoPim Update Product Test cases', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
  });

  test('with empty SKU field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('input[name="values[common][sku]"]').click();
    await page.keyboard.press('Control+A');
    await page.keyboard.press('Backspace');
    await page.locator('input[name="values[common][product_number]"]').click();
    await page.locator('input[name="values[common][product_number]"]').fill('456');
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').type('Acer Laptop', { delay: 100 });
    await page.locator('input[name="values[common][url_key]"]').click();
    await page.locator('input[name="values[common][url_key]"]').type('laptop', { delay: 100 });
    await page.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'White' }).locator('span').first().click();
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This laptop is best in the market', { delay: 100 });
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality', { delay: 100 });
    await page.locator('#meta_title').fill('thakubali');
   await page.locator('#price').click();
    await page.locator('#price').fill('40000');
    await expect(page.locator('input[name="uniqueFields[values.common.sku]"] + p.text-red-600')).toHaveText('The SKU field is required');
  });

  test('with empty Name field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('input[name="values[common][product_number]"]').click();
    await page.locator('input[name="values[common][product_number]"]').fill('456');
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').fill('');
    await page.locator('input[name="values[common][url_key]"]').click();
    await page.locator('input[name="values[common][url_key]"]').type('laptop', { delay: 100 });
    await page.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'White' }).locator('span').first().click();
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This laptop is best in the market', { delay: 100 });
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality', { delay: 100 });
    await page.locator('#meta_title').fill('thakubali');
   await page.locator('#price').click();
    await page.locator('#price').fill('40000');
    await page.locator('.relative > .rounded-full').click();
    await expect(page.locator('input[name="values[channel_locale_specific][default][en_US][name]"] + p.text-red-600')).toHaveText('The Name field is required');
  });

  test('with empty URL key field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('input[name="values[common][product_number]"]').click();
    await page.locator('input[name="values[common][product_number]"]').fill('456');
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').type('Acer Laptop', { delay: 100 });
    await page.locator('input[name="values[common][url_key]"]').click();
    await page.locator('input[name="values[common][url_key]"]').fill('');
    await page.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'White' }).locator('span').first().click();
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This laptop is best in the market', { delay: 100 });
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality', { delay: 100 });
    await page.locator('#meta_title').fill('thakubali');
await page.locator('#price').click();
    await page.locator('#price').fill('40000');
    await page.locator('.relative > .rounded-full').click();
    await expect(page.locator('input[name="uniqueFields[values.common.url_key]"] + p.text-red-600')).toHaveText('The URL Key field is required');
  });

  test('with empty Short Description field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('input[name="values[common][product_number]"]').click();
    await page.locator('input[name="values[common][product_number]"]').fill('456');
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').type('Acer Laptop', { delay: 100 });
    await page.locator('input[name="values[common][url_key]"]').click();
    await page.locator('input[name="values[common][url_key]"]').type('laptop', { delay: 100 });
    await page.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'White' }).locator('span').first().click();
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').fill('');
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality', { delay: 100 });
    await page.locator('#meta_title').fill('thakubali');
    await page.locator('#price').click();
    await page.locator('#price').fill('40000');
    await page.locator('.relative > .rounded-full').click();
    await expect(page.locator('p.text-red-600')).toHaveText('The Short Description field is required');
  });

  test('with empty Description field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('input[name="values[common][product_number]"]').click();
    await page.locator('input[name="values[common][product_number]"]').fill('456');
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').type('Acer Laptop', { delay: 100 });
    await page.locator('input[name="values[common][url_key]"]').click();
    await page.locator('input[name="values[common][url_key]"]').type('laptop', { delay: 100 });
    await page.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'White' }).locator('span').first().click();
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This laptop is best in the market', { delay: 100 });
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').fill('');
    await page.locator('#meta_title').fill('thakubali');
   await page.locator('#price').click();
    await page.locator('#price').fill('40000');
    await page.locator('.relative > .rounded-full').click();
    await expect(page.locator('p.text-red-600')).toHaveText('The Description field is required');
  });

  test('with empty Price field', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('input[name="values[common][product_number]"]').click();
    await page.locator('input[name="values[common][product_number]"]').fill('456');
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').type('Acer Laptop', { delay: 100 });
    await page.locator('input[name="values[common][url_key]"]').click();
    await page.locator('input[name="values[common][url_key]"]').type('laptop', { delay: 100 });
    await page.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'White' }).locator('span').first().click();
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This laptop is best in the market', { delay: 100 });
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality', { delay: 100 });
    await page.locator('#meta_title').fill('thakubali');
    await page.locator('#price').click();
    await page.locator('#price').fill('');
    await page.locator('.relative > .rounded-full').click();
    await expect(page.locator('p.text-red-600')).toHaveText('The Price field is required');
  });

  test('with all required field empty', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('input[name="values[common][sku]"]').click();
    await page.keyboard.press('Control+A');
    await page.keyboard.press('Backspace');
    await page.locator('input[name="values[common][product_number]"]').click();
    await page.locator('input[name="values[common][product_number]"]').fill('456');
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').fill('');
    await page.locator('input[name="values[common][url_key]"]').click();
    await page.locator('input[name="values[common][url_key]"]').fill('');
    await page.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'White' }).locator('span').first().click();
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').fill('');
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').fill('');
    await page.locator('#meta_title').fill('thakubali');
    await page.locator('#price').click();
    await page.locator('#price').fill('');
    await page.locator('.relative > .rounded-full').click();
    await expect(page.getByText('The SKU field is required')).toBeVisible();
    await expect(page.locator('input[name="values[channel_locale_specific][default][en_US][name]"] + p.text-red-600')).toHaveText('The Name field is required');
    await expect(page.locator('input[name="uniqueFields[values.common.url_key]"] + p.text-red-600')).toHaveText('The URL Key field is required');
    await expect(page.getByText('The Short Description field is required')).toBeVisible();
    await expect(page.getByText('The Description field is required')).toBeVisible();
    await expect(page.getByText('The Price field is required')).toBeVisible();
  });

  test('Update simple product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('input[name="values[common][product_number]"]').click();
    await page.locator('input[name="values[common][product_number]"]').fill('456');
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').click();
    await page.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').type('Acer Laptop', { delay: 100 });
    await page.locator('input[name="values[common][url_key]"]').click();
    await page.locator('input[name="values[common][url_key]"]').type('laptop', { delay: 100 });
    await page.locator('input[name="values[common][color]"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'White' }).locator('span').first().click();
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This laptop is best in the market', { delay: 100 });
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the ACER Laptop with high functionality', { delay: 100 });
    await page.locator('#meta_title').fill('thakubali');
    await page.locator('#price').click();
    await page.locator('#price').fill('40000');
    await page.locator('.relative > .rounded-full').click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText(/Product updated successfully/i)).toBeVisible();
  });

  test('Delete simple product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = await page.locator('div', { hasText: 'acer456' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/Product deleted successfully/i)).toBeVisible();
  });

  test('Create Configurable Product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('button', { name: 'Create Product' }).click();
    await page.locator('div').filter({ hasText: /^Select option$/ }).first().click();
    await page.getByRole('option', { name: 'Configurable' }).locator('span').first().click();
    await page.locator('div').filter({ hasText: /^Select option$/ }).click();
    await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await page.locator('input[name="sku"]').click();
    await page.locator('input[name="sku"]').fill('realme1245');
    await page.getByRole('button', { name: 'Save Product' }).click();
    await page.getByRole('paragraph').filter({ hasText: 'Brand' }).locator('span').click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText(/Product created successfully/i)).toBeVisible();
  });

  test('Update Configurable Product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = await page.locator('div', { hasText: 'realme1245' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.locator('#product_number').click();
    await page.locator('#product_number').type('12345', { delay: 100 });
    await page.locator('#name').click();
    await page.locator('#name').type('Realme 7pro', { delay: 100 });
    await page.locator('#url_key').click();
    await page.locator('#url_key').type('Mobile', { delay: 100 });
    const shortDescFrame = page.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This smart phone is best in the market', { delay: 100 });
    const mainDescFrame = page.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the Realme 7pro phone with 7500mah batttery and 200mp camera', { delay: 100 });
    await page.locator('#meta_title').click();
    await page.locator('#meta_title').fill('best mobile');
    await page.locator('#price').click();
    await page.locator('#price').fill('25000');
    await page.locator('.relative > .rounded-full').click();
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText(/Product updated successfully/i)).toBeVisible();
  });

  test('Delete configurable product', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    const itemRow = await page.locator('div', { hasText: 'realme1245' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await page.click('button:has-text("Delete")');
    await expect(page.getByText(/Product deleted successfully/i)).toBeVisible();
  });
});
