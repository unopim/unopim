const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create an attribute via UI and land on the edit page.
 * @param {import('@playwright/test').Page} adminPage
 * @param {string} code
 * @param {string} name
 * @param {string} type - e.g. 'Text', 'Checkbox', 'Multiselect', 'Select'
 */
async function createAttribute(adminPage, code, name, type = 'Text') {
  await navigateTo(adminPage, 'attributes');
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill(type);
  await adminPage.getByRole('option', { name: type }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(name);
  await Promise.all([
    adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
    adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
  ]);
  await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
}

/**
 * Helper: Create a select-type attribute with a specific swatch type.
 * @param {import('@playwright/test').Page} adminPage
 * @param {string} code
 * @param {string} name
 * @param {string} swatchType - 'Text Swatch', 'Color Swatch', or 'Image Swatch'
 */
async function createSelectSwatchAttribute(adminPage, code, name, swatchType = 'Text Swatch') {
  await navigateTo(adminPage, 'attributes');
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Select');
  await adminPage.getByRole('option', { name: 'Select' }).first().click();
  if (swatchType !== 'Text Swatch') {
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).click();
    await adminPage.getByRole('option', { name: swatchType }).first().click();
  }
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(name);
  await Promise.all([
    adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
    adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
  ]);
  await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
}

/**
 * Helper: Search for an attribute by code on the attributes listing page.
 */
async function searchAttribute(adminPage, code) {
  await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
}

/**
 * Helper: Navigate to attributes, search for one, and click Edit.
 */
async function searchAndEditAttribute(adminPage, code) {
  await navigateTo(adminPage, 'attributes');
  await searchAttribute(adminPage, code);
  const row = adminPage.locator('div', { hasText: code });
  await row.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('networkidle');
}

/**
 * Helper: Delete an attribute by code (navigate, search, delete, confirm).
 * Silently succeeds if the attribute doesn't exist.
 */
async function deleteAttribute(adminPage, code) {
  await navigateTo(adminPage, 'attributes');
  await searchAttribute(adminPage, code);
  const deleteBtn = adminPage.locator('div', { hasText: code }).locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
}

/**
 * Helper: Add an option to an attribute currently open in the edit page.
 * @param {import('@playwright/test').Page} adminPage
 * @param {string} optionCode
 * @param {string} optionLabel
 */
async function addOption(adminPage, optionCode, optionLabel) {
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill(optionCode);
  await adminPage.locator('input[name="locales.en_US"]').fill(optionLabel);
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
  await adminPage.getByText('Close').first().click();
}

/**
 * Helper: Add a color swatch option (uses locales\\.en_US selector).
 */
async function addColorSwatchOption(adminPage, optionCode, optionLabel, color) {
  await adminPage.getByText('Add Row').click();
  await adminPage.locator('input[name="swatch_value"]').fill(color);
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill(optionCode);
  await adminPage.locator('input[name="locales\\.en_US"]').fill(optionLabel);
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
}

/**
 * Helper: Add a text swatch option (uses locales.en_US selector — no backslash).
 */
async function addTextSwatchOption(adminPage, optionCode, optionLabel) {
  await adminPage.getByText('Add Row').click();
  await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
  await adminPage.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill(optionCode);
  await adminPage.locator('input[name="locales.en_US"]').fill(optionLabel);
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully')).toBeVisible();
}

/**
 * Helper: Add an image swatch option.
 */
async function addImageSwatchOption(adminPage, optionCode, optionLabel, imagePath) {
  await adminPage.getByText('Add Row').click();
  const fileInput = adminPage.locator('label', { hasText: 'Add Image' }).nth(1).locator('input[type="file"]');
  await fileInput.setInputFiles(imagePath);
  const codeInput = adminPage.getByRole('textbox', { name: 'Code' }).nth(1);
  await codeInput.fill(optionCode);
  await adminPage.locator('input[name="locales\\.en_US"]').fill(optionLabel);
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.locator('#app').getByText('Attribute Option Created Successfully').last()).toBeVisible();
}

// ============================================================================
// DESCRIBE BLOCK 1: UnoPim Attribute — basic CRUD for text attributes
// ============================================================================
test.describe('UnoPim Attribute', () => {

  test('Create attribute with empty code field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option', { name: 'Text' }).first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create attribute with empty Type field', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `attr_${uid}`;
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
  });

  test('Create attribute with empty Code and Type field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
    await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
  });

  test('Create attribute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `attr_${uid}`;
    await createAttribute(adminPage, code, 'Product Name', 'Text');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('should allow attribute search', async ({ adminPage }) => {
    // Use seeded 'sku' attribute — always exists
    await navigateTo(adminPage, 'attributes');
    await searchAttribute(adminPage, 'sku');
    await expect(adminPage.locator('#app').getByText('sku', { exact: true }).first()).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per adminPage', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributes');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.locator('#app').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a attribute (Edit, Delete)', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `attr_${uid}`;
    await createAttribute(adminPage, code, 'Actions Test', 'Text');

    // Search and verify Edit action
    await navigateTo(adminPage, 'attributes');
    await searchAttribute(adminPage, code);
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributes\/edit/);

    // Go back and verify Delete action shows confirmation
    await navigateTo(adminPage, 'attributes');
    await searchAttribute(adminPage, code);
    const itemRow2 = adminPage.locator('div', { hasText: code });
    await itemRow2.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();

    // Cleanup — confirm delete
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Deleted Successfully/i)).toBeVisible();
  });

  test('should allow selecting all attribute with the mass action checkbox', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributes');
    await adminPage.click('label[for="mass_action_select_all_records"]');
    await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
  });

  test('Update attribute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `attr_${uid}`;
    await createAttribute(adminPage, code, 'Before Update', 'Text');

    // Search and edit
    await navigateTo(adminPage, 'attributes');
    await searchAttribute(adminPage, code);
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('prudact nem');
    await adminPage.locator('#is_required').nth(1).click();
    await clickSaveAndExpect(adminPage, 'Save Attribute', /Attribute Updated Successfully/i);

    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Delete Attribute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `attr_${uid}`;
    await createAttribute(adminPage, code, 'To Delete', 'Text');

    // Search and delete
    await navigateTo(adminPage, 'attributes');
    await searchAttribute(adminPage, code);
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Deleted Successfully/i)).toBeVisible();
  });
});


