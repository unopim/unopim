const { test, expect } = require('../../utils/fixtures');

async function navigateToAttributes(adminPage) {
  await adminPage.goto('/admin/catalog/attributes', { waitUntil: 'domcontentloaded' });
}

async function deleteAttributeIfExists(adminPage, code) {
  await navigateToAttributes(adminPage);
  const searchBox = adminPage.getByRole('textbox', { name: 'Search' });
  await searchBox.fill(code);
  await searchBox.press('Enter');
  await adminPage.waitForLoadState('networkidle');

  const deleteBtn = adminPage
    .locator('div', { hasText: code })
    .locator('span[title="Delete"]')
    .first();

  if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Deleted Successfully')).toBeVisible({ timeout: 10000 });
  }
}

test.describe('UnoPim Attribute', () => {
  test('Create attribute with empty code field', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option', { name: 'Text' }).first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create attribute with empty Type field', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('product_name');
    await adminPage.locator('input[name="en_US\\[name\\]"]').click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
  });

  test('Create attribute with empty Code and Type field', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('input[name="en_US\\[name\\]"]').click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
  });

  test('Create attribute', { timeout: 60000 }, async ({ adminPage }) => {
    await deleteAttributeIfExists(adminPage, 'product_name');
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('product_name');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option', { name: 'Text' }).first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
  });

  test('should allow attribute search', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('textbox', { name: 'Search' }).click();
    await adminPage.getByRole('textbox', { name: 'Search' }).type('product');
    await adminPage.keyboard.press('Enter');
    await expect(adminPage.locator('text=product_name', { exact: true })).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per adminPage', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a attribute (Edit, Delete)', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'product_name' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributes\/edit/);
    await adminPage.goBack();
    await adminPage.waitForLoadState('networkidle');
    await itemRow.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
  });

  test('should allow selecting all attribute with the mass action checkbox', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.click('label[for="mass_action_select_all_records"]');
    await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
  });

  test('Update attribute', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'product_name' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('prudact nem');
    await adminPage.locator('#is_required').nth(1).click();
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Updated Successfully/i)).toBeVisible();
  });

  test('Delete Attribute', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByText('product_nameprudact nem').getByTitle('Delete').click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Deleted Successfully/i)).toBeVisible();
  });
});

test.describe('Checkbox Type Attribute Option Grid', () => {
  test('Adding options should not be visible while creating the attribute (checkbox type)', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('in_the_box');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('checkbox');
    await adminPage.getByRole('option', { name: 'Checkbox' }).first().click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('In the Box');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).not.toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).not.toBeVisible();
  });

  test('create the checkbox type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    await deleteAttributeIfExists(adminPage, 'in_the_box');
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('in_the_box');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('checkbox');
    await adminPage.getByRole('option', { name: 'Checkbox' }).first().click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('In the Box');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
  });

  test('Edit and add the options in the checkbox type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('adapter');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Adapter');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('cable');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Cable');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('instruction_manual');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Instruction Manual');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('phone_cover');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Phone Cover');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
  });

  test('check the search bar of attribute options datagrid', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('cable');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await expect(adminPage.locator('#app').getByText('cableCable')).toBeVisible();
  });

  test('should allow setting items per adminPage', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a attribute option (Edit, Delete)', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const itemRow1 = adminPage.locator('div', { hasText: 'cableCable' });
    await itemRow1.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('span.icon-cancel.cursor-pointer').click();
    await itemRow1.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
  });

  test('Pagination buttons should be visible, enabled, and clickable', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const paginationSymbols = ['«', '‹', '›', '»'];
    for (const symbol of paginationSymbols) {
      const button = adminPage.getByText(symbol, { exact: true });
      await expect(button).toBeVisible();
      await expect(button).toBeEnabled();
      await button.click();
      await adminPage.waitForLoadState('networkidle');
    }
  });

  test('Delete the checkbox type attibute', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'in_the_box' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});

test.describe('Multiselect Type Attribute Options Grid', () => {
  test('Adding options should not be visible while creating the attribute (multiselect type)', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('features');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Multiselect');
    await adminPage.getByRole('option', { name: 'Multiselect' }).first().click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('Features');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).not.toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).not.toBeVisible();
  });

  test('create the multiselect type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    await deleteAttributeIfExists(adminPage, 'features');
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('features');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Multiselect');
    await adminPage.getByRole('option', { name: 'Multiselect' }).first().click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('Features');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
  });

  test('Edit and add the options in the multiselect type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('waterproof');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Waterproof');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('bluetooth');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Bluetooth');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('rechargeable');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Rechargeable');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('charger');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Charger');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
  });

  test('check the search bar of attribute options datagrid', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('charger');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await expect(adminPage.locator('#app').getByText('chargerCharger')).toBeVisible();
  });

  test('should allow setting items per adminPage', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a attribute option (Edit, Delete)', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'featuresfeatures' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const itemRow1 = adminPage.locator('div', { hasText: 'chargerCharger' });
    await itemRow1.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('span.icon-cancel.cursor-pointer').click();
    await itemRow1.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
  });

  test('Pagination buttons should be visible, enabled, and clickable', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const paginationSymbols = ['«', '‹', '›', '»'];
    for (const symbol of paginationSymbols) {
      const button = adminPage.getByText(symbol, { exact: true });
      await expect(button).toBeVisible();
      await expect(button).toBeEnabled();
      await button.click();
      await adminPage.waitForLoadState('networkidle');
    }
  });

  test('Delete the multiselect type attibute', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'featuresFeatures' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});


test.describe('Select Type Attribute', () => {
  test('Adding options should not be visible while creating the attribute (select type)', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('material');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('Material');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).not.toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).not.toBeVisible();
  });

  test('create the Select type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    await deleteAttributeIfExists(adminPage, 'material');
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('material');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('Material');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
  });

  test('Edit and add the options in the Select type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('cotton');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Cotton');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('fabric');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Fabric');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('leather');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Leather');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('polyester');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Polyester');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Close').click();
  });

  test('check the search bar of attribute options datagrid', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('cotton');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await expect(adminPage.locator('#app').getByText('cottonCotton')).toBeVisible();
  });

  test('should allow setting items per adminPage', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a attribute option (Edit, Delete)', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const itemRow1 = adminPage.locator('div', { hasText: 'cottonCotton' });
    await itemRow1.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('span.icon-cancel.cursor-pointer').click();
    await itemRow1.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
  });

  test('Pagination buttons should be visible, enabled, and clickable', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const paginationSymbols = ['«', '‹', '›', '»'];
    for (const symbol of paginationSymbols) {
      const button = adminPage.getByText(symbol, { exact: true });
      await expect(button).toBeVisible();
      await expect(button).toBeEnabled();
      await button.click();
      await adminPage.waitForLoadState('networkidle');
    }
  });

  test('Delete the Select type attibute', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});


