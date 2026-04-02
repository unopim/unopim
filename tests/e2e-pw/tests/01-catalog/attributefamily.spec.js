const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create an attribute family via UI.
 */
async function createFamily(adminPage, code, name) {
  await navigateTo(adminPage, 'attributeFamilies');
  await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Enter Code' }).fill(code);
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(name);
  await clickSaveAndExpect(adminPage, 'Save Attribute Family', /Family created successfully/i);
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
    await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Enter Code' }).fill('');
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('Header');
    await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create Attribute family', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `fam_${uid}`;

    await createFamily(adminPage, code, 'Test Family');

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
    await adminPage.getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on an attribute family (Edit, Copy, Delete)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `fam_${uid}`;

    // Create test data
    await createFamily(adminPage, code, 'Actions Test');

    // Edit action
    await searchAndEditFamily(adminPage, code);
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/edit/);

    // Copy action
    await navigateTo(adminPage, 'attributeFamilies');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Copy"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/copy/);

    // Delete action — shows confirmation
    await navigateTo(adminPage, 'attributeFamilies');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row2 = adminPage.locator('div', { hasText: code });
    await row2.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();

    // Cleanup — confirm delete
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Family deleted successfully/i)).toBeVisible();

    // Also delete the copy if it was created
    await deleteFamily(adminPage, code);
  });

  test('Edit Attribute Family', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `fam_${uid}`;

    // Create test data
    await createFamily(adminPage, code, 'Before Edit');

    // Search and edit
    await searchAndEditFamily(adminPage, code);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('After Edit');

    // Assign General attribute group
    await adminPage.locator('.secondary-button', { hasText: 'Assign Attribute Group' }).click();
    await adminPage.locator('input[name="group"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('textbox', { name: 'group-searchbox' }).fill('General');
    await adminPage.getByRole('option', { name: 'General' }).first().click();
    await adminPage.getByRole('button', { name: 'Assign Attribute Group' }).click();

    // Drag SKU attribute to the group
    const dragHandle = adminPage.locator('#unassigned-attributes i.icon-drag:near(:text("SKU"))').first();
    const dropTarget = adminPage.locator('#assigned-attribute-groups .group_node').first();
    const dragBox = await dragHandle.boundingBox();
    const dropBox = await dropTarget.boundingBox();
    if (dragBox && dropBox) {
      await adminPage.mouse.move(dragBox.x + dragBox.width / 2, dragBox.y + dragBox.height / 2);
      await adminPage.mouse.down();
      await adminPage.mouse.move(dropBox.x + dropBox.width / 2, dropBox.y + dropBox.height / 2, { steps: 10 });
      await adminPage.mouse.up();
    }

    await clickSaveAndExpect(adminPage, 'Save Attribute Family', /Family updated successfully/i);

    // Cleanup
    await deleteFamily(adminPage, code);
  });

  test('Delete Attribute Family', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `fam_${uid}`;

    // Create test data specifically for deletion
    await createFamily(adminPage, code, 'To Delete');

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
