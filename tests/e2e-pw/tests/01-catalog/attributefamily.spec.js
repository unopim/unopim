const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create an attribute family via the create-family modal on the index
 * page. The modal only accepts a `code` (and, once other families exist, an
 * optional `based_on` family to clone the structure from) — it lands directly
 * on the new family's edit page, already scaffolded with a General group
 * holding SKU.
 */
async function createFamily(adminPage, code) {
  await navigateTo(adminPage, 'attributeFamilies');
  await adminPage.getByRole('button', { name: 'Create Attribute Family' }).click();
  await adminPage.getByPlaceholder('Enter Code').fill(code);
  await clickSaveAndExpect(
    adminPage,
    'Save Attribute Family',
    /Family created successfully/i,
    /\/admin\/catalog\/attribute-families\/edit\/\d+/
  );
}

/**
 * Helper: Search and click Edit on a family by code.
 */
async function searchAndEditFamily(adminPage, code) {
  await navigateTo(adminPage, 'attributeFamilies');
  await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const row = adminPage.locator('div', { hasText: code });
  await row.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('networkidle');
}

/**
 * Helper: Delete a family by code (search → delete → confirm).
 */
async function deleteFamily(adminPage, code) {
  await navigateTo(adminPage, 'attributeFamilies');
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

test.describe('UnoPim Attribute Family Tests', () => {

  test('Create Attribute family with empty code field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeFamilies');
    await adminPage.getByRole('button', { name: 'Create Attribute Family' }).click();
    await adminPage.getByPlaceholder('Enter Code').fill('');
    await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create Attribute family', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `fam_${uid}`;

    await createFamily(adminPage, code);

    // Cleanup
    await deleteFamily(adminPage, code);
  });

  test('should allow attribute family search', async ({ adminPage }) => {
    // Use seeded data — 'default' family always exists
    await navigateTo(adminPage, 'attributeFamilies');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill('default');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('default', { exact: true }).first()).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeFamilies');
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeFamilies');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.locator('#app').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on an attribute family (Edit, Delete)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `fam_${uid}`;

    // Create test data — modal creation lands directly on the edit page
    await createFamily(adminPage, code);
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attribute-families\/edit/);

    // Edit action (from the datagrid)
    await searchAndEditFamily(adminPage, code);
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attribute-families\/edit/);

    // Delete action — shows confirmation
    await navigateTo(adminPage, 'attributeFamilies');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();

    // Confirm delete
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Family deleted successfully/i)).toBeVisible();
  });

  test('Edit Attribute Family', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `fam_${uid}`;

    // Create test data — modal creation lands directly on the edit page, already
    // scaffolded with a General group holding SKU.
    await createFamily(adminPage, code);

    const assignedGroups = adminPage.locator('#assigned-attribute-groups');
    await expect(assignedGroups.getByText('General').first()).toBeVisible();
    await expect(assignedGroups.getByText('SKU').first()).toBeVisible();

    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('After Edit');

    await clickSaveAndExpect(adminPage, 'Save Attribute Family', /Family updated successfully/i);

    // Cleanup
    await deleteFamily(adminPage, code);
  });

  test('Delete Attribute Family', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `fam_${uid}`;

    // Create test data specifically for deletion
    await createFamily(adminPage, code);

    // Search and delete
    await navigateTo(adminPage, 'attributeFamilies');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Family deleted successfully/i)).toBeVisible();
  });

});
