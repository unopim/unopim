const { test, expect } = require('../../utils/fixtures');
test.describe('UnoPim Attribute', () => {
test('Create attribute with empty code field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
});

test('Create attribute with empty Type field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product_name');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText('The Type field is required')).toBeVisible();
});

test('Create attribute with empty Code and Type field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
  await expect(adminPage.getByText('The Type field is required')).toBeVisible();
});

test('Create attribute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product_name');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i)).toBeVisible();
});

test('should allow attribute search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('product');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=product_name', {exact:true})).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a category (Edit, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'product_name' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributes\/edit/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('should allow selecting all category with the mass action checkbox', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.click('label[for="mass_action_select_all_records"]');
  await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
});

test('Update attribute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'product_name' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('prudact nem');
  await adminPage.locator('#is_required').nth(1).click();
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Updated Successfully/i)).toBeVisible();
});

test('Delete Attribute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByText('product_nameprudact nem').getByTitle('Delete').click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Attribute Deleted Successfully/i)).toBeVisible();
});
});

test.describe('Checkbox Type Attribute Option Grid', () => {

test('Adding options should not be visible while creating the attribute (checkbox type)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('in_the_box');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('checkbox');
  await adminPage.getByRole('option', { name: 'Checkbox' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US[name]"]').click();
  await adminPage.locator('input[name="en_US[name]"]').fill('In the Box');
  await expect(adminPage.getByText('Options', { exact: true })).not.toBeVisible();
  await expect(adminPage.getByText('Add Row')).not.toBeVisible();
});

test('create the checkbox type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('in_the_box');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('checkbox');
  await adminPage.getByRole('option', { name: 'Checkbox' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US[name]"]').click();
  await adminPage.locator('input[name="en_US[name]"]').fill('In the Box');
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i).first()).toBeVisible();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
});

test('Edit and add the options in the checkbox type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('adapter');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Adapter');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('cable');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Cable');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('instruction_manual');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Instruction Manual');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('phone_cover');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Phone Cover');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
});
    
test('check the search bar of attribute options datagrid', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('cable');
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
  await expect(adminPage.getByText('cableCable')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a attribute option (Edit, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
  await itemRow.locator('span[title="Edit"]').first().click();
  const itemRow1 = adminPage.locator('div', { hasText: 'cableCable' });
  await itemRow1.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('span.icon-cancel.cursor-pointer').click();
  await itemRow1.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Pagination buttons should be visible, enabled, and clickable', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
  await itemRow.locator('span[title="Edit"]').first().click();
  const paginationSymbols = ['«', '‹', '›', '»'];
  for (const symbol of paginationSymbols) {
  const button = adminPage.getByText(symbol, { exact: true });
  await expect(button).toBeVisible();
  await expect(button).toBeEnabled();
  await button.click();
  await adminPage.waitForTimeout(300);
  }
});

test('Delete the checkbox type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});

test.describe('Multiselect Type Attribute Options Grid', () => {

test('Adding options should not be visible while creating the attribute (multiselect type)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('features');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Multiselect');
  await adminPage.getByRole('option', { name: 'Multiselect' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US[name]"]').click();
  await adminPage.locator('input[name="en_US[name]"]').fill('Features');
  await expect(adminPage.getByText('Options', { exact: true })).not.toBeVisible();
  await expect(adminPage.getByText('Add Row')).not.toBeVisible();
});

test('create the multiselect type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('features');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Multiselect');
  await adminPage.getByRole('option', { name: 'Multiselect' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US[name]"]').click();
  await adminPage.locator('input[name="en_US[name]"]').fill('Features');
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i).first()).toBeVisible();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
});

test('Edit and add the options in the multiselect type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('waterproof');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Waterproof');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('bluetooth');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Bluetooth');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('rechargeable');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Rechargeable');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('charger');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Charger');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
});

test('check the search bar of attribute options datagrid', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('charger');
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
  await expect(adminPage.getByText('chargerCharger')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a attribute option (Edit, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'featuresfeatures' });
  await itemRow.locator('span[title="Edit"]').first().click();
  const itemRow1 = adminPage.locator('div', { hasText: 'chargerCharger' });
  await itemRow1.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('span.icon-cancel.cursor-pointer').click();
  await itemRow1.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Pagination buttons should be visible, enabled, and clickable', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
  await itemRow.locator('span[title="Edit"]').first().click();
  const paginationSymbols = ['«', '‹', '›', '»'];
  for (const symbol of paginationSymbols) {
  const button = adminPage.getByText(symbol, { exact: true });
  await expect(button).toBeVisible();
  await expect(button).toBeEnabled();
  await button.click();
  await adminPage.waitForTimeout(300);
  }
});

test('Delete the multiselect type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', {name: 'Delete'}).click();
  await expect(adminPage.getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});


test.describe('Select Type Attribute', () => {

test('Adding options should not be visible while creating the attribute (select type)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('material');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Select');
  await adminPage.getByRole('option', { name: 'Select' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US[name]"]').click();
  await adminPage.locator('input[name="en_US[name]"]').fill('Material');
  await expect(adminPage.getByText('Options', { exact: true })).not.toBeVisible();
  await expect(adminPage.getByText('Add Row')).not.toBeVisible();
});

test('create the Select type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('material');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Select');
  await adminPage.getByRole('option', { name: 'Select' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US[name]"]').click();
  await adminPage.locator('input[name="en_US[name]"]').fill('Material');
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i).first()).toBeVisible();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
});

test('Edit and add the options in the Select type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('cotton');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Cotton');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('fabric');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Fabric');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('leather');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Leather');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('polyester');
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').fill('Polyester');
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').click();
});

test('check the search bar of attribute options datagrid', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Options', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('Add Row')).toBeVisible();
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('cotton');
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
  await expect(adminPage.getByText('cottonCotton')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a attribute option (Edit, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  const itemRow1 = adminPage.locator('div', { hasText: 'cottonCotton' });
  await itemRow1.locator('span[title="Edit"]').first().click();
  await expect(adminPage.getByText('Add Option')).toBeVisible();
  await adminPage.locator('span.icon-cancel.cursor-pointer').click();
  await itemRow1.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Pagination buttons should be visible, enabled, and clickable', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  const paginationSymbols = ['«', '‹', '›', '»'];
  for (const symbol of paginationSymbols) {
  const button = adminPage.getByText(symbol, { exact: true });
  await expect(button).toBeVisible();
  await expect(button).toBeEnabled();
  await button.click();
  await adminPage.waitForTimeout(300);
  }
});

test('Delete the Select type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', {name: 'Delete'}).click();
  await expect(adminPage.getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});