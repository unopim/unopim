const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, clickSaveAndExpect, fillLocalizedField } = require('../../utils/helpers');

/** Create an attribute group via UI. */
async function createAttributeGroup(adminPage, code, name) {
  await navigateTo(adminPage, 'attributeGroups');
  await adminPage.getByRole('button', { name: 'Create Attribute Group' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await fillLocalizedField(adminPage, name);
  await clickSaveAndExpect(adminPage, 'Save Attribute Group', /Attribute Group Created Successfully/i);
}

/** Delete an attribute group by code (search, delete, confirm). */
async function deleteAttributeGroup(adminPage, code) {
  await navigateTo(adminPage, 'attributeGroups');
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const deleteBtn = adminPage.locator('div', { hasText: code }).locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();
    await adminPage.waitForLoadState('networkidle');
  }
}

test.describe('UnoPim Attribute Group Tests', () => {

  test('Create Attribute Group with empty Code field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('button', { name: 'Create Attribute Group' }).click();
    await adminPage.locator('input[name$="[name]"]').first().fill('Product Description');
    // v-code derives the code from the name, so clear it to submit an empty code.
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await clickSave(adminPage, 'Save Attribute Group');
    await expect(adminPage.locator('#app').getByText('The Code field is required').first()).toBeVisible();
  });

  test('Create Attribute Group', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `grp_${uid}`;

    await createAttributeGroup(adminPage, code, 'Test Group');

    await deleteAttributeGroup(adminPage, code);
  });

  test('should allow attribute group search', async ({ adminPage }) => {
    // Seeded 'general' group always exists.
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('general');
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
    await adminPage.getByRole('list').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on an attribute group (Edit, Delete)', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `grp_${uid}`;

    await createAttributeGroup(adminPage, code, 'Actions Test');

    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attribute-groups\/edit/);

    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const row = adminPage.locator('div', { hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();

    await adminPage.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Group Deleted Successfully/i)).toBeVisible();
  });

  test('Update attribute group', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `grp_${uid}`;

    await createAttributeGroup(adminPage, code, 'Before Update');

    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Edit"]').first().click();
    await fillLocalizedField(adminPage, 'After Update');
    await clickSaveAndExpect(adminPage, 'Save changes', /Attribute Group Updated Successfully/i);

    await deleteAttributeGroup(adminPage, code);
  });

  test('Delete Attribute Group', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `grp_${uid}`;

    await createAttributeGroup(adminPage, code, 'To Delete');

    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: code });
    await itemRow.locator('span[title="Delete"]').first().click();
    await adminPage.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();
    await expect(adminPage.locator('#app').getByText(/Attribute Group Deleted Successfully/i)).toBeVisible();
  });

});
