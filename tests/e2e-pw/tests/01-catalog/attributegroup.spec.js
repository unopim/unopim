const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create an attribute group via UI and return its code.
 */
async function createAttributeGroup(adminPage, code, name) {
  await navigateTo(adminPage, 'attributeGroups');
  await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(name);
  await clickSaveAndExpect(adminPage, 'Save Attribute Group', /Attribute Group Created Successfully/i);
}

/**
 * Helper: Delete an attribute group by code (search → delete → confirm).
 */
async function deleteAttributeGroup(adminPage, code) {
  await navigateTo(adminPage, 'attributeGroups');
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

test.describe('UnoPim Attribute Group Tests', () => {

  test('Create Attribute Group with empty Code field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Product Description');
    await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create Attribute Group', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `grp_${uid}`;

    await createAttributeGroup(adminPage, code, 'Test Group');

    // Cleanup
    await deleteAttributeGroup(adminPage, code);
  });

  test('should allow attribute group search', async ({ adminPage }) => {
    // Use seeded data — 'general' group always exists
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill('general');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('general', { exact: true }).first()).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeGroups');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.locator('#app').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on an attribute group (Edit, Delete)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `grp_${uid}`;

    // Create test data
    await createAttributeGroup(adminPage, code, 'Actions Test');

    // Search and verify Edit action
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributegroups\/edit/);

    // Go back and verify Delete action shows confirmation
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();

    // Cleanup — confirm delete
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Group Deleted Successfully/i)).toBeVisible();
  });

  test('Update attribute group', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `grp_${uid}`;

    // Create test data
    await createAttributeGroup(adminPage, code, 'Before Update');

    // Search and edit
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('After Update');
    await clickSaveAndExpect(adminPage, 'Save Attribute Group', /Attribute Group Updated Successfully/i);

    // Cleanup
    await deleteAttributeGroup(adminPage, code);
  });

  test('Delete Attribute Group', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `grp_${uid}`;

    // Create test data specifically for deletion
    await createAttributeGroup(adminPage, code, 'To Delete');

    // Search and delete
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Group Deleted Successfully/i)).toBeVisible();
  });

});
