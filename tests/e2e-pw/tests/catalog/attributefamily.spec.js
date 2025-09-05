const { test, expect } = require('../../utils/fixtures');

test.describe('Attribute Family', () => {
test('Create Attribute family with empty code field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();
  await adminPage.getByRole('textbox', { name: 'Enter Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Enter Code' }).fill('');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Header');
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.getByText('The Code field is required')).toBeVisible();
});

test('Create Attribute family', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();
  await adminPage.getByRole('textbox', { name: 'Enter Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Enter Code' }).fill('header');
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Header');
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.getByText(/Family created successfully/i)).toBeVisible();
});

test('should allow attribute family search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('header');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=headerHeader', {exact:true})).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByRole('button', { name: '' }).click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a attribute family (Edit, Copy, Delete)', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'header' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/edit/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Copy"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/copy/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Edit Attribute Family', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByText('headerHeader').getByTitle('Edit').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Footer');
  await adminPage.locator('.secondary-button', { hasText: 'Assign Attribute Group' }).click();
  await adminPage.locator('input[name="group"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('textbox', { name: 'group-searchbox' }).fill('General');
  await adminPage.getByRole('option', { name: 'General' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Assign Attribute Group' }).click();
  const dragHandle = await adminPage.locator('#unassigned-attributes i.icon-drag:near(:text("SKU"))').first();
  const dropTarget = await adminPage.locator('#assigned-attribute-groups .group_node').first();
  const dragBox = await dragHandle.boundingBox();
  const dropBox = await dropTarget.boundingBox();
  if (dragBox && dropBox) {
  await adminPage.mouse.move(dragBox.x + dragBox.width / 2, dragBox.y + dragBox.height / 2);
  await adminPage.mouse.down();
  await adminPage.mouse.move(dropBox.x + dropBox.width / 2, dropBox.y + dropBox.height / 2, { steps: 10 });
  await adminPage.mouse.up();
  }
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.getByText(/Family updated successfully/i)).toBeVisible();
});

test('Delete Attribute Family', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByText('headerFooter').getByTitle('Delete').click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Family deleted successfully/i)).toBeVisible();
});
});

