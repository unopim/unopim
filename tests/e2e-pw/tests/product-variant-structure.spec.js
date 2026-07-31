const { test, expect } = require('../utils/fixtures');
const { navigateTo, clickSave, generateUid, searchInDataGrid } = require('../utils/helpers');

/** Select a value from a Vue-multiselect dropdown by field name (mirrors products.spec.js). */
async function selectMultiselect(page, fieldName, optionLabel) {
  const wrapper = page.locator(`input[name="${fieldName}"]`)
    .locator('xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " multiselect ")][1]');
  await wrapper.locator('.multiselect__tags').click();
  await wrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
  if (optionLabel) {
    await wrapper.locator(`input[name="${fieldName}"][type="text"]`).fill(optionLabel).catch(() => {});
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

const FAMILY_ID = 1;
const COLOR_SIZE_STRUCTURE_CODE = 'e2e_color_size_structure';

/**
 * Ensure the `default` family (id 1, guaranteed to exist on any install) carries a
 * two-axis (Color, Size) variant structure named "Based on Color and Size". This
 * environment's seed doesn't include the demo catalog (no `Electronics` family
 * survives here — see PRODUCT BUG / environment note in the task report).
 * `saveVariantStructures` replaces the family's whole structure list, so existing
 * structures (e.g. `variant-structure-editor.spec.js`'s own `e2e_variant_structure`
 * fixture) are fetched first and resubmitted alongside the new one rather than
 * being dropped.
 */
async function ensureColorSizeVariantStructure(adminPage) {
  const result = await adminPage.evaluate(async ({ familyId, code }) => {
    const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
    const headers = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrf,
    };

    const listRes = await fetch(`/admin/catalog/attribute-families/edit/${familyId}/variant-structures`, {
      headers,
    });
    const listBody = await listRes.json();
    const existing = listBody.data ?? [];

    if (existing.some((structure) => structure.code === code)) {
      return { status: 200, body: 'already exists' };
    }

    const structures = [
      ...existing.map(({ code: c, name, levels, axes, placements }) => ({ code: c, name, levels, axes, placements })),
      {
        code,
        name: 'Based on Color and Size',
        levels: 1,
        axes: { level_1: ['color', 'size'] },
        placements: {},
      },
    ];

    const res = await fetch(`/admin/catalog/attribute-families/edit/${familyId}/variant-structures`, {
      method: 'POST',
      headers,
      body: JSON.stringify({ _method: 'PUT', structures }),
    });

    return { status: res.status, body: await res.text() };
  }, { familyId: FAMILY_ID, code: COLOR_SIZE_STRUCTURE_CODE });

  if (result.status >= 300) {
    throw new Error(`variant structure setup failed: ${result.body}`);
  }
}

// Reuses a `default`-family variant structure rather than creating a family per run,
// to keep the focus on the modal.
test.describe('Product Creation - Variant Structure selector', () => {
  test('create configurable product picks a variant structure and redirects to edit', async ({ adminPage }) => {
    test.setTimeout(60000);
    const sku = `vs-${generateUid()}`;

    await navigateTo(adminPage, 'products');
    await ensureColorSizeVariantStructure(adminPage);
    await adminPage.getByRole('button', { name: 'Create Product' }).click();
    await adminPage.waitForLoadState('networkidle');

    await selectMultiselect(adminPage, 'type', 'Configurable');
    // `default` (id 1) now carries the "Based on Color and Size" structure ensured above.
    await selectMultiselect(adminPage, 'attribute_family_id', 'Default');
    await adminPage.locator('input[name="sku"]').fill(sku);

    // Scope submits to the modal; the datagrid's pagination also exposes a "Next" button.
    const createModal = adminPage.locator('.fixed').filter({ hasText: 'Create New Product' }).first();

    await createModal.getByRole('button', { name: 'Next', exact: true }).click();

    await expect(adminPage.locator('#app').getByText('Variant Structure').first()).toBeVisible({ timeout: 10000 });
    await selectMultiselect(adminPage, 'variant_structure_id', 'Based on Color and Size');
    // Step 2 swaps the modal content, so scope this submit to the page instead.
    await adminPage.getByRole('button', { name: 'Save Product', exact: true }).click();

    await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/products\/edit\//);

    // A multi-axis level is labelled by all its axes; its add modal needs one option per axis.
    await expect(adminPage.getByText(/^\s*Color,\s*Size\s*$/i).first()).toBeVisible({ timeout: 15000 });

    await adminPage.getByRole('button', { name: /Select Color, Size/i }).click();
    await adminPage.getByRole('button', { name: 'Add New', exact: true }).click();

    const addModal = adminPage.locator('.fixed').filter({ hasText: /Add a new Color, Size/i }).first();

    await addModal.waitFor({ state: 'visible', timeout: 10000 });

    await expect(addModal.locator('.multiselect')).toHaveCount(2);
    await expect(addModal.getByRole('button', { name: 'Create', exact: true })).toBeDisabled();

    await addModal.getByRole('button', { name: 'Cancel', exact: true }).click();

    await deleteProductBySku(adminPage, sku);
  });
});
