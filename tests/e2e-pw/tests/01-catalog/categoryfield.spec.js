const { test, expect } = require('../../utils/fixtures');
const {
  clickSave,
  navigateTo,
  generateUid,
  clickSaveAndExpect,
  fillLocalizedField,
  openFilterDrawer,
  searchInDataGrid,
  clickEditOnRow,
  clickDeleteOnRow,
  confirmDelete,
} = require('../../utils/helpers');
const { seedDisabledCategoryField, deferCategoryFieldCleanup } = require('../../utils/category-field-helpers');

/** Create a category field via UI; it is enabled, so global teardown deletes it. */
async function createCategoryField(adminPage, code, name, type = 'Text') {
  await navigateTo(adminPage, 'categoryFields');
  await adminPage.getByRole('button', { name: 'Create Category Field' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: type }).first().click();
  await adminPage.locator('input[name$="[name]"]').first().fill(name);
  await clickSaveAndExpect(adminPage, 'Save Category Field', /Category Field Created Successfully/i);
  await expect(adminPage).toHaveURL(/category-fields\/edit\/\d+/);
  deferCategoryFieldCleanup(adminPage);
}

/** Open the category field grid filtered to a single code. */
async function searchCategoryField(adminPage, code) {
  await navigateTo(adminPage, 'categoryFields');
  await searchInDataGrid(adminPage, code);
}

test.describe('UnoPim Category Field Tests', { tag: '@global-state' }, () => {

  test('Create category field with empty Code', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('button', { name: 'Create Category Field' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option', { name: 'Text' }).first().click();
    await adminPage.locator('input[name$="[name]"]').first().fill('Suggestion');
    // v-code derives the code from the name, so clear it to submit an empty code.
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await clickSave(adminPage, 'Save Category Field');
    await expect(adminPage.locator('#app').getByText('The Code field is required ')).toBeVisible();
  });

  test('Create category field with empty Type', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('button', { name: 'Create Category Field' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('test_empty_type');
    await adminPage.locator('input[name$="[name]"]').first().fill('Suggestion');
    await clickSave(adminPage, 'Save Category Field');
    await expect(adminPage.locator('#app').getByText('The Type field is required ')).toBeVisible();
  });

  test('Create category field with empty Code and Type', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('button', { name: 'Create Category Field' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('input[name$="[name]"]').first().fill('Suggestion');
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await clickSave(adminPage, 'Save Category Field');
    await expect(adminPage.locator('#app').getByText('The Code field is required ')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Type field is required ')).toBeVisible();
  });

  test('Create category field', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `cf_${uid}`;

    await createCategoryField(adminPage, code, 'Test Field');
  });

  test('should allow category field search', async ({ adminPage }) => {
    // Seeded 'name' category field always exists.
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('name');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('name', { exact: true }).first()).toBeVisible();
  });

  test('should open the filter menu when clicked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await openFilterDrawer(adminPage);
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('should allow setting items per page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
    await expect(perPageBtn).toBeVisible({ timeout: 20000 });
    await perPageBtn.click();
    await adminPage.getByRole('list').getByText('20', { exact: true }).click();
    await expect(perPageBtn).toContainText('20');
  });

  test('should perform actions on a category field (Edit, Delete)', async ({ adminPage }) => {
    const code = `cf_${generateUid()}`;

    await seedDisabledCategoryField(adminPage, code);

    await searchCategoryField(adminPage, code);
    await clickEditOnRow(adminPage, code);
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/category-fields\/edit/);

    await searchCategoryField(adminPage, code);
    await clickDeleteOnRow(adminPage, code);
    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();

    await confirmDelete(adminPage);
    await expect(adminPage.locator('#app').getByText(/Category Field Deleted Successfully/i)).toBeVisible();
  });

  test('should allow selecting all category fields with the mass action checkbox', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.click('label[for="mass_action_select_all_records"]');
    await expect(adminPage.locator('#mass_action_select_all_records')).toBeChecked();
  });

  test('update category field', async ({ adminPage }) => {
    const code = `cf_${generateUid()}`;

    await createCategoryField(adminPage, code, 'Before Update');

    await searchCategoryField(adminPage, code);
    await clickEditOnRow(adminPage, code);
    await fillLocalizedField(adminPage, 'After Update');
    await clickSaveAndExpect(adminPage, 'Save changes', /Category Field Updated Successfully/i);
  });

  test('delete category field', async ({ adminPage }) => {
    const code = `cf_${generateUid()}`;

    await seedDisabledCategoryField(adminPage, code);

    await searchCategoryField(adminPage, code);
    await clickDeleteOnRow(adminPage, code);
    await confirmDelete(adminPage);
    await expect(adminPage.locator('#app').getByText(/Category Field Deleted Successfully/i)).toBeVisible();
  });

  test('delete default category field', async ({ adminPage }) => {
    // Seeded 'name' field should not be deletable.
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('name');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('div', { hasText: 'name' }).locator('span[title="Delete"]').first().click();
    await adminPage.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();
    await expect(adminPage.locator('#app').getByText(/This category field can not be deleted/i)).toBeVisible();
  });

});
