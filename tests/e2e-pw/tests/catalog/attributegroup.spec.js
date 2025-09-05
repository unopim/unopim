const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Test cases', () => {
test('Create Attribute Group with empty Code field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Description');
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
});

test('Create Attribute Group', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('product_description');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Description');
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.getByText(/Attribute Group Created Successfully/i)).toBeVisible();
});

test('should allow attribute group search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('product');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=product_description', {exact:true})).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a attribute group (Edit, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'product_description' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributegroups\/edit/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Update attribute group', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByText('product_descriptionProduct Description').getByTitle('Edit').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('prudact discripsan');
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.getByText(/Attribute Group Updated Successfully/i)).toBeVisible();
});

test('Delete Attribute Group', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByText('product_descriptionprudact discripsan').getByTitle('Delete').click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Attribute Group Deleted Successfully/i)).toBeVisible();
});
});

