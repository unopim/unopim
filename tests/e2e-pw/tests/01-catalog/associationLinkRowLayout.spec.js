const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

const PRODUCT_EDIT_ID = process.env.PRODUCT_EDIT_ID || 14;

test.describe('Product Edit Associations - Link Row Layout (#1258)', () => {
  test.setTimeout(150000);

  test('should align field labels above their controls and label the remove action', async ({ adminPage }) => {
    const uid = generateUid();
    const typeCode = `layout_${uid}`;

    await navigateTo(adminPage, 'associationTypes');

    await adminPage.getByRole('button', { name: 'Create Association Type' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').last().fill(typeCode);
    await adminPage.getByRole('button', { name: 'Save Association Type' }).click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    for (const field of [{ code: `qty_${uid}`, label: `Quantity ${uid}` }, { code: `note_${uid}`, label: `Note ${uid}` }]) {
      await adminPage.getByText('Add Field', { exact: true }).first().click();
      await adminPage.waitForTimeout(800);

      await adminPage.getByPlaceholder('Name', { exact: true }).fill(field.label);
      await adminPage.getByPlaceholder('Code', { exact: true }).fill(field.code);

      const typeSelect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="type"]') });
      await typeSelect.locator('.multiselect__tags').click();
      await typeSelect.locator('li[role="option"]', { hasText: 'Text' }).first().click();

      await adminPage.getByRole('button', { name: 'Save Field' }).click();
      await adminPage.waitForTimeout(800);
    }

    await adminPage.getByRole('button', { name: 'Save changes' }).click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1500);

    await adminPage.goto(`/admin/catalog/products/edit/${PRODUCT_EDIT_ID}`, { waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(1500);

    await adminPage.locator('div.box-shadow').filter({ hasText: 'Associations' }).first().click();
    await adminPage.waitForTimeout(1000);

    const drawer = adminPage.locator('.fixed[data-section-id="associations"]');
    await drawer.getByRole('button', { name: 'Add Association Type' }).click();
    await adminPage.waitForTimeout(1000);

    await adminPage.locator('div[role="checkbox"]', { hasText: typeCode }).first().click();
    await adminPage.locator('.primary-button:text-is("Add")').first().click();
    await adminPage.waitForTimeout(1000);

    await drawer.locator('button:text-is("Add")').first().click();
    await adminPage.waitForTimeout(1800);

    await adminPage.locator('label[for^="assoc-pick-"]').first().click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('button:text-is("Add Selected")').last().click();
    await adminPage.waitForTimeout(1800);

    const removeButton = drawer.getByRole('button', { name: 'Remove Product' });
    await expect(removeButton).toBeVisible();

    const geometry = await adminPage.evaluate((fieldCode) => {
      const input = document.querySelector(`input[name*="[additional_data][common][${fieldCode}]"]`);
      const group = input.closest('[data-control-group]') || input.closest('div').parentElement;
      const label = group.querySelector('label');

      const labelRect = label.getBoundingClientRect();
      const inputRect = input.getBoundingClientRect();

      return {
        labelBottom: labelRect.bottom,
        inputTop: inputRect.top,
        labelLeft: labelRect.left,
        inputLeft: inputRect.left,
      };
    }, `qty_${uid}`);

    expect(geometry.labelBottom).toBeLessThanOrEqual(geometry.inputTop + 1);
    expect(Math.abs(geometry.labelLeft - geometry.inputLeft)).toBeLessThanOrEqual(2);

    await removeButton.click();
    await adminPage.waitForTimeout(800);

    await adminPage.getByRole('button', { name: 'Agree' }).or(adminPage.getByRole('button', { name: 'Delete' })).first().click();
    await adminPage.waitForTimeout(1200);

    await expect(adminPage.locator(`input[name*="[additional_data][common][qty_${uid}]"]`)).toHaveCount(0);
  });
});
