const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

const uid = generateUid();

test.describe.serial('UnoPim Category Field', () => {
test('Create category field with empty Code', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText('The Code field is required ')).toBeVisible();
});

test('Create category field with empty Type', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`suggestion_${uid}`);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText('The Type field is required ')).toBeVisible();
});

test('Create category field with empty Code and Type', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText('The Code field is required ')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The Type field is required ')).toBeVisible();
});

test('Create category field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`test1_${uid}`);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText(/Category Field Created Successfully/i)).toBeVisible();
});

test('should allow category field search', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).fill(`test1_${uid}`);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('text=1 Results')).toBeVisible();
  await expect(adminPage.locator('#app').getByText(`test1_${uid}`)).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(perPageBtn).toContainText('20');
});

test('should perform actions on a category (Edit, Delete)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  const itemRow = adminPage.locator('div', { hasText: 'name' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/category-fields\/edit/);
  await adminPage.goBack();
  await adminPage.waitForLoadState('networkidle');
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('should allow selecting all category field with the mass action checkbox', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.click('label[for="mass_action_select_all_records"]');
  await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
});

test('update category field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  const itemRow = adminPage.locator('div', { hasText: `test1_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('soogesan');
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText(/Category Field Updated Successfully/i)).toBeVisible();
});

test('delete category field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  const itemRow = adminPage.locator('div', { hasText: `test1_${uid}` });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Category Field Deleted Successfully/i)).toBeVisible();
});

test('delete default category field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByText('nameName').getByTitle('Delete').click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/This category field can not be deleted./i)).toBeVisible();
});
});
