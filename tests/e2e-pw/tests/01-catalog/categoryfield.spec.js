const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create a category field via UI.
 */
async function createCategoryField(adminPage, code, name, type = 'Text') {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: type }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(name);
  await clickSaveAndExpect(adminPage, 'Save Category Field', /Category Field Created Successfully/i);
}

/**
 * Helper: Delete a category field by code.
 */
async function deleteCategoryField(adminPage, code) {
  await navigateTo(adminPage, 'categoryFields');
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

test.describe('UnoPim Category Field Tests', () => {

  test('Create category field with empty Code', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
    await adminPage.getByRole('option', { name: 'Text' }).first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
    await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required ')).toBeVisible();
  });

  test('Create category field with empty Type', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('test_empty_type');
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
    await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(adminPage.locator('#app').getByText('The Type field is required ')).toBeVisible();
  });

  test('Create category field with empty Code and Type', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Suggestion');
    await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required ')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Type field is required ')).toBeVisible();
  });

  test('Create category field', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cf_${uid}`;

    await createCategoryField(adminPage, code, 'Test Field');

    // Cleanup
    await deleteCategoryField(adminPage, code);
  });

  test('should allow category field search', async ({ adminPage }) => {
    // Use seeded data — 'name' category field always exists
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill('name');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('name', { exact: true }).first()).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a category field (Edit, Delete)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cf_${uid}`;

    // Create test data
    await createCategoryField(adminPage, code, 'Actions Test');

    // Search and verify Edit action
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/category-fields\/edit/);

    // Go back, search again, verify Delete shows confirmation
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row2 = adminPage.locator('div', { hasText: code });
    await row2.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();

    // Cleanup — confirm delete
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Category Field Deleted Successfully/i)).toBeVisible();
  });

  test('should allow selecting all category fields with the mass action checkbox', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.click('label[for="mass_action_select_all_records"]');
    await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
  });

  test('update category field', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cf_${uid}`;

    // Create test data
    await createCategoryField(adminPage, code, 'Before Update');

    // Search and edit
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('After Update');
    await clickSaveAndExpect(adminPage, 'Save Category Field', /Category Field Updated Successfully/i);

    // Cleanup
    await deleteCategoryField(adminPage, code);
  });

  test('delete category field', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cf_${uid}`;

    // Create test data specifically for deletion
    await createCategoryField(adminPage, code, 'To Delete');

    // Search and delete
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Category Field Deleted Successfully/i)).toBeVisible();
  });

  test('delete default category field', async ({ adminPage }) => {
    // Use seeded 'name' field — should not be deletable
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill('name');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('div', { hasText: 'name' }).locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/This category field can not be deleted/i)).toBeVisible();
  });

});
