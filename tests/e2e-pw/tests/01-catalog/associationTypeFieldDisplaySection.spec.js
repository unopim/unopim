const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

const SECTION_CONTROL = 'select[name="section"], input[name="section"]';

test.describe('Association Type - Field Configuration Display Section (#1255)', () => {
  test.setTimeout(90000);

  test('should not show the Display Section control when adding or editing a field', async ({ adminPage }) => {
    const uid = generateUid();

    await navigateTo(adminPage, 'associationTypes');

    await adminPage.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('domcontentloaded');

    await adminPage.getByText('Add Field', { exact: true }).first().click();
    await adminPage.waitForTimeout(1000);

    await expect(adminPage.locator(SECTION_CONTROL)).toHaveCount(0);

    await adminPage.getByPlaceholder('Name', { exact: true }).fill(`Display Section ${uid}`);
    await adminPage.getByPlaceholder('Code', { exact: true }).fill(`dsec_${uid}`);

    const typeSelect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="type"]') });
    await typeSelect.locator('.multiselect__tags').click();
    await typeSelect.locator('li[role="option"]', { hasText: 'Text' }).first().click();

    await adminPage.getByRole('button', { name: 'Save Field' }).click();
    await adminPage.waitForTimeout(1000);

    const fieldRow = adminPage.locator('tr', { hasText: `dsec_${uid}` }).first();
    await expect(fieldRow).toBeVisible();

    await fieldRow.locator('.icon-edit').click();
    await adminPage.waitForTimeout(1000);

    await expect(adminPage.locator(SECTION_CONTROL)).toHaveCount(0);
  });
});
