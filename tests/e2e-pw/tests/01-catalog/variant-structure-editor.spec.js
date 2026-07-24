const { test, expect } = require('../../utils/fixtures');

/**
 * Variant structure editor: common pool on the left, one card per variant
 * level on the right. Axis attributes are pinned read-only inside their
 * attribute group; everything else moves between common and a level and must
 * survive a save.
 */
async function openFirstStructure(page) {
  // A save redirect can still be in flight, which aborts a competing goto.
  await page.waitForLoadState('domcontentloaded').catch(() => {});

  for (let attempt = 0; attempt < 3; attempt++) {
    // The electronics family carries the seeded variant structures; family 1
    // (default) has none, so its variants tab shows nothing to edit.
    const navigated = await page
      .goto('/admin/catalog/attribute-families/edit/3?variants=1', { waitUntil: 'domcontentloaded' })
      .then(() => true)
      .catch(() => false);

    if (navigated) {
      break;
    }

    await page.waitForTimeout(1000);
  }

  // The variants list is a datagrid: its edit action is an icon, not a link.
  const editAction = page.locator('.icon-edit, span[title="Edit"]').first();

  await editAction.waitFor({ state: 'visible', timeout: 20000 });

  await editAction.click();

  await page.waitForURL(/\/variant-structures\/\d+\/edit/, { timeout: 20000 });

  await page.locator('[data-variant-level-card]').first().waitFor({ state: 'visible', timeout: 20000 });
}

test.describe('Variant structure editor', () => {
  test('renders one card per level and pins axis attributes read-only', async ({ adminPage }) => {
    test.setTimeout(90000);

    await openFirstStructure(adminPage);

    const levels = await adminPage.locator('.rounded-full', { hasText: /^Parent/ }).first().textContent();

    const cards = adminPage.locator('[data-variant-level-card]');

    // "Parent → Child" is one level, "Parent → Sub-parent → Child" is two.
    const expectedCards = /Sub-parent/i.test(levels || '') ? 2 : 1;

    await expect(cards).toHaveCount(expectedCards);

    const axisRow = adminPage.locator('[data-variant-level-card]').first().getByText('Axis', { exact: true }).first();

    await expect(axisRow).toBeVisible();

    // An axis has no drag handle and no remove button — it cannot leave its level.
    const axisRowContainer = axisRow.locator('xpath=ancestor::div[contains(@class, "grid-cols-[18px_minmax(0,1fr)_auto]")][1]');

    await expect(axisRowContainer.locator('.icon-drag')).toHaveCount(0);
    await expect(axisRowContainer.locator('.icon-cancel')).toHaveCount(0);
  });

  test('moves an attribute from common into a level and keeps it after save', async ({ adminPage }) => {
    test.setTimeout(120000);

    await openFirstStructure(adminPage);

    const card = adminPage.locator('[data-variant-level-card]').last();

    await card.getByRole('button', { name: /Move from Common/i }).click();

    const modal = adminPage.locator('.fixed').filter({ hasText: /Move to level/i }).first();

    await modal.waitFor({ state: 'visible', timeout: 10000 });

    const firstOption = modal.locator('button.grid').first();

    const movedLabel = (await firstOption.locator('span.block').first().textContent())?.trim();

    await firstOption.click();

    await modal.getByRole('button', { name: /^Move from Common$/i }).click();

    await expect(card.getByText(movedLabel, { exact: true }).first()).toBeVisible({ timeout: 10000 });

    await adminPage.getByRole('button', { name: /Save Variant/i }).click();

    await adminPage.waitForURL(/attribute-families\/edit\/3/, { timeout: 30000 });

    await openFirstStructure(adminPage);

    const reopened = adminPage.locator('[data-variant-level-card]').last();

    await expect(reopened.getByText(movedLabel, { exact: true }).first()).toBeVisible({ timeout: 15000 });
  });
});
