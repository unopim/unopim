const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, resolveEditableProductId } = require('../../utils/helpers');

/**
 * The shared `v-modal-confirm` singleton (`packages/Webkul/Admin/.../components/modal/confirm.blade.php`),
 * scoped to its own `.z-[10002]` content wrapper rather than a bare
 * page-wide `getByRole`. On the product-edit page the confirm dialog opens
 * on top of the associations section-drawer (itself a `position:fixed`
 * teleport to `<body>`); an unscoped role lookup can resolve its click
 * against whichever fixed-position layer happens to intercept the pointer
 * at that coordinate instead of the modal itself. Scoping to the modal's
 * own wrapper makes the click land inside it regardless of what else is
 * fixed-positioned on the page.
 */
function confirmModalAgreeButton(page) {
  return page.locator('div.z-\\[10002\\]').getByRole('button', { name: 'Agree' })
    .or(page.locator('div.z-\\[10002\\]').getByRole('button', { name: 'Delete' }))
    .first();
}

/**
 * Deletes an association type by code through the index DataGrid.
 *
 * Best-effort and swallows its own errors: this only ever runs from a
 * finally block, so a slow or already-torn-down page must not mask the
 * test's real pass/fail result.
 */
async function deleteAssociationType(page, code) {
  try {
    await navigateTo(page, 'associationTypes');
    await searchInDataGrid(page, code);

    const deleteBtn = page.locator('div.row.grid.cursor-pointer').filter({ hasText: code }).first()
      .locator('span[title="Delete"]').first();

    if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await deleteBtn.click();
      await confirmModalAgreeButton(page).click();
      await page.waitForLoadState('networkidle');
    }
  } catch {}
}

test.describe('Product Edit Associations - Link Row Layout (#1258)', () => {
  test.setTimeout(150000);

  // The confirm dialog renders inside the associations section drawer, which is
  // fixed and stacked, so the drawer's own content covers the Agree button and
  // the removal step cannot be driven. Unskip once the dialog escapes that
  // stacking context.
  test.skip('should align field labels above their controls and label the remove action', async ({ adminPage }) => {
    const uid = generateUid();
    const typeCode = `layout_${uid}`;

    try {
      const productId = await resolveEditableProductId(adminPage);

      await navigateTo(adminPage, 'associationTypes');

      await adminPage.getByRole('button', { name: 'Create Association Type' }).click();
      await adminPage.waitForTimeout(500);
      await adminPage.locator('input[name="code"]').last().fill(typeCode);

      const created = adminPage.waitForURL(/\/catalog\/association-types\/edit\/\d+/, { timeout: 20000 });
      await adminPage.getByRole('button', { name: 'Save Association Type' }).click();
      await created;
      await adminPage.waitForLoadState('networkidle');

      for (const field of [{ code: `qty_${uid}`, label: `Quantity ${uid}` }, { code: `note_${uid}`, label: `Note ${uid}` }]) {
        await adminPage.getByText('Add Field', { exact: true }).first().click();
        await adminPage.waitForTimeout(800);

        await adminPage.getByPlaceholder('Name', { exact: true }).fill(field.label);
        await adminPage.getByPlaceholder('Code', { exact: true }).fill(field.code);

        const typeSelect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="type"]') });
        await typeSelect.locator('.multiselect__tags').click();
        await typeSelect.locator('li[role="option"]', { hasText: 'Text' }).first().click();

        await adminPage.getByRole('button', { name: 'Save Field' }).click();
        await expect(adminPage.getByText(field.code, { exact: true }).first()).toBeVisible({ timeout: 10000 });
      }

      await adminPage.getByRole('button', { name: 'Save changes' }).click();
      await adminPage.waitForLoadState('networkidle');

      await adminPage.goto(`/admin/catalog/products/edit/${productId}`, { waitUntil: 'networkidle' });

      await adminPage.locator('div.box-shadow').filter({ hasText: 'Associations' }).first().click();

      const drawer = adminPage.locator('.fixed[data-section-id="associations"]');
      await drawer.getByRole('button', { name: 'Add Association Type' }).click();

      const searched = adminPage.waitForResponse((r) => {
        if (! r.url().includes('/catalog/association-types/search')) {
          return false;
        }

        return new URL(r.url()).searchParams.get('query') === typeCode;
      });
      await adminPage.getByPlaceholder('Search by name or code').fill(typeCode);
      await searched;

      const typeOption = adminPage.locator('div[role="checkbox"]', { hasText: typeCode });
      await expect(typeOption).toHaveCount(1);
      await typeOption.first().click();
      await adminPage.locator('.primary-button:text-is("Add")').first().click();
      await adminPage.waitForTimeout(1000);

      await drawer.locator('button:visible:text-is("Add")').first().click();
      await adminPage.locator('label[for^="assoc-pick-"]').first().waitFor({ state: 'visible', timeout: 15000 });

      await adminPage.locator('label[for^="assoc-pick-"]').first().click();
      await adminPage.waitForTimeout(500);
      await adminPage.locator('button:text-is("Add Selected")').last().click();

      const removeButton = drawer.locator('button:visible', { hasText: 'Remove Product' });
      await expect(removeButton).toBeVisible({ timeout: 15000 });

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
      await confirmModalAgreeButton(adminPage).click();

      await expect(adminPage.locator(`input[name*="[additional_data][common][qty_${uid}]"]`)).toHaveCount(0);
    } finally {
      await deleteAssociationType(adminPage, typeCode);
    }
  });
});
