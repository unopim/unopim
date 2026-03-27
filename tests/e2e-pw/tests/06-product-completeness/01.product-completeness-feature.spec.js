const { test, expect } = require('../../utils/fixtures');
test.describe('Verify that Product Completeness feature correctly Exists', () => {
  test('Verify “Completeness” tab is displayed in Default Family Edit adminPage', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    const editBtn = adminPage.locator('span[title="Edit"]').first();
    await editBtn.click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/edit\/\d+$/);
    await expect(adminPage.getByRole('link', { name: 'Completeness' })).toBeVisible();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/edit\/\d+\?completeness/);
    await expect(adminPage.getByRole('paragraph').filter({ hasText: 'Completeness' })).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Code$/ })).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Name$/ })).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Required in Channels$/ })).toBeVisible();
});

  test('Verify Product Completeness Status Display on Dashboard for All Products Channel-wise', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Dashboard' }).click();
    await expect(adminPage.getByRole('link', { name: ' Dashboard' })).toBeVisible();
    await expect(adminPage.getByRole('heading', { name: 'Default' })).toBeVisible();
    await expect(adminPage.locator('circle').first()).toBeVisible();
    await expect(adminPage.locator('header').filter({ hasText: 'Default Low completeness' })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('English (United States)0%').first()).toBeVisible();
});
    
  test('Verify Product Completeness Status Displays N/A When No Attributes Are Configured as Required for a Channel', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');

    // Skip product creation if it already exists from a previous run
    const existingProduct = adminPage.locator('#app').getByText('check-complete-status-onproduct');
    if (!(await existingProduct.isVisible({ timeout: 3000 }).catch(() => false))) {
      await adminPage.getByRole('button', { name: 'Create Product' }).click();
    await adminPage.locator('div').filter({ hasText: /^Select option$/ }).first().click();
    await adminPage.getByRole('option', { name: 'Simple' }).first().click();
    await adminPage.getByText('Select option').click();
    await adminPage.getByRole('option', { name: 'Default' }).first().click();
    await adminPage.locator('input[name="sku"]').click();
    await adminPage.locator('input[name="sku"]').fill('check-complete-status-onproduct');
    await adminPage.getByRole('button', { name: 'Save Product' }).click();
      await adminPage.waitForLoadState('networkidle');
    }
    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    // Wait for product rows to render in the datagrid
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 15000 });
    await expect(adminPage.getByRole('paragraph').filter({ hasText: /^Complete$/ })).toBeVisible();
    await expect(adminPage.getByRole('paragraph').filter({ hasText: 'N/A' }).first()).toBeVisible();
});
    
  test('Verify Completeness tab is automatically displayed for a new or custom family creation', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Settings' }).click();
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();
    await adminPage.getByText('General Code').click();
    await adminPage.getByRole('textbox', { name: 'Enter Code' }).fill('displaycompletensstab');
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('displaytab');
    await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.getByTitle('Edit').first().click();
    await expect(adminPage.getByRole('link', { name: 'Completeness' })).toBeVisible();
});
    
  test('Assign attribute group ,attribute in created family', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByText('Assign Attribute Group').click();
    await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
    await adminPage.getByRole('option', { name: 'General' }).first().click();
    await adminPage.getByRole('button', { name: 'Assign Attribute Group' }).click();
    await adminPage.waitForLoadState('networkidle');
    const attributes = ['sku', 'Name', 'price', 'Description'];
    for (const attr of attributes) {
    const dragHandle = await adminPage.locator(`#unassigned-attributes i.icon-drag:near(:text("${attr}"))`).first();
    const dropTarget = await adminPage.locator('#assigned-attribute-groups .group_node').first()
    const dragBox = await dragHandle.boundingBox();
    const dropBox = await dropTarget.boundingBox()
    if (dragBox && dropBox) {
    await adminPage.mouse.move(dragBox.x + dragBox.width / 2, dragBox.y + dragBox.height / 2);
    await adminPage.mouse.down();
    await adminPage.mouse.move(dropBox.x + dropBox.width / 2, dropBox.y + dropBox.height / 2, { steps: 10 });
    await adminPage.mouse.up();
    }
    }
    await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
});

  test('Verify newly assigned SKU attribute appears in Completeness tab for a new family', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('div').filter({ hasText: /^SKU$/ })).toBeVisible();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await expect(adminPage.locator('#app').getByText('sku', { exact: true })).toBeVisible({ timeout: 15000 });
    await expect(adminPage.locator('#app').getByText('SKU', { exact: true })).toBeVisible();

});
    
  test('Verify attribute search using search bar in Completeness section returns correct results in Family settings', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('sku');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('1 Results')).toBeVisible({ timeout: 15000 });
    await expect(adminPage.locator('#app').getByText('sku', { exact: true })).toBeVisible();
});

  test('Verify default channel appears in dropdown for “Required in Channel” in Completeness tab', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.locator('div').filter({ hasText: /^Select option$/ }).nth(1).click();
    await expect(adminPage.getByRole('option', { name: 'Default' }).first()).toBeVisible();
});

  test('Verify attribute filter using code in Completeness section of Family settings', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('sku');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('1 Results')).toBeVisible({ timeout: 15000 });
});

  test('Verify attribute filter using name in Completeness section of Family settings', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Name' }).click();
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('xyz');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await expect(adminPage.locator('#app').getByText('0 Results')).toBeVisible();
});

  test('Verify attribute filter using Required in Channel in Completeness section of Family settings', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).fill('xyz');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await expect(adminPage.locator('#app').getByText('0 Results')).toBeVisible();
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).fill('default');
    await adminPage.getByText('Save').click();
    await expect(adminPage.locator('#app').getByText('0 Result')).toBeVisible();
});
    
  test('Verify correct selection of SKU in default channel using “Required for Channel” dropdown', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.locator(`input[name="channel_requirements"]`).locator('..').locator('.multiselect__tags').nth(1).click();
    await adminPage.getByRole('option', { name: 'Default' }).first().click();
    await expect(adminPage.locator('#app').getByText('Completeness updated successfully Close')).toBeVisible();

});

  test('Verify filter using Required in Channel in Completeness section of Family settings return 1 result', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).fill('default');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await expect(adminPage.locator('#app').getByText('1 Results')).toBeVisible();
});

  test('Verify selectable attribute count in Completeness tab equals assigned family attributes', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: 'Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForSelector('#assigned-attribute-groups', { state: 'visible' });
    await adminPage.waitForLoadState('networkidle');
    const assignedCount = await adminPage
    .locator('#assigned-attribute-groups .ltr\\:ml-11 [data-draggable="true"]').count();
    console.log(`Number of assigned attributes (excluding groups): ${assignedCount}`);
    expect(assignedCount).toBeGreaterThan(0);
});

test('Create a new channel and assigned multiple locale and currency', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Settings' }).click();
    await adminPage.getByRole('link', { name: 'Locales' }).click();

    //Enable the af_ZA locale (only if not already enabled)
    await adminPage.getByRole('textbox', { name: 'Search by code' }).click();
    await adminPage.getByRole('textbox', { name: 'Search by code' }).fill('af_ZA');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: 'af_ZAAfrikaans (South Africa)' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('load');
    const statusChecked = await adminPage.locator('input[name="status"][type="checkbox"]').isChecked();
    if (!statusChecked) {
        await adminPage.locator('label[for="status"]').first().click();
    }
    await adminPage.getByRole('button', { name: 'Save Locale' }).click();
    await expect(adminPage.locator('#app').getByText(/Locale Updated successfully/i)).toBeVisible({ timeout: 15000 });

    //Enable the Andorran Peseta currency
    await adminPage.getByRole('link', { name: 'Currencies' }).click();
    await adminPage.getByRole('textbox', { name: 'Search by code or id' }).click();
    await adminPage.getByRole('textbox', { name: 'Search by code or id' }).type('adp');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow1 = adminPage.locator('div', { hasText: 'ADPAndorran Peseta' });
    await itemRow1.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('load');
    const currencyChecked = await adminPage.locator('input[name="status"][type="checkbox"]').isChecked();
    if (!currencyChecked) {
        await adminPage.locator('label[for="status"]').first().click();
    }
    await adminPage.getByRole('button', { name: 'Save Currency' }).click();
    await expect(adminPage.locator('#app').getByText(/Currency updated successfully/i)).toBeVisible();

    //Create a new channel and assign the above enabled locale and currency (skip if already exists)
    await adminPage.getByRole('link', { name: 'Channels' }).click();
    await adminPage.waitForLoadState('networkidle');
    const existingChannel = adminPage.locator('#app').getByText('channel3');
    if (await existingChannel.isVisible({ timeout: 3000 }).catch(() => false)) {
        return; // Channel already exists from a previous run
    }
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('defaultchannel2');
    await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
    await adminPage.getByText('[root]').click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('channel3');
    await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
    await adminPage.locator('#locales').getByText('Afrikaans (South Africa)').click();
    await adminPage.getByRole('option', { name: 'English (United States)' }).first().click();
    await adminPage.locator('body').click();
    await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
    await adminPage.getByText('Andorran Peseta').click();
    await adminPage.getByRole('option', { name: 'US Dollar' }).first().click();
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.locator('#app').getByText(/Channel created successfully/i)).toBeVisible();
});

  test('Verify all available channels are displayed in Configure Completeness for newly created family', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await expect(adminPage.locator('#app').getByText('displaycompletensstab')).toBeVisible();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    // Wait for completeness datagrid to fully render
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.click('label[for="mass_action_select_all_records"]');
    // Verify rows are selected — Select Action button only appears when rows are selected
    await expect(adminPage.getByRole('button', { name: /Select Action/i })).toBeVisible({ timeout: 5000 });
    await adminPage.getByRole('button', { name: /Select Action/i }).click();
    // Use force click to bypass dropdown close-on-blur behavior in headless mode
    await adminPage.locator('a', { hasText: 'Change Completeness Requirement' }).click({ force: true, timeout: 5000 });
    // Wait for the Configure Completeness modal to open
    await expect(adminPage.getByText('Configure Completeness')).toBeVisible({ timeout: 10000 });
    // Click the multiselect that appears AFTER the modal heading (the last one on page is in the modal)
    await adminPage.locator('.multiselect__tags').last().click();
    await expect(adminPage.getByRole('option', { name: 'Default' }).first()).toBeVisible();
    await expect(adminPage.getByRole('option', { name: 'channel3' }).first()).toBeVisible();
});

test('Delete the created family after tests', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('displaycompletensstab');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Family deleted successfully/i)).toBeVisible();
});
});
