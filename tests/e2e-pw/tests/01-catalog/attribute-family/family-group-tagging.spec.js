const { test, expect } = require('../../../utils/family-fixtures');
const { generateUid } = require('../../../utils/helpers');
const { createFamily, deleteFamilyByCode, selectMultiselect } = require('../../../utils/family-helpers');

/**
 * Regression: creating a brand-new Attribute Group from the "Assign Attribute Group"
 * modal (taggable multiselect) must ADD to the current selection, not replace it.
 * Reported bug: previously selected groups get unselected the moment a new one is
 * created, and the freshly created group must show its typed (translated) name —
 * never the "[code]" non-translated fallback.
 */
test.describe('Attribute Family — assign-group modal tagging', () => {
  test('creating a new group keeps previously selected groups selected', async ({ adminPage }) => {
    test.slow();
    const page = adminPage;
    const { code } = await createFamily(page);

    await page.getByText('Assign Attribute Group', { exact: true }).first().click();
    await page.waitForTimeout(600);

    // 1) Select an existing group first — one tag chip appears.
    await selectMultiselect(page, 'group');
    const tags = page.locator('.multiselect__tags-wrap .multiselect__tag');
    await expect(tags).toHaveCount(1);
    const firstTagLabel = (await tags.first().innerText()).trim();

    // 2) Type a brand-new group name and press Enter to create it inline.
    const newName = `Grp ${generateUid()}`;
    const input = page.locator('input[name="group"]').first();
    await input.pressSequentially(newName, { delay: 15 });
    await page.keyboard.press('Enter');

    // Inline create round-trips to the server; wait for the second chip.
    await expect(tags).toHaveCount(2, { timeout: 15000 });

    // 3) Previously selected group is STILL selected (root cause of the bug).
    await expect(tags.filter({ hasText: firstTagLabel })).toHaveCount(1);

    // 4) New group shows the typed name, not the "[code]" non-translated fallback.
    await expect(tags.filter({ hasText: newName })).toHaveCount(1);
    await expect(page.locator('.multiselect__tags-wrap').getByText(/^\[.*\]$/)).toHaveCount(0);

    await page.keyboard.press('Escape').catch(() => {});
    await deleteFamilyByCode(page, code);
  });
});
