const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Category', () => {
  const uniqueId    = Date.now();
  const catCode     = `cat_${uniqueId}`;
  const catName     = `Television_${uniqueId}`;
  let   updatedName = `LG Television_${uniqueId}`;

  test('Create Categories with empty Code field', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForLoadState('load');
    await adminPage.locator('#name').click();
    await adminPage.locator('#name').type('Television');
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await expect(adminPage.locator('#app').getByText('The code field is required')).toBeVisible();
  });

  test('Create Categories with empty Name field', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForLoadState('load');
    await adminPage.locator('input[name="code"]').click();
    await adminPage.locator('input[name="code"]').fill('television');
    await adminPage.locator('#name').click();
    await adminPage.locator('#name').fill('');
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
  });

  test('Create Categories with empty Code and Name field', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForLoadState('load');
    await adminPage.locator('input[name="code"]').click();
    await adminPage.locator('input[name="code"]').fill('');
    await adminPage.locator('#name').click();
    await adminPage.locator('#name').fill('');
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await expect(adminPage.locator('#app').getByText('The code field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
  });

  test('Create Categories with all field', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForLoadState('load');
    await adminPage.locator('input[name="code"]').click();
    await adminPage.locator('input[name="code"]').fill(catCode);
    await adminPage.locator('#name').click();
    await adminPage.locator('#name').fill(catName);
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await expect(adminPage.locator('#app').getByText(/category created successfully/i)).toBeVisible({ timeout: 15000 });
  });

  test('should allow category search', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.getByRole('textbox', { name: 'Search' }).click();
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(catCode);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(catCode)).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per adminPage', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await perPageBtn.click();
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a category (Edit, Delete)', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    const itemRow = adminPage.locator('div', { hasText: 'root' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/categories\/edit/);
    await adminPage.goBack();
    await adminPage.waitForLoadState('networkidle');
    await itemRow.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('#app').locator('text=Are you sure you want to delete?')).toBeVisible();
  });

  test('should allow selecting all category with the mass action checkbox', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.click('label[for="mass_action_select_all_records"]');
    await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
  });

  test('Update Categories', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.getByRole('textbox', { name: 'Search' }).click();
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(catCode);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(catCode)).toBeVisible();
    const itemRow = adminPage.locator('div', { hasText: catCode });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('load');
    await adminPage.locator('#name').click();
    await adminPage.locator('#name').fill(updatedName);
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await expect(adminPage.locator('#app').getByText(/category updated successfully/i)).toBeVisible({ timeout: 15000 });
  });

  test('Delete Category', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.getByRole('textbox', { name: 'Search' }).click();
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(catCode);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: catCode });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/category has been successfully deleted/i)).toBeVisible({ timeout: 15000 });
  });

  test('Delete Root Category', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Catalog' }).click();
    await adminPage.getByRole('link', { name: 'Categories' }).click();
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: /\[root\]/ });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/cannot delete the root category/i)).toBeVisible({ timeout: 15000 });
  });
});
