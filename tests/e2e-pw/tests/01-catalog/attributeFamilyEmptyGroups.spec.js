const { test, expect } = require('../../utils/family-fixtures');
const { generateUid } = require('../../utils/helpers');
const {
  gotoIndex, createFamily, deleteFamilyByCode, assignGroup, saveFamilyEdit,
} = require('../../utils/family-helpers');

/**
 * Regression #709 — saving an attribute family after every assigned group has been
 * removed must not 500. Runs against the current branch (8023) via family-fixtures.
 */
async function reopenFamily(page, code) {
  await gotoIndex(page);
  await page.getByRole('textbox', { name: 'Search' }).fill(code);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1500);
  await page.locator('div', { hasText: code }).locator('span[title="Edit"]').first().click();
  await page.waitForSelector('.group_node', { timeout: 30000 });
}

test.describe('Attribute Family - Save with empty groups (#709)', () => {
  test.setTimeout(120000);

  test('should not crash when saving after removing all assigned groups', async ({ adminPage }) => {
    const page = adminPage;
    const code = `emptygrp_${generateUid()}`;

    // Create a family (lands on edit page).
    const { code: created } = await createFamily(page, code, { name: 'Empty Group Test' });

    // Assign an extra group, then save via the tracked bar.
    await assignGroup(page);
    await page.locator('input[name$="[name]"]').first().fill('Empty Group Test A');
    await page.locator('input[name$="[name]"]').first().blur();
    await saveFamilyEdit(page);

    // Reopen and remove the non-SKU group via its trash icon (the General group
    // holds SKU and cannot be removed).
    await reopenFamily(page, created);
    const removableRow = page.locator('#assigned-attribute-groups .group_node')
      .filter({ hasNot: page.getByText('General', { exact: true }) }).first();
    await removableRow.waitFor({ state: 'visible', timeout: 15000 });
    await removableRow.locator('button.icon-delete').click();

    const agree = page.getByRole('button', { name: 'Agree', exact: true });
    await agree.waitFor({ state: 'visible', timeout: 10000 });
    await agree.click();
    await agree.waitFor({ state: 'hidden', timeout: 10000 });

    // Save again — must succeed (no 500).
    await page.locator('input[name$="[name]"]').first().fill('Empty Group Test B');
    await page.locator('input[name$="[name]"]').first().blur();
    await saveFamilyEdit(page);

    await deleteFamilyByCode(page, created);
  });
});
