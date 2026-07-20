const { test, expect } = require('../../utils/family-fixtures');
const { generateUid } = require('../../utils/helpers');
const { gotoIndex, createFamily, deleteFamilyByCode, saveFamilyEdit } = require('../../utils/family-helpers');

/**
 * Attribute Family index + edit smoke tests.
 *
 * Runs against the current branch (served at 8023) via family-fixtures, where the
 * route group is `/admin/catalog/attribute-families` and the create modal requires
 * both Name and Code. Uses the shared family-helpers so the create/delete flow
 * stays in one place.
 */
async function searchAndEditFamily(page, code) {
  await gotoIndex(page);
  await page.getByRole('textbox', { name: 'Search' }).fill(code);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1500);
  await page.locator('div', { hasText: code }).locator('span[title="Edit"]').first().click();
  await page.waitForSelector('.group_node', { timeout: 30000 });
}

test.describe('UnoPim Attribute Family Tests', () => {
  test('Create Attribute family with empty required fields', async ({ adminPage }) => {
    await gotoIndex(adminPage);
    await adminPage.getByRole('button', { name: 'Create Attribute Family' }).click();
    await adminPage.getByPlaceholder('Enter Name').fill('');
    await adminPage.getByPlaceholder('Enter Code').fill('');
    await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
    await expect(adminPage.locator('#app').getByText(/The (Name|Code) field is required/).first()).toBeVisible();
  });

  test('Create Attribute family', async ({ adminPage }) => {
    const code = `fam_${generateUid()}`;
    await createFamily(adminPage, code);
    await expect(adminPage).toHaveURL(/\/attribute-families\/edit\/\d+/);
    await deleteFamilyByCode(adminPage, code);
  });

  test('should allow attribute family search', async ({ adminPage }) => {
    await gotoIndex(adminPage);
    await adminPage.getByRole('textbox', { name: 'Search' }).fill('default');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForTimeout(1500);
    await expect(adminPage.locator('#app').getByText('default', { exact: true }).first()).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await gotoIndex(adminPage);
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should perform actions on an attribute family (Edit, Delete)', async ({ adminPage }) => {
    const code = `fam_${generateUid()}`;

    await createFamily(adminPage, code);
    await expect(adminPage).toHaveURL(/\/attribute-families\/edit/);

    await searchAndEditFamily(adminPage, code);
    await expect(adminPage).toHaveURL(/\/attribute-families\/edit/);

    await gotoIndex(adminPage);
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForTimeout(1500);
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Family deleted successfully/i)).toBeVisible();
  });

  test('Edit Attribute Family', async ({ adminPage }) => {
    const code = `fam_${generateUid()}`;
    await createFamily(adminPage, code);

    const assignedGroups = adminPage.locator('#assigned-attribute-groups');
    await expect(assignedGroups.getByText('General').first()).toBeVisible();
    await expect(assignedGroups.getByText('SKU').first()).toBeVisible();

    const nameInput = adminPage.locator('input[name="en_US\\[name\\]"]');
    await nameInput.fill('After Edit');
    await nameInput.blur();
    await saveFamilyEdit(adminPage);

    await deleteFamilyByCode(adminPage, code);
  });

  test('Delete Attribute Family', async ({ adminPage }) => {
    const code = `fam_${generateUid()}`;
    await createFamily(adminPage, code);

    await gotoIndex(adminPage);
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForTimeout(1500);
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Family deleted successfully/i)).toBeVisible();
  });
});
