const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, resolveEditableProductId } = require('../../utils/helpers');

test.describe('Product Edit Associations - Boolean Field (#1249)', () => {
  test.setTimeout(120000);

  test('should toggle and hold the value of a boolean association field', async ({ adminPage }) => {
    const uid = generateUid();
    const typeCode = `boolfld_${uid}`;
    const fieldCode = `flag_${uid}`;
    const productId = await resolveEditableProductId(adminPage);

    await navigateTo(adminPage, 'associationTypes');

    await adminPage.getByRole('button', { name: 'Create Association Type' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').last().fill(typeCode);
    await adminPage.getByRole('button', { name: 'Save Association Type' }).click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    await adminPage.getByText('Add Field', { exact: true }).first().click();
    await adminPage.waitForTimeout(800);

    await adminPage.getByPlaceholder('Name', { exact: true }).fill(`Flag ${uid}`);
    await adminPage.getByPlaceholder('Code', { exact: true }).fill(fieldCode);

    const typeSelect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="type"]') });
    await typeSelect.locator('.multiselect__tags').click();
    await typeSelect.locator('li[role="option"]', { hasText: 'Boolean' }).first().click();

    await adminPage.getByRole('button', { name: 'Save Field' }).click();
    await adminPage.waitForTimeout(800);

    await adminPage.getByRole('button', { name: 'Save changes' }).click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1500);

    await adminPage.goto(`/admin/catalog/products/edit/${productId}`, { waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(1500);

    await adminPage.locator('div.box-shadow').filter({ hasText: 'Associations' }).first().click();
    await adminPage.waitForTimeout(1000);

    const drawer = adminPage.locator('.fixed[data-section-id="associations"]');
    await expect(drawer).toBeVisible();

    await drawer.getByRole('button', { name: 'Add Association Type' }).click();
    await adminPage.waitForTimeout(1000);

    await adminPage.getByPlaceholder('Search by name or code').fill(typeCode);
    await adminPage.waitForTimeout(1000);

    await adminPage.locator('div[role="checkbox"]', { hasText: typeCode }).first().click();
    await adminPage.locator('.primary-button:text-is("Add")').first().click();
    await adminPage.waitForTimeout(1000);

    await drawer.locator('button:visible:text-is("Add")').first().click();
    await adminPage.waitForTimeout(1800);

    await adminPage.locator('label[for^="assoc-pick-"]').first().click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('button:text-is("Add Selected")').last().click();
    await adminPage.waitForTimeout(1800);

    const checkbox = adminPage.locator(`input[type="checkbox"][name*="[additional_data][common][${fieldCode}]"]`);
    await expect(checkbox).toHaveCount(1);
    await expect(checkbox).not.toBeChecked();

    const toggle = adminPage.locator(`span.relative label[for*="[additional_data][common][${fieldCode}]"]`);
    await expect(toggle).toHaveCount(1);

    await toggle.click();
    await adminPage.waitForTimeout(500);

    await expect(checkbox).toBeChecked();

    const submitted = await adminPage.evaluate(() => {
      const form = document.getElementById('product-edit-form');

      return [...new FormData(form).entries()]
        .filter(([key]) => key.includes('additional_data'))
        .map(([key, value]) => `${key}=${value}`);
    });

    expect(submitted[submitted.length - 1]).toContain('=true');
  });
});
