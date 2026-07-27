const { test, expect } = require('../../../utils/family-fixtures');
const { generateUid } = require('../../../utils/helpers');
const { createFamily, deleteFamilyByCode, selectMultiselect } = require('../../../utils/family-helpers');

/**
 * Regression: creating a new group from the "Assign Attribute Group" modal must ADD to the
 * selection, not replace it, and show the typed name (not the "[code]" non-translated fallback).
 */
test.describe('Attribute Family — assign-group modal tagging', () => {
  test('creating a new group keeps previously selected groups selected', async ({ adminPage }) => {
    test.slow();
    const page = adminPage;
    const { code } = await createFamily(page);

    await page.getByText('Assign Attribute Group', { exact: true }).first().click();
    await page.waitForTimeout(600);

    await selectMultiselect(page, 'group');
    const tags = page.locator('.multiselect__tags-wrap .multiselect__tag');
    await expect(tags).toHaveCount(1);
    const firstTagLabel = (await tags.first().innerText()).trim();

    const newName = `Grp ${generateUid()}`;
    const input = page.locator('input[name="group"]').first();
    await input.pressSequentially(newName, { delay: 15 });
    await page.keyboard.press('Enter');

    // Inline create round-trips to the server; wait for the second chip.
    await expect(tags).toHaveCount(2, { timeout: 15000 });

    // Previously selected group is STILL selected (root cause of the bug).
    await expect(tags.filter({ hasText: firstTagLabel })).toHaveCount(1);

    // New group shows the typed name, not the "[code]" non-translated fallback.
    await expect(tags.filter({ hasText: newName })).toHaveCount(1);
    await expect(page.locator('.multiselect__tags-wrap').getByText(/^\[.*\]$/)).toHaveCount(0);

    await page.keyboard.press('Escape').catch(() => {});
    await deleteFamilyByCode(page, code);
  });
});
