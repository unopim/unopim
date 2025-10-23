const { test, expect } = require('../../utils/fixtures');
test.describe('Verify that Product Completeness feature correctly Exists', () => {
  test('Verify “Completeness” tab is displayed in Default Family Edit adminPage', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).dblclick();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'Default' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/edit\/\d+$/);
    await expect(adminPage.getByRole('link', { name: 'Completeness' })).toBeVisible();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
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
    await expect(adminPage.getByText('English (United States)0%')).toBeVisible();
});
    
  test('Verify Product Completeness Status Displays N/A When No Attributes Are Configured as Required for a Channel', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Products' }).click();
    await adminPage.getByRole('button', { name: 'Create Product' }).click();
    await adminPage.locator('div').filter({ hasText: /^Select option$/ }).first().click();
    await adminPage.getByRole('option', { name: 'Simple' }).locator('span').first().click();
    await adminPage.getByText('Select option').click();
    await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await adminPage.locator('input[name="sku"]').click();
    await adminPage.locator('input[name="sku"]').fill('check-complete-status-onproduct');
    await adminPage.getByRole('button', { name: 'Save Product' }).click();
    await adminPage.getByRole('link', { name: ' Dashboard' }).click();
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
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
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByText('Assign Attribute Group').click();
    await adminPage.locator('div').filter({ hasText: /^Select option$/ }).click();
    await adminPage.getByRole('option', { name: 'General' }).locator('span').first().click();
    await adminPage.getByRole('button', { name: 'Assign Attribute Group' }).click();
    await adminPage.waitForTimeout(200);
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
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.locator('div').filter({ hasText: /^SKU$/ })).toBeVisible();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await expect(adminPage.locator('div').filter({ hasText: /^sku$/ })).toBeVisible();
    await expect(adminPage.getByText('SKU', { exact: true })).toBeVisible();

});
    
  test('Verify attribute search using search bar in Completeness section returns correct results in Family settings', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('sku');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await expect(adminPage.getByText('1 Results')).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^sku$/ })).toBeVisible();
});

  test('Verify default channel appears in dropdown for “Required in Channel” in Completeness tab', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.locator('div').filter({ hasText: /^Select option$/ }).nth(1).click();
    await expect(adminPage.getByRole('option', { name: 'Default' }).locator('span').first()).toBeVisible();
});

  test('Verify attribute filter using code in Completeness section of Family settings', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('sku');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await expect(adminPage.getByText('1 Results')).toBeVisible();
});
    
  test('Verify attribute filter using name in Completeness section of Family settings', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Name' }).click();
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('xyz');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await expect(adminPage.getByText('0 Results')).toBeVisible();
});
    
  test('Verify attribute filter using Required in Channel in Completeness section of Family settings', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).fill('xyz');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await expect(adminPage.getByText('0 Results')).toBeVisible();
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).fill('default');
    await adminPage.getByText('Save').click();
    await expect(adminPage.getByText('0 Result')).toBeVisible();
});
    
  test('Verify correct selection of SKU in default channel using “Required for Channel” dropdown', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.locator(`input[name="channel_requirements"]`).locator('..').locator('.multiselect__tags').nth(1).click();
    await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await expect(adminPage.getByText('Completeness updated successfully Close')).toBeVisible();

});

  test('Verify filter using Required in Channel in Completeness section of Family settings return 1 result', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).fill('default');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await expect(adminPage.getByText('1 Results')).toBeVisible();
});

  test('Verify selectable attribute count in Completeness tab equals assigned family attributes', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: 'Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForSelector('#assigned-attribute-groups', { state: 'visible' });
    await adminPage.waitForTimeout(1000);
    const assignedCount = await adminPage
    .locator('#assigned-attribute-groups .ltr\\:ml-11 [data-draggable="true"]').count();
    console.log(`Number of assigned attributes (excluding groups): ${assignedCount}`);
    expect(assignedCount).toBeGreaterThan(0);
});
});

