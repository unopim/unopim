const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create a category via UI.
 */
async function createCategory(adminPage, code, name) {
  await navigateTo(adminPage, 'categories');
  await adminPage.getByRole('link', { name: 'Create Category' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.locator('input[name="code"]').fill(code);
  await adminPage.locator('#name').fill(name);
  await clickSaveAndExpect(adminPage, 'Save Category', /category created successfully/i);
}

/**
 * Helper: Delete a category by code.
 */
async function deleteCategory(adminPage, code) {
  await navigateTo(adminPage, 'categories');
  await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const deleteBtn = adminPage.locator('div', { hasText: code }).locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
}

test.describe('UnoPim Category Tests', () => {

  test('Create Categories with empty Code field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('#name').fill('Television');
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await expect(adminPage.locator('#app').getByText('The code field is required')).toBeVisible();
  });

  test('Create Categories with empty Name field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('input[name="code"]').fill('television_empty_name');
    await adminPage.locator('#name').fill('');
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
  });

  test('Create Categories with empty Code and Name field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('input[name="code"]').fill('');
    await adminPage.locator('#name').fill('');
    await adminPage.getByRole('button', { name: 'Save Category' }).click();
    await expect(adminPage.locator('#app').getByText('The code field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
  });

  test('Create Categories with all fields', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cat_${uid}`;

    await createCategory(adminPage, code, `Television ${uid}`);

    // Cleanup
    await deleteCategory(adminPage, code);
  });

  test('should allow category search', async ({ adminPage }) => {
    // Use seeded root category
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill('root');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('root', { exact: true }).first()).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.locator('#app').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a category (Edit, Delete)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cat_${uid}`;

    // Create test data
    await createCategory(adminPage, code, `Action Cat ${uid}`);

    // Search and verify Edit action
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/categories\/edit/);

    // Go back, search again, verify Delete shows confirmation
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row2 = adminPage.locator('div', { hasText: code });
    await row2.locator('span[title="Delete"]').first().click();
    await expect(adminPage.locator('#app').getByText('Are you sure you want to delete?')).toBeVisible();

    // Cleanup — confirm delete
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/category has been successfully deleted/i)).toBeVisible({ timeout: 20000 });
  });

  test('should allow selecting all categories with the mass action checkbox', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    await adminPage.click('label[for="mass_action_select_all_records"]');
    await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
  });

  test('Update Categories', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cat_${uid}`;

    // Create test data
    await createCategory(adminPage, code, `Before Update ${uid}`);

    // Search and edit
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('#name').fill(`Updated ${uid}`);
    await clickSaveAndExpect(adminPage, 'Save Category', /category updated successfully/i);

    // Cleanup
    await deleteCategory(adminPage, code);
  });

  test('Delete Category', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cat_${uid}`;

    // Create test data specifically for deletion
    await createCategory(adminPage, code, `Delete Me ${uid}`);

    // Search and delete
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/category has been successfully deleted/i)).toBeVisible({ timeout: 20000 });
  });

  test('Delete Root Category', async ({ adminPage }) => {
    // Root category should not be deletable
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill('root');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: /\[root\]/ });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/cannot delete the root category/i)).toBeVisible({ timeout: 20000 });
  });

});
