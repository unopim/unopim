const { test, expect } = require('../../utils/fixtures');
test.describe('Verify the behvaiour of Product Completenss feature', () => {
  test('Verify product grid shows NA for completeness when no required channel configured', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('button', { name: 'Create Product' }).click();
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__tags').click();
    await adminPage.getByRole('option', { name: 'Simple' }).locator('span').first().click();
    await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__tags').click();
    await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await adminPage.locator('input[name="sku"]').click();
    await adminPage.locator('input[name="sku"]').fill('NAScore');
    await adminPage.getByRole('button', { name: 'Save Product' }).click();
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    const skuRow = adminPage.locator('div.row:has-text("NAScore")');
    const completeColumn = skuRow.locator('span.label-info');
    await expect(completeColumn).toHaveText('N/A');
});
  test('Verify product edit adminPage shows no completeness score when no required channel configured', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByText('NAScore').click();
    await expect(adminPage).toHaveURL(/.*\/edit\/.*/);
    await expect(adminPage.locator('text=Missing Required Attributes')).toHaveCount(0);
    await expect(adminPage.locator('text=Completeness')).toHaveCount(0);
});

  test('Verify that attributes can be set as required from Completeness tab in default family', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'Default'}).nth(1);
    await itemRow.locator('span[title="Edit"]').nth(1).click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.getByRole('button', { name: '' }).click();
    await adminPage.getByText('50', { exact: true }).first().click();
    await expect(adminPage.getByText('16 Results')).toBeVisible();
    await adminPage.locator(`input[name="channel_requirements"]`).locator('..').locator('.multiselect__tags').nth(15).click();
    await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
    await adminPage.locator(`input[name="channel_requirements"]`).locator('..').locator('.multiselect__tags').nth(14).click();
    await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
    await adminPage.locator(`input[name="channel_requirements"]`).locator('..').locator('.multiselect__tags').nth(13).click();
    await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
});

  test('Create a new channel and assigned multiple locale and currency', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Settings' }).click();
    await adminPage.getByRole('link', { name: 'Locales' }).click();

    //Enable the af_ZA locale
    await adminPage.getByRole('textbox', { name: 'Search by code' }).click();
    await adminPage.getByRole('textbox', { name: 'Search by code' }).fill('af_ZA');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForTimeout(1000);
    const itemRow = adminPage.locator('div', { hasText: 'af_ZAAfrikaans (South Africa)' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.locator('label[for="status"]').click();
    await adminPage.getByRole('button', { name: 'Save Locale' }).click();
    await expect(adminPage.getByText(/Locale Updated successfully/i)).toBeVisible();

    //Enable the Andorran Peseta currency
    await adminPage.getByRole('link', { name: 'Currencies' }).click();
    await adminPage.getByRole('textbox', { name: 'Search by code or id' }).click();
    await adminPage.getByRole('textbox', { name: 'Search by code or id' }).type('adp');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForTimeout(1000);
    const itemRow1 = adminPage.locator('div', { hasText: 'ADPAndorran Peseta' });
    await itemRow1.locator('span[title="Edit"]').first().click();
    await adminPage.locator('label[for="status"]').click();
    await adminPage.getByRole('button', { name: 'Save Currency' }).click();
    await expect(adminPage.getByText(/Currency updated successfully/i)).toBeVisible();

    //Create a new channel and assign the above enabled locale and currency
    await adminPage.getByRole('link', { name: 'Channels' }).click();
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('defaultchannel2');
    await adminPage.locator('div').filter({ hasText: /^Select Root Category$/ }).click();
    await adminPage.getByText('[root]').click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('channel3');
    await adminPage.locator('div').filter({ hasText: /^Select Locales$/ }).click();
    await adminPage.locator('#locales').getByText('Afrikaans (South Africa)').click();
    await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
    await adminPage.locator('div').filter({ hasText: /^Select currencies$/ }).click();
    await adminPage.getByText('Andorran Peseta').click();
    await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.getByText(/Channel created successfully/i)).toBeVisible();
});

  test('Verify all available channels are displayed in Configure Completeness for newly created family', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await expect(adminPage.getByText('displaycompletensstab')).toBeVisible();
    const itemRow = adminPage.locator('div', { hasText: 'displaycompletensstab' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.locator('div').filter({ hasText: /^Code$/ }).locator('label span').click();
    await adminPage.getByRole('button', { name: 'Select Action ' }).click();
    await adminPage.getByRole('link', { name: 'Change Completeness' }).click();
    await adminPage.locator('.px-4 > .mb-4 > div > .multiselect > .multiselect__tags').click();
    await expect(adminPage.getByRole('option', { name: 'Default' }).locator('span').first()).toBeVisible();
    await expect(adminPage.getByRole('option', { name: 'channel3' }).locator('span').first()).toBeVisible();
});

  test('Verify all available channels are displayed when user clicks “Configure Completeness” option', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'Default' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.locator('div').filter({ hasText: /^Code$/ }).locator('label span').click();
    await adminPage.getByRole('button', { name: 'Select Action ' }).click();
    await adminPage.getByRole('link', { name: 'Change Completeness' }).click();
    await adminPage.locator('.px-4 > .mb-4 > div > .multiselect > .multiselect__tags').click();
    await expect(adminPage.getByRole('option', { name: 'Default' }).locator('span').first()).toBeVisible();
    await expect(adminPage.getByRole('option', { name: 'channel3' }).locator('span').first()).toBeVisible();
});

  test('Verify bulk selection of attributes for required channel updates product completeness visibility', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.getByTitle('Edit').nth(1).click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.getByRole('button', { name: '' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.click('label[for="mass_action_select_all_records"]');
    await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
    await adminPage.getByRole('button', { name: /Select Action/i }).click();
    await adminPage.locator('a', { hasText: 'Change Completeness Requirement' }).click();
    await adminPage.locator('.px-4 > .mb-4 > div > .multiselect > .multiselect__tags').click();
    await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.getByText('Completeness updated successfully Close')).toBeVisible();
});

  test('Verify channel can be deselected for specific attribute in completeness settings', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.getByTitle('Edit').nth(1).click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.getByRole('button', { name: '' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.locator('.multiselect__tag-icon').first().click();
    //await adminPage.locator('div:nth-child(14) > div:nth-child(3) > .mb-4 > div > .multiselect > .multiselect__tags > .multiselect__tags-wrap > .multiselect__tag > .multiselect__tag-icon').click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
});

  test('Update the sku by filling all missing required attribute', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Products' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'NAScore' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.locator('#product_number').click();
    await adminPage.locator('#product_number').fill('123');
    await adminPage.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').fill('skusavedraft')
    await adminPage.locator('input[name="values[common][url_key]"]').click();
    await adminPage.locator('input[name="values[common][url_key]"]').type('skusavedraft');
    const shortDescFrame = adminPage.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This is a short description', { delay: 100 });
    const mainDescFrame = adminPage.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the full product description added by test.');
    await adminPage.locator('input[name="values[channel_locale_specific][default][en_US][price][USD]"]').fill('300');
    await adminPage.locator('#meta_title').click();
    await adminPage.locator('#meta_title').fill('meattitle');
    await adminPage.locator('#meta_keywords').click();
    await adminPage.locator('#meta_keywords').fill('keyword');
    await adminPage.locator('#meta_description').click();
    await adminPage.locator('#meta_description').fill('description');
    await adminPage.locator('#cost').click();
    await adminPage.locator('#cost').fill('23');
    await adminPage.getByRole('button', { name: 'Save Product' }).click();
});

  test('Verify configuring required attributes for different channels in Default Family Completeness settings', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.getByTitle('Edit').nth(1).click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.getByRole('button', { name: '' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.locator(`input[name="channel_requirements"]`).locator('..').locator('.multiselect__tags').first().click();
    await adminPage.getByRole('option', { name: 'channel3' }).locator('span').first().click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
});
});