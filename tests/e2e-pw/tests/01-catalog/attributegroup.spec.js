const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

const uid = generateUid();

test.describe.serial('UnoPim Test cases', () => {
test('Create Attribute Group with empty Code field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Description');
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
});

test('Create Attribute Group', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`product_description_${uid}`);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Description');
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Group Created Successfully/i)).toBeVisible();
});

test('should allow attribute group search', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).fill(`product_description_${uid}`);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('#app').getByText(`product_description_${uid}`)).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
  await perPageBtn.click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(perPageBtn).toContainText('20');
});

test('should perform actions on a attribute group (Edit, Delete)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  const itemRow = adminPage.locator('div', { hasText: `product_description_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributegroups\/edit/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Update attribute group', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  const itemRow = adminPage.locator('div', { hasText: `product_description_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('prudact discripsan');
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Group Updated Successfully/i)).toBeVisible();
});

test('Delete Attribute Group', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  const itemRow = adminPage.locator('div', { hasText: `product_description_${uid}` });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Group Deleted Successfully/i)).toBeVisible();
});
});
