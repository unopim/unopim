const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Category', () => {
  test('Create Categories with empty Code field', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('#name').click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('#name').type('Television');
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText('The code field is required')).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('Create Categories with empty Name field', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').fill('television');
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText('The Name field is required')).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('Create Categories with empty Code and Name field', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').fill('');
    await adminPage.waitForTimeout(500);
    await adminPage.locator('#name').click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('#name').fill('');
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText('The code field is required')).toBeVisible();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText('The Name field is required')).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('Create Categories with all field', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').type('test1');
    await adminPage.waitForTimeout(500);
    await adminPage.locator('#name').click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('#name').type('Television');
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText(/Category created successfully/i)).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('should allow category search', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('textbox', { name: 'Search' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('textbox', { name: 'Search' }).type('test1');
    await adminPage.waitForTimeout(500);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForTimeout(500);
    await expect(adminPage.locator('text=TelevisionTelevisiontest1', {exact:true})).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByText('Filter', { exact: true }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText('Apply Filters')).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('should allow setting items per adminPage', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: '' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByText('20', { exact: true }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByRole('button', { name: '' })).toContainText('20');
    await adminPage.waitForTimeout(500);
  });

  test('should perform actions on a category (Edit, Delete)', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    const itemRow = adminPage.locator('div', { hasText: 'root' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/categories\/edit/);
    await adminPage.waitForTimeout(500);
    await adminPage.goBack();
    await adminPage.waitForTimeout(500);
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('should allow selecting all category with the mass action checkbox', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.click('label[for="mass_action_select_all_records"]');
    await adminPage.waitForTimeout(500);
    await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
    await adminPage.waitForTimeout(500);
  });

  test('Update Categories', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('textbox', { name: 'Search' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('textbox', { name: 'Search' }).type('test1');
    await adminPage.waitForTimeout(500);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForTimeout(500);
    await expect(adminPage.locator('text=TelevisionTelevisiontest1')).toBeVisible();
    await adminPage.waitForTimeout(500);
    const itemRow = adminPage.locator('div', { hasText: 'TelevisionTelevisiontest1' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('#name').click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('#name').fill('LG Television');
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText(/Category updated successfully/i)).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('Delete Category', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByText('LG TelevisionLG Televisiontest1').getByTitle('Delete').click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText(/The category has been successfully deleted/i)).toBeVisible();
    await adminPage.waitForTimeout(500);
  });

  test('Delete Root Category', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForTimeout(500);
    const itemRow = adminPage.locator('div', { hasText: '[root][root]' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText(/You cannot delete the root category that is associated with a channel./i)).toBeVisible();
    await adminPage.waitForTimeout(500);
  });
});
