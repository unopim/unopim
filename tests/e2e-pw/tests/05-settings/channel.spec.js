const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

const uid = generateUid();

test.describe.serial('UnoPim Channel test', () => {
test('Create Channel with empty Code', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.keyboard.press('Escape');
  await expect(adminPage.locator('#locales')).toBeVisible();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
});

test('Create Channel with empty Root Category', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`${uid}eCom`);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.keyboard.press('Escape');
  await expect(adminPage.locator('#locales')).toBeVisible();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText('The Root Category field is required')).toBeVisible();
});

test('Create Channel with empty Locales field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`${uid}eCom`);
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText('The Locales field is required')).toBeVisible();
});

test('Create Channel with empty Currency field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`${uid}eCom`);
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.keyboard.press('Escape');
  await expect(adminPage.locator('#locales')).toBeVisible();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText('The Currencies field is required')).toBeVisible();
});

test('Create Channel with all required field empty', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The Root Category field is required')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The Locales field is required')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The Currencies field is required')).toBeVisible();
});

test('Create Channel', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`${uid}eCom`);
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(`${uid} E-Commerce`);
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.keyboard.press('Escape');
  await expect(adminPage.locator('#locales')).toBeVisible();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText(/Channel created successfully/i)).toBeVisible();
});

test('Create Channel without translations', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`${uid}noTrans`);
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.keyboard.press('Escape');
  await expect(adminPage.locator('#locales')).toBeVisible();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText(/Channel created successfully/i)).toBeVisible();
});

test('Create Channel with same Code', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`${uid}eCom`);
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.keyboard.press('Escape');
  await expect(adminPage.locator('#locales')).toBeVisible();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText('The Code has already been taken.')).toBeVisible();
});

test('should allow Channel search', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type(`${uid}eCom`);
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('#app').locator(`text=${uid} E-Commerce`, {exact:true})).toBeVisible();
});

test('Update Channel', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  const itemRow = adminPage.locator('div', { hasText: `${uid}eCom` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(`${uid} Mobile`);
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText(/Update Channel Successfully/i)).toBeVisible();
});

test('Delete Channel', async ({ adminPage }) => {
  // Create a temporary channel first, then delete it
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`${uid}delCh`);
  await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
  await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(`${uid} Delete Test`);
  await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.keyboard.press('Escape');
  await expect(adminPage.locator('#locales')).toBeVisible();
  await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
  await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await expect(adminPage.locator('#app').getByText(/Channel created successfully/i)).toBeVisible();

  // Now navigate back and delete the channel we just created
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await adminPage.waitForLoadState('networkidle');
  const itemRow = adminPage.locator('div').filter({ hasText: `${uid}delCh` });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Channel deleted successfully/i)).toBeVisible();
});

test('Delete Channel without translations', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  const itemRow = adminPage.locator('div', { hasText: `${uid}noTrans` });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Channel deleted successfully/i)).toBeVisible();
});

test('Delete Default Channel', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');

  // Search for default channel to isolate the row
  await adminPage.getByPlaceholder('Search').first().fill('default');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');

  const itemRow = adminPage.locator('div').filter({ hasText: 'default' }).first();
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();

  // Matches either "The default channel cannot be deleted." or "You can't delete the channel "default" because..."
  await expect(adminPage.locator('#app').getByText(/default channel cannot be deleted|can.t delete the channel.*default/i)).toBeVisible();
});
});
