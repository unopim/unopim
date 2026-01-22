const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Channel test', () => {
test('Create Channel with empty Code', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
});

test('Create Channel with empty Root Category', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.getByText('The Root Category field is required')).toBeVisible();
});

test('Create Channel with empty Locales field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.getByText('The Locales field is required')).toBeVisible();
});

test('Create Channel with empty Currency field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.getByText('The Currencies field is required')).toBeVisible();
});

test('Create Channel with all required field empty', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
  await expect(adminPage.getByText('The Root Category field is required')).toBeVisible();
  await expect(adminPage.getByText('The Locales field is required')).toBeVisible();
  await expect(adminPage.getByText('The Currencies field is required')).toBeVisible();
});

test('Create Channel', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.getByText(/Channel created successfully/i)).toBeVisible();
});

test('Create Channel with same Code', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.getByText('The Code has already been taken.')).toBeVisible();
});

test('should allow Channel search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('eCommerce');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=E-Commerce', {exact:true})).toBeVisible();
});

test('Update Channel', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'eCommerceE-Commerce' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Mobile');
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.getByText(/Update Channel Successfully/i)).toBeVisible();
});

test('Delete Channel', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'eCommerceMobile' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Channel deleted successfully/i)).toBeVisible();
});

test('Delete Default Channel', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  const itemRow = adminPage.locator('div', { hasText: '[root]' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/You can't delete the channel "default" because your PIM needs to have at least one channel./i)).toBeVisible();
});
});

