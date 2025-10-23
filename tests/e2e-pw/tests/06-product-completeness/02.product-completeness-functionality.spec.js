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
    await adminPage.getByText('50', { exact: true }).click();
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

//   test('Verify for the Completness feature on Product Edit page', async ({ adminPage }) => {
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     await adminPage.getByRole('link', { name: 'Products' }).click();
//     const itemRow = adminPage.locator('div', { hasText: 'NAScore' });
//     await itemRow.locator('span[title="Edit"]').first().click();
//     await expect(adminPage.getByRole('button', { name: '% Completeness 33%' })).toBeVisible();
//     await adminPage.getByRole('button', { name: '% Completeness 33%' }).click();
//     await expect(adminPage.getByText('2 missing required attributes')).toBeVisible();
// });

//   test('Verify for the completeness score on product grid adminPage', async ({ adminPage }) => {
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     const skuRow = adminPage.locator('div.row:has-text("NAScore")');
//     const completeColumn = skuRow.locator('span.inline-flex.items-center.justify-center.text-center');
//     await expect(completeColumn).toHaveText('33%');;
// });

  test('Create a new channel and assigned multiple locale and currency', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Settings' }).click();
    await adminPage.getByRole('link', { name: 'Locales' }).click();
    await adminPage.locator('div').filter({ hasText: /^1af_ZAAfrikaans \(South Africa\)Disabled$/ }).locator('a').first().click();
    await adminPage.locator('form').filter({ hasText: 'Edit Locale' }).locator('label').nth(3).click();
    await adminPage.locator('.px-4.py-2\\.5 > div:nth-child(3)').click();
    await adminPage.getByRole('button', { name: 'Save Locale' }).click();
    await expect(adminPage.getByText('Locale updated successfully. Close')).toBeVisible();
    await adminPage.getByRole('link', { name: 'Currencies' }).click();
    await adminPage.locator('div').filter({ hasText: /^1ADPAndorran PesetaDisabled$/ }).locator('a').first().click();
    await adminPage.locator('.rounded-full.w-9.h-5').click();
    await adminPage.getByRole('button', { name: 'Save Currency' }).click();
    await expect(adminPage.getByText('Currency updated successfully. Close')).toBeVisible();
    await adminPage.getByRole('link', { name: ' Settings' }).click();
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

  test('Verify bulk selection of attributes for required channel updates product completeness visibility', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.getByTitle('Edit').nth(1).click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.getByRole('button', { name: '' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.locator('div').filter({ hasText: /^Code$/ }).locator('label span').click();
    await adminPage.getByRole('button', { name: 'Select Action ' }).click();
    await adminPage.getByRole('link', { name: 'Change Completeness' }).click();
    await adminPage.locator('.px-4 > .mb-4 > div > .multiselect > .multiselect__tags').click();
    await adminPage.getByRole('option', { name: 'Default' }).locator('span').first().click();
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.getByText('Completeness updated successfully Close')).toBeVisible();
    await expect(adminPage.getByText('of 16 Selected')).toBeVisible();
});

//   test('Verify product completeness score updates on product grid after marking all attributes as required with Sku data only', async ({ adminPage }) => {
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     const skuRow = adminPage.locator('div.row:has-text("NAScore")');
//     const completeColumn = skuRow.locator('span.inline-flex.items-center.justify-center.text-center');
//     await expect(completeColumn).toHaveText('6%');;
// });

//   test('Verify product completeness score updates on product Edit adminPage after marking all attributes as required with Sku data only', async ({ adminPage }) => {
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     await adminPage.getByRole('link', { name: 'Products' }).click();
//     await adminPage.getByTitle('Edit').click();
//     await expect(adminPage.getByRole('button', { name: '% Completeness 6%' })).toBeVisible();
//     await adminPage.getByRole('button', { name: '% Completeness 6%' }).click();
//     await expect(adminPage.getByText('15 missing required attributes ')).toBeVisible();
// });

  test('Verify channel can be deselected for specific attribute in completeness settings', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.getByTitle('Edit').nth(1).click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.getByRole('button', { name: '' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.locator('div:nth-child(14) > div:nth-child(3) > .mb-4 > div > .multiselect > .multiselect__tags > .multiselect__tags-wrap > .multiselect__tag > .multiselect__tag-icon').click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
    await adminPage.locator('div:nth-child(13) > div:nth-child(3) > .mb-4 > div > .multiselect > .multiselect__tags > .multiselect__tags-wrap > .multiselect__tag > .multiselect__tag-icon').click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
    await adminPage.locator('div:nth-child(5) > div:nth-child(3) > .mb-4 > div > .multiselect > .multiselect__tags > .multiselect__tags-wrap > .multiselect__tag > .multiselect__tag-icon').click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
    await adminPage.locator('div:nth-child(4) > div:nth-child(3) > .mb-4 > div > .multiselect > .multiselect__tags > .multiselect__tags-wrap > .multiselect__tag > .multiselect__tag-icon').click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
    await adminPage.locator('div:nth-child(3) > div:nth-child(3) > .mb-4 > div > .multiselect > .multiselect__tags > .multiselect__tags-wrap > .multiselect__tag > .multiselect__tag-icon').click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
});

//   test('Verify product completeness score updates after deselecting 5 attribute from required in channel', async ({ adminPage }) => {
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     const skuRow = adminPage.locator('div.row:has-text("NAScore")');
//     const completeColumn = skuRow.locator('span.inline-flex.items-center.justify-center.text-center');
//     await expect(completeColumn).toHaveText('9%');;
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     await adminPage.getByRole('link', { name: 'Products' }).click();
//     await adminPage.getByTitle('Edit').click();
//     await expect(adminPage.getByRole('button', { name: '% Completeness 9%' })).toBeVisible();
//     await adminPage.getByRole('button', { name: '% Completeness 9%' }).click();
//     await expect(adminPage.getByText('10 missing required attributes ')).toBeVisible();
// });

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
//   test('Verify completeness score is 100% on Product Edit page when all mising required attributes for channel are filled', async ({ adminPage }) => {
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     await adminPage.getByRole('link', { name: 'Products' }).click();
//     await adminPage.getByTitle('Edit').first().click();
//     await expect(adminPage.getByRole('button', { name: '% Completeness 100%' })).toBeVisible();
//     await adminPage.getByRole('button', { name: '% Completeness 100%' }).click();
//     await expect(adminPage.getByText('English (United States):100%')).toBeVisible();

// });

//   test('Verify completness score on product grid adminPage as 100% after filling all msiing field', async ({ adminPage }) => {
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     const skuRow = adminPage.locator('div.row:has-text("NAScore")');
//     const completeColumn = skuRow.locator('span.inline-flex.items-center.justify-center.text-center');
//     await expect(completeColumn).toHaveText('100%');;
// });
  test('Verify configuring required attributes for different channels in Default Family Completeness settings', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
    await adminPage.getByTitle('Edit').nth(1).click();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.getByRole('button', { name: '' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.locator('.multiselect__tags').first().click();
    await adminPage.getByRole('option', { name: 'channel3' }).locator('span').first().click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
    await adminPage.locator('div:nth-child(17) > div:nth-child(3) > .mb-4 > div > .multiselect > .multiselect__tags').click();
    await adminPage.getByRole('option', { name: 'channel3' }).locator('span').first().click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
    await adminPage.locator('div:nth-child(16) > div:nth-child(3) > .mb-4 > div > .multiselect > .multiselect__tags').click();
    await adminPage.getByRole('option', { name: 'channel3' }).locator('span').first().click();
    await expect(adminPage.getByText('Completeness updated successfully Close').first()).toBeVisible();
});

//   test('Verify for the complete score of a sku for both channel', async ({ adminPage }) => {
//     await adminPage.getByRole('link', { name: ' Catalog' }).click();
//     await adminPage.getByRole('link', { name: 'Products' }).click();
//     await adminPage.getByTitle('Edit').first().click();
//     await adminPage.getByRole('button', { name: ' Default ' }).click();
//     await adminPage.getByRole('link', { name: 'channel3' }).click();
//     await expect(adminPage.getByRole('button', { name: '% Completeness 67%' })).toBeVisible();
//     await adminPage.getByRole('button', { name: '% Completeness 67%' }).click();
//     await expect(adminPage.getByText('Afrikaans (South Africa):67%')).toBeVisible();
//     await adminPage.getByRole('button', { name: '% Completeness 67%' }).click();
//     await expect(adminPage.getByText('English (United States):67%')).toBeVisible();
//     await adminPage.getByRole('button', { name: ' channel3 ' }).click();
//     await adminPage.getByRole('link', { name: 'Default' }).click();
//     await expect(adminPage.getByRole('button', { name: '% Completeness 100%' })).toBeVisible();
// });
});