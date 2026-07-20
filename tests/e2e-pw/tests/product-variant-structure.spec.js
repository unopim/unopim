const { test, expect } = require('../utils/fixtures');
const { navigateTo, clickSave, generateUid, searchInDataGrid } = require('../utils/helpers');

/**
 * Select a value from a Vue-multiselect dropdown by field name.
 * Mirrors the helper in tests/01-catalog/products.spec.js.
 */
async function selectMultiselect(page, fieldName, optionLabel) {
  const wrapper = page.locator(`input[name="${fieldName}"]`).locator('..');
  await wrapper.locator('.multiselect__tags').click();
  await wrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
  if (optionLabel) {
    await page.getByRole('option', { name: optionLabel }).first().click();
  } else {
    await wrapper
      .locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
      .first()
      .click();
  }
  await page.keyboard.press('Escape');
}

async function deleteProductBySku(adminPage, sku) {
  await navigateTo(adminPage, 'products');
  await searchInDataGrid(adminPage, sku);
  const deleteIcon = adminPage.locator('span[title="Delete"]').first();
  const visible = await deleteIcon.isVisible({ timeout: 3000 }).catch(() => false);
  if (!visible) return;
  await deleteIcon.click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.locator('#app').getByText(/Product deleted successfully/i).waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
}

// The seeded 'Default' family (id 1) already owns a variant structure named
// "Test" (1-level) — created while building the Variant Structure feature.
// Reused here rather than creating a fresh family/structure per run to keep
// this create-flow test focused on the modal, not family setup.
test.describe('Product Creation - Variant Structure selector', () => {
  test('create configurable product picks a variant structure and redirects to edit', async ({ adminPage }) => {
    test.setTimeout(60000);
    const sku = `vs-${generateUid()}`;

    await navigateTo(adminPage, 'products');
    await adminPage.getByRole('button', { name: 'Create Product' }).click();
    await adminPage.waitForLoadState('networkidle');

    await selectMultiselect(adminPage, 'type', 'Configurable');
    await selectMultiselect(adminPage, 'attribute_family_id', 'Default');
    await adminPage.locator('input[name="sku"]').fill(sku);
    await clickSave(adminPage, 'Save Product');

    // Step 2: the variant structure selector replaces the type/family/sku view.
    await expect(adminPage.locator('#app').getByText('Variant Structure').first()).toBeVisible({ timeout: 10000 });
    await selectMultiselect(adminPage, 'variant_structure_id', 'Test (1-level)');
    await clickSave(adminPage, 'Save Product');

    await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/products\/edit\//);

    // Cleanup
    await deleteProductBySku(adminPage, sku);
  });
});