// ============================================================================
// DESCRIBE BLOCK 2: Checkbox Type Attribute Option Grid
// ============================================================================
test.describe('Checkbox Type Attribute Option Grid', () => {

  test('Adding options should not be visible while creating the attribute (checkbox type)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chk_${uid}`;
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('checkbox');
    await adminPage.getByRole('option', { name: 'Checkbox' }).first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('In the Box');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).not.toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).not.toBeVisible();
  });

  test('create the checkbox type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chk_${uid}`;
    await createAttribute(adminPage, code, 'In the Box', 'Checkbox');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Edit and add the options in the checkbox type attibute', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chk_${uid}`;
    await createAttribute(adminPage, code, 'In the Box', 'Checkbox');
    await addOption(adminPage, `adapter_${uid}`, 'Adapter');
    await addOption(adminPage, `cable_${uid}`, 'Cable');
    await addOption(adminPage, `manual_${uid}`, 'Instruction Manual');
    await addOption(adminPage, `cover_${uid}`, 'Phone Cover');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('check the search bar of attribute options datagrid', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chk_${uid}`;
    const optCode = `cable_${uid}`;
    await createAttribute(adminPage, code, 'In the Box', 'Checkbox');
    await addOption(adminPage, optCode, 'Cable');
    // Search for the option
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(optCode);
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(optCode, { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Cable', { exact: true })).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('should allow setting items per adminPage', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chk_${uid}`;
    await createAttribute(adminPage, code, 'In the Box', 'Checkbox');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.locator('#app').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('should perform actions on a attribute option (Edit, Delete)', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chk_${uid}`;
    const optCode = `cable_${uid}`;
    await createAttribute(adminPage, code, 'In the Box', 'Checkbox');
    await addOption(adminPage, optCode, 'Cable');
    // Edit action
    const itemRow1 = adminPage.locator('div', { hasText: optCode }).filter({ hasText: 'Cable' });
    await itemRow1.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('span.icon-cancel.cursor-pointer').click();
    // Delete action
    await itemRow1.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();
    // Cleanup — dismiss modal and delete the attribute
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
    await deleteAttribute(adminPage, code);
  });

  test('Pagination buttons should be visible, enabled, and clickable', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chk_${uid}`;
    await createAttribute(adminPage, code, 'In the Box', 'Checkbox');
    await addOption(adminPage, `opt1_${uid}`, 'Option 1');
    await addOption(adminPage, `opt2_${uid}`, 'Option 2');
    await addOption(adminPage, `opt3_${uid}`, 'Option 3');
    await addOption(adminPage, `opt4_${uid}`, 'Option 4');
    const paginationSymbols = ['«', '‹', '›', '»'];
    for (const symbol of paginationSymbols) {
      const button = adminPage.getByText(symbol, { exact: true });
      await expect(button).toBeVisible();
      await expect(button).toBeEnabled();
      await button.click();
      await adminPage.waitForLoadState('networkidle');
    }
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Delete the checkbox type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chk_${uid}`;
    await createAttribute(adminPage, code, 'In the Box', 'Checkbox');
    // Navigate back to listing and delete
    await navigateTo(adminPage, 'attributes');
    await searchAttribute(adminPage, code);
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});


// ============================================================================
// DESCRIBE BLOCK 3: Multiselect Type Attribute Options Grid
// ============================================================================
test.describe('Multiselect Type Attribute Options Grid', () => {

  test('Adding options should not be visible while creating the attribute (multiselect type)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `msel_${uid}`;
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Multiselect');
    await adminPage.getByRole('option', { name: 'Multiselect' }).first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Features');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).not.toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).not.toBeVisible();
  });

  test('create the multiselect type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `msel_${uid}`;
    await createAttribute(adminPage, code, 'Features', 'Multiselect');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Edit and add the options in the multiselect type attibute', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `msel_${uid}`;
    await createAttribute(adminPage, code, 'Features', 'Multiselect');
    await addOption(adminPage, `waterproof_${uid}`, 'Waterproof');
    await addOption(adminPage, `bluetooth_${uid}`, 'Bluetooth');
    await addOption(adminPage, `rechargeable_${uid}`, 'Rechargeable');
    await addOption(adminPage, `charger_${uid}`, 'Charger');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('check the search bar of attribute options datagrid', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `msel_${uid}`;
    const optCode = `charger_${uid}`;
    await createAttribute(adminPage, code, 'Features', 'Multiselect');
    await addOption(adminPage, optCode, 'Charger');
    // Search for the option
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(optCode);
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(optCode, { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Charger', { exact: true })).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('should allow setting items per adminPage', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `msel_${uid}`;
    await createAttribute(adminPage, code, 'Features', 'Multiselect');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.locator('#app').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('should perform actions on a attribute option (Edit, Delete)', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `msel_${uid}`;
    const optCode = `charger_${uid}`;
    await createAttribute(adminPage, code, 'Features', 'Multiselect');
    await addOption(adminPage, optCode, 'Charger');
    // Edit action
    const itemRow1 = adminPage.locator('div', { hasText: optCode }).filter({ hasText: 'Charger' });
    await itemRow1.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('span.icon-cancel.cursor-pointer').click();
    // Delete action
    await itemRow1.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();
    // Cleanup — confirm delete of option, then delete attribute
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
    await deleteAttribute(adminPage, code);
  });

  test('Pagination buttons should be visible, enabled, and clickable', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `msel_${uid}`;
    await createAttribute(adminPage, code, 'Features', 'Multiselect');
    await addOption(adminPage, `opt1_${uid}`, 'Option 1');
    await addOption(adminPage, `opt2_${uid}`, 'Option 2');
    await addOption(adminPage, `opt3_${uid}`, 'Option 3');
    await addOption(adminPage, `opt4_${uid}`, 'Option 4');
    const paginationSymbols = ['«', '‹', '›', '»'];
    for (const symbol of paginationSymbols) {
      const button = adminPage.getByText(symbol, { exact: true });
      await expect(button).toBeVisible();
      await expect(button).toBeEnabled();
      await button.click();
      await adminPage.waitForLoadState('networkidle');
    }
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Delete the multiselect type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `msel_${uid}`;
    await createAttribute(adminPage, code, 'Features', 'Multiselect');
    // Navigate back to listing and delete
    await navigateTo(adminPage, 'attributes');
    await searchAttribute(adminPage, code);
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});


// ============================================================================
// DESCRIBE BLOCK 4: Select Type Attribute
// ============================================================================
test.describe('Select Type Attribute', () => {

  test('Adding options should not be visible while creating the attribute (select type)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `sel_${uid}`;
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Material');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).not.toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).not.toBeVisible();
  });

  test('create the Select type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `sel_${uid}`;
    await createAttribute(adminPage, code, 'Material', 'Select');
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Edit and add the options in the Select type attibute', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `sel_${uid}`;
    await createAttribute(adminPage, code, 'Material', 'Select');
    await addOption(adminPage, `cotton_${uid}`, 'Cotton');
    await addOption(adminPage, `fabric_${uid}`, 'Fabric');
    await addOption(adminPage, `leather_${uid}`, 'Leather');
    await addOption(adminPage, `polyester_${uid}`, 'Polyester');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('check the search bar of attribute options datagrid', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `sel_${uid}`;
    const optCode = `cotton_${uid}`;
    await createAttribute(adminPage, code, 'Material', 'Select');
    await addOption(adminPage, optCode, 'Cotton');
    // Search for the option
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(optCode);
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(optCode, { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Cotton', { exact: true })).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('should allow setting items per adminPage', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `sel_${uid}`;
    await createAttribute(adminPage, code, 'Material', 'Select');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.locator('#app').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('should perform actions on a attribute option (Edit, Delete)', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `sel_${uid}`;
    const optCode = `cotton_${uid}`;
    await createAttribute(adminPage, code, 'Material', 'Select');
    await addOption(adminPage, optCode, 'Cotton');
    // Edit action
    const itemRow1 = adminPage.locator('div', { hasText: optCode }).filter({ hasText: 'Cotton' });
    await itemRow1.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('#app').getByText('Add Option')).toBeVisible();
    await adminPage.locator('span.icon-cancel.cursor-pointer').click();
    // Delete action
    await itemRow1.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();
    // Cleanup — confirm delete of option, then delete attribute
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
    await deleteAttribute(adminPage, code);
  });

  test('Pagination buttons should be visible, enabled, and clickable', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `sel_${uid}`;
    await createAttribute(adminPage, code, 'Material', 'Select');
    await addOption(adminPage, `opt1_${uid}`, 'Option 1');
    await addOption(adminPage, `opt2_${uid}`, 'Option 2');
    await addOption(adminPage, `opt3_${uid}`, 'Option 3');
    await addOption(adminPage, `opt4_${uid}`, 'Option 4');
    const paginationSymbols = ['«', '‹', '›', '»'];
    for (const symbol of paginationSymbols) {
      const button = adminPage.getByText(symbol, { exact: true });
      await expect(button).toBeVisible();
      await expect(button).toBeEnabled();
      await button.click();
      await adminPage.waitForLoadState('networkidle');
    }
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Delete the Select type attibute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `sel_${uid}`;
    await createAttribute(adminPage, code, 'Material', 'Select');
    // Navigate back to listing and delete
    await navigateTo(adminPage, 'attributes');
    await searchAttribute(adminPage, code);
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});


// ============================================================================
// DESCRIBE BLOCK 5: Swatch Type Attribute Option
// ============================================================================
test.describe('Swatch Type Attribute Option', () => {

  test('Check swatch type visibility on Select attribute creation', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('swatch_vis_check');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).click();
  });

  test('Check the swatch type options for select type attribute', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('swatch_opt_check');
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
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('swatch_default');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    const swatchType = await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).innerText();
    expect(swatchType).toBe('Text Swatch');
  });

  test('Create a select type attribute with swatch type as text swatch', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `tsw_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Text Swatch', 'Text Swatch');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Verify swatch type field is visible and selected swatch type is visible while editing the select type attribute', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `tsw_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Text Swatch', 'Text Swatch');
    // Navigate to edit page
    await searchAndEditAttribute(adminPage, code);
    const swatchTypeInput = adminPage.locator('input[name="swatch_type"][type="text"]');
    await expect(swatchTypeInput).toBeDisabled();
    const hiddenInput = adminPage.locator('input[name="swatch_type"][type="hidden"]');
    await expect(hiddenInput).toHaveValue('text');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Edit and add the options in the Select type attibute with swatch type as text swatch', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `tsw_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Text Swatch', 'Text Swatch');
    // Navigate to edit page to add options
    await searchAndEditAttribute(adminPage, code);
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await addTextSwatchOption(adminPage, `red_${uid}`, 'Red');
    await addTextSwatchOption(adminPage, `blue_${uid}`, 'Blue');
    await clickSaveAndExpect(adminPage, 'Save Attribute', /Attribute Updated Successfully/i);
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Delete the text swatch attribute option', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `tsw_${uid}`;
    const optCode = `red_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Text Swatch', 'Text Swatch');
    // Navigate to edit page and add an option to delete
    await searchAndEditAttribute(adminPage, code);
    await addTextSwatchOption(adminPage, optCode, 'Red');
    // Delete the option
    const itemRow1 = adminPage.locator('div', { hasText: optCode }).filter({ hasText: 'Red' });
    await itemRow1.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Deleted Successfully')).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Create the select type attribute with swatch type as color swatch', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `csw_${uid}`;
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).click();
    await adminPage.getByRole('option', { name: 'Color Swatch' }).first().click();
    await expect(adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Color Swatch' }).first()).toBeVisible();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Color Swatch');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Verify swatch type field is visible and selected swatch type is visible while editing the select type attribute with color swatch', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `csw_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Color Swatch', 'Color Swatch');
    // Navigate to edit page
    await searchAndEditAttribute(adminPage, code);
    const swatchTypeInput = adminPage.locator('input[name="swatch_type"][type="text"]');
    await expect(swatchTypeInput).toBeDisabled();
    const hiddenInput = adminPage.locator('input[name="swatch_type"][type="hidden"]');
    await expect(hiddenInput).toHaveValue('color');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Edit and add the options in the Select type attibute with swatch type as color swatch', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `csw_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Color Swatch', 'Color Swatch');
    // Navigate to edit page
    await searchAndEditAttribute(adminPage, code);
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await addColorSwatchOption(adminPage, `red_${uid}`, 'Red', '#ff0000');
    await addColorSwatchOption(adminPage, `aqua_${uid}`, 'Aqua Blue', '#00faf6');
    await clickSaveAndExpect(adminPage, 'Save Attribute', /Attribute Updated Successfully/i);
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Delete the color swatch attribute option', { timeout: 90000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `csw_${uid}`;
    const optCode = `red_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Color Swatch', 'Color Swatch');
    // Navigate to edit page and add an option to delete
    await searchAndEditAttribute(adminPage, code);
    await addColorSwatchOption(adminPage, optCode, 'Red', '#ff0000');
    // Delete the option
    const itemRow1 = adminPage.locator('div', { hasText: optCode }).filter({ hasText: 'Red' });
    await itemRow1.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Deleted Successfully')).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Create the select type attribute with swatch type as image swatch', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `isw_${uid}`;
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Select');
    await adminPage.getByRole('option', { name: 'Select' }).first().click();
    await adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Text Swatch' }).click();
    await adminPage.getByRole('option', { name: 'Image Swatch' }).first().click();
    await expect(adminPage.locator('#swatch_type').getByRole('combobox').locator('div')
      .filter({ hasText: 'Image Swatch' }).first()).toBeVisible();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Image Swatch');
    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
      adminPage.getByRole('button', { name: 'Save Attribute' }).click(),
    ]);
    await expect(adminPage.locator('#app').getByText('Edit Attribute')).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Verify swatch type field is visible and selected swatch type is visible while editing the select type attribute with image swatch', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `isw_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Image Swatch', 'Image Swatch');
    // Navigate to edit page
    await searchAndEditAttribute(adminPage, code);
    const swatchTypeInput = adminPage.locator('input[name="swatch_type"][type="text"]');
    await expect(swatchTypeInput).toBeDisabled();
    const hiddenInput = adminPage.locator('input[name="swatch_type"][type="hidden"]');
    await expect(hiddenInput).toHaveValue('image');
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Edit and add the options in the Select type attibute with swatch type as image swatch', { timeout: 120000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `isw_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Image Swatch', 'Image Swatch');
    // Navigate to edit page
    await searchAndEditAttribute(adminPage, code);
    await expect(adminPage.locator('#app').getByText('Options', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Add Row')).toBeVisible();
    await addImageSwatchOption(adminPage, `floral_${uid}`, 'Floral Pattern', 'assets/floral.jpg');
    await addImageSwatchOption(adminPage, `stripes_${uid}`, 'Stripes Pattern', 'assets/stripes.jpg');
    await addImageSwatchOption(adminPage, `dots_${uid}`, 'Dots Pattern', 'assets/dotted.png');
    await addImageSwatchOption(adminPage, `checked_${uid}`, 'Checked Pattern', 'assets/check.jpeg');
    await clickSaveAndExpect(adminPage, 'Save Attribute', /Attribute Updated Successfully/i);
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Delete the image swatch attribute option', { timeout: 120000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `isw_${uid}`;
    const optCode = `dots_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Image Swatch', 'Image Swatch');
    // Navigate to edit page and add an option to delete
    await searchAndEditAttribute(adminPage, code);
    await addImageSwatchOption(adminPage, optCode, 'Dots Pattern', 'assets/dotted.png');
    // Search for the option
    const searchBox = adminPage.getByRole('textbox', { name: 'Search', exact: true });
    await searchBox.fill(optCode);
    await searchBox.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    // Delete the option
    const itemRow1 = adminPage.locator('div', { hasText: optCode }).filter({ hasText: 'Dots Pattern' });
    await itemRow1.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText('Attribute Option Deleted Successfully')).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });

  test('Check the search bar of attribute options datagrid for swatch type attribute', { timeout: 120000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const code = `isw_${uid}`;
    const optCode = `floral_${uid}`;
    await createSelectSwatchAttribute(adminPage, code, 'Image Swatch', 'Image Swatch');
    // Navigate to edit page and add an option
    await searchAndEditAttribute(adminPage, code);
    await addImageSwatchOption(adminPage, optCode, 'Floral Pattern', 'assets/floral.jpg');
    // Search for the option
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(optCode);
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(optCode, { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Floral Pattern', { exact: true })).toBeVisible();
    // Cleanup
    await deleteAttribute(adminPage, code);
  });
});
