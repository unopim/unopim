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

    // Scope submits to the modal: the datagrid's pagination also exposes a "Next"
    // button, and a configurable is created in two steps (Next, then Save Product).
    const createModal = adminPage.locator('.fixed').filter({ hasText: 'Create New Product' }).first();

    await createModal.getByRole('button', { name: 'Next', exact: true }).click();

    // Step 2: the variant structure selector replaces the type/family/sku view.
    await expect(adminPage.locator('#app').getByText('Variant Structure').first()).toBeVisible({ timeout: 10000 });
    await selectMultiselect(adminPage, 'variant_structure_id', 'product by color,size and brand (1-level)');
    // Step 2 swaps the modal content, so scope this submit to the page instead.
    await adminPage.getByRole('button', { name: 'Save Product', exact: true }).click();

    await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/products\/edit\//);

    // A level that splits on several axes is labelled by all of them, and its
    // "add" modal asks for one option per axis before it will create anything.
    await expect(adminPage.getByText(/^\s*color,\s*size,\s*brand\s*$/i).first()).toBeVisible({ timeout: 15000 });

    await adminPage.getByRole('button', { name: /Select Color, Size, Brand/i }).click();
    await adminPage.getByRole('button', { name: 'Add New', exact: true }).click();

    const addModal = adminPage.locator('.fixed').filter({ hasText: /Add a new Color, Size, Brand/i }).first();

    await addModal.waitFor({ state: 'visible', timeout: 10000 });

    await expect(addModal.locator('.multiselect')).toHaveCount(3);
    await expect(addModal.getByRole('button', { name: 'Create', exact: true })).toBeDisabled();

    await addModal.getByRole('button', { name: 'Cancel', exact: true }).click();

    // Cleanup
    await deleteProductBySku(adminPage, sku);
  });
});
