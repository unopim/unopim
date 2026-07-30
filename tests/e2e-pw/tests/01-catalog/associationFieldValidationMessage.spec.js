const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

const PRODUCT_EDIT_ID = process.env.PRODUCT_EDIT_ID || 14;

test.describe('Product Edit Associations - Field Validation Message (#1243)', () => {
  test.setTimeout(120000);

  test('should name the field by its label instead of the request path', async ({ adminPage }) => {
    const uid = generateUid();
    const typeCode = `valmsg_${uid}`;
    const fieldCode = `qty_${uid}`;
    const fieldLabel = `Quantity ${uid}`;

    await navigateTo(adminPage, 'associationTypes');

    await adminPage.getByRole('button', { name: 'Create Association Type' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').last().fill(typeCode);
    await adminPage.getByRole('button', { name: 'Save Association Type' }).click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    await adminPage.getByText('Add Field', { exact: true }).first().click();
    await adminPage.waitForTimeout(800);

    await adminPage.getByPlaceholder('Name', { exact: true }).fill(fieldLabel);
    await adminPage.getByPlaceholder('Code', { exact: true }).fill(fieldCode);

    const typeSelect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="type"]') });
    await typeSelect.locator('.multiselect__tags').click();
    await typeSelect.locator('li[role="option"]', { hasText: 'Text' }).first().click();

    const validationSelect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="validation"]') });
    await validationSelect.locator('.multiselect__tags').click();
    await validationSelect.locator('li[role="option"]', { hasText: 'Number' }).first().click();

    await adminPage.getByRole('button', { name: 'Save Field' }).click();
    await adminPage.waitForTimeout(800);

    await adminPage.getByRole('button', { name: 'Save changes' }).click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1500);

    await adminPage.goto(`/admin/catalog/products/edit/${PRODUCT_EDIT_ID}`, { waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(1500);

    await adminPage.getByRole('button', { name: /associations/i }).first().evaluate((el) => el.click());
    await adminPage.waitForTimeout(800);

    const drawer = adminPage.locator('.fixed[data-section-id="associations"]');
    await expect(drawer).toBeVisible();

    await drawer.getByRole('button', { name: 'Add Association Type' }).click();
    await adminPage.waitForTimeout(800);

    await adminPage.locator('div[role="checkbox"]', { hasText: typeCode }).first().click();
    await adminPage.locator('.primary-button:text-is("Add")').first().click();
    await adminPage.waitForTimeout(800);

    await drawer.locator('button:text-is("Add")').first().click();
    await adminPage.waitForTimeout(1500);

    await adminPage.locator('label[for^="assoc-pick-"]').first().click();
    await adminPage.waitForTimeout(500);

    await adminPage.locator('button:text-is("Add Selected")').last().click();
    await adminPage.waitForTimeout(1500);

    const fieldInput = adminPage.locator(`input[name*="[additional_data][common][${fieldCode}]"]`);
    await expect(fieldInput).toBeVisible();

    await fieldInput.fill('abcd');
    await fieldInput.blur();
    await adminPage.waitForTimeout(1500);

    const error = adminPage.locator('p.text-red-600').filter({ hasText: /numeric/i }).first();
    await expect(error).toBeVisible();

    const message = await error.textContent();

    expect(message).toContain(fieldLabel);
    expect(message).not.toContain('additional_data');
    expect(message).not.toContain('associations[');
  });
});