test.describe('Swatch Type Attribute Option', () => {
  test('Check swatch type visibility on Select attribute creation', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('swatch');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).click();
  });

  test('Check the swatch type options for select type attribute', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('swatch');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Color Swatch' }).click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Image Swatch' }).click();
  });

  test('Verify swatch type field have default value as Text Swatch', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('swatch');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    const swatchType = await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).innerText();
    expect(swatchType).toBe('Text Swatch');
  });

  test('Create a select type attribute with swatch type as text swatch', { timeout: 60000 }, async ({ adminPage }) => {
    await deleteAttributeIfExists(adminPage, 'text_swatch');
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('text_swatch');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    const swatchType = await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).innerText();
    expect(swatchType).toBe('Text Swatch');
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('Text Swatch');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
  });

  test('Verify swatch type field is visible and selected swatch type is visible while editing the select type attribute', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'text_swatchText Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const swatchTypeInput = await adminPage.locator('input[name="swatch_type"][type="text"]');
    await expect(swatchTypeInput).toBeDisabled();
    const hiddenInput = await adminPage.locator('input[name="swatch_type"][type="hidden"]');
    await expect(hiddenInput).toHaveValue('text');
  });

  test('Edit and add the options in the Select type attibute with swatch type as text swatch', { timeout: 60000 }, async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'text_swatchText Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('red');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Red');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('blue');
    await adminPage.locator('input[name="locales.en_US"]').click();
    await adminPage.locator('input[name="locales.en_US"]').fill('Blue');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Updated Successfully/i)).toBeVisible();
  });

  test('Delete the text swatch attribute option', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'text_swatchText Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const itemRow1 = adminPage.locator('div', { hasText: 'redRed' });
    await itemRow1.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Deleted Successfully')).toBeVisible();
  });

  test('Create the select type attribute with swatch type as color swatch', { timeout: 60000 }, async ({ adminPage }) => {
    await deleteAttributeIfExists(adminPage, 'color_swatch');
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('color_swatch');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).click();
    await adminPage.getByRole('option', { name: 'Color Swatch' }).first().click();
    await expect(adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Color Swatch' }).first()).toBeVisible();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('Color Swatch');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
  });

  test('Verify swatch type field is visible and selected swatch type is visible while editing the select type attribute with color swatch', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'color_swatchColor Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const swatchTypeInput = await adminPage.locator('input[name="swatch_type"][type="text"]');
    await expect(swatchTypeInput).toBeDisabled();
    const hiddenInput = await adminPage.locator('input[name="swatch_type"][type="hidden"]');
    await expect(hiddenInput).toHaveValue('color');
  });

  test('Edit and add the options in the Select type attibute with swatch type as color swatch', { timeout: 60000 }, async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'color_swatchColor Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await adminPage.locator('input[name="swatch_value"]').fill('#ff0000');
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('red');
    await adminPage.locator('input[name="locales\\.en_US"]').click();
    await adminPage.locator('input[name="locales\\.en_US"]').fill('Red');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await adminPage.locator('input[name="swatch_value"]').fill('#00faf6');
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('aqua_blue');
    await adminPage.locator('input[name="locales\\.en_US"]').click();
    await adminPage.locator('input[name="locales\\.en_US"]').fill('Aqua Blue');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Updated Successfully/i)).toBeVisible();
  });

  test('Delete the color swatch attribute option', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'color_swatchColor Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const itemRow1 = adminPage.locator('div', { hasText: 'redRed' });
    await itemRow1.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Deleted Successfully')).toBeVisible();
  });

  test('Create the select type attribute with swatch type as image swatch', { timeout: 60000 }, async ({ adminPage }) => {
    await deleteAttributeIfExists(adminPage, 'image_swatch');
    await navigateToAttributes(adminPage);
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('image_swatch');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).click();
    await adminPage.getByRole('option', { name: 'Image Swatch' }).first().click();
    await expect(adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Image Swatch' }).first()).toBeVisible();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('Image Swatch');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
  });

  test('Verify swatch type field is visible and selected swatch type is visible while editing the select type attribute with image swatch', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'image_swatchImage Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const swatchTypeInput = await adminPage.locator('input[name="swatch_type"][type="text"]');
    await expect(swatchTypeInput).toBeDisabled();
    const hiddenInput = await adminPage.locator('input[name="swatch_type"][type="hidden"]');
    await expect(hiddenInput).toHaveValue('image');
  });

  test('Edit and add the options in the Select type attibute with swatch type as image swatch', { timeout: 60000 }, async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'image_swatchImage Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await adminPage.getByText('Add Row').click();
    const fileInput = adminPage.locator('label', { hasText: 'Add Image' }).nth(1).locator('input[type="file"]');
    await fileInput.setInputFiles('assets/floral.jpg');
    const Code = adminPage.getByRole('textbox', { name: 'Code' }).nth(1);
    await (Code).fill('floral_pattern');
    await adminPage.locator('input[name="locales\\.en_US"]').click();
    await adminPage.locator('input[name="locales\\.en_US"]').fill('Floral Pattern');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully').last()).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await fileInput.setInputFiles('assets/stripes.jpg');
    await (Code).fill('stripes_pattern');
    await adminPage.locator('input[name="locales\\.en_US"]').click();
    await adminPage.locator('input[name="locales\\.en_US"]').fill('Stripes Pattern');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully').last()).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await fileInput.setInputFiles('assets/dotted.png');
    await (Code).fill('dots_pattern');
    await adminPage.locator('input[name="locales\\.en_US"]').click();
    await adminPage.locator('input[name="locales\\.en_US"]').fill('Dots Pattern');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully').last()).toBeVisible();
    await adminPage.getByText('Add Row').click();
    await fileInput.setInputFiles('assets/check.jpeg');
    await (Code).fill('checked_pattern');
    await adminPage.locator('input[name="locales\\.en_US"]').click();
    await adminPage.locator('input[name="locales\\.en_US"]').fill('Checked Pattern');
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully').last()).toBeVisible();
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Updated Successfully/i)).toBeVisible();
  });

  test('Delete the image swatch attribute option', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'image_swatchImage Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    const searchBox = adminPage.getByRole('textbox', { name: 'Search', exact: true });
    await searchBox.fill('dots');
    await searchBox.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow1 = adminPage.locator('div', { hasText: 'dots_patternDots Pattern' });
    await itemRow1.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Deleted Successfully')).toBeVisible();
  });

  test('Check the search bar of attribute options datagrid for swatch type attribute', async ({ adminPage }) => {
    await navigateToAttributes(adminPage);
    const itemRow = adminPage.locator('div', { hasText: 'image_swatchImage Swatch' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('floral');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await expect(adminPage.locator('#app').getByText('floral_patternFloral Pattern')).toBeVisible();
  });
});
