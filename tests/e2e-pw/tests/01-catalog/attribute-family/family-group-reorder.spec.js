const { test, expect } = require('../../../utils/family-fixtures');
const { createFamily, deleteFamilyByCode, assignGroup, saveFamilyEdit } = require('../../../utils/family-helpers');

const bar = (page) => page.getByText('You have unsaved changes');

/** Read the assigned group codes in their rendered order. */
async function readGroupOrder(page) {
  return page
    .locator('#assigned-attribute-groups input[type="hidden"][name$="[code]"]')
    .evaluateAll(inputs => inputs.map(input => input.value));
}

/**
 * Drag the first assigned group below the second. Sortable listens to raw mouse
 * events, so `dragTo` (HTML5 drag-and-drop) does not drive it.
 */
async function dragFirstGroupBelowSecond(page) {
  const groups = page.locator('#assigned-attribute-groups > div');
  const handle = groups.first().locator('.icon-drag').first();

  const from = await handle.boundingBox();
  const to = await groups.nth(1).boundingBox();

  await page.mouse.move(from.x + from.width / 2, from.y + from.height / 2);
  await page.mouse.down();

  await page.mouse.move(from.x + 20, from.y + 20, { steps: 10 });
  await page.mouse.move(to.x + to.width / 2, to.y + to.height - 4, { steps: 20 });
  await page.mouse.move(to.x + to.width / 2, to.y + to.height - 2, { steps: 5 });

  await page.mouse.up();
}

test.describe.configure({ timeout: 180_000 });

test.describe('Attribute Family — group reorder', () => {
  test('edit: reordering groups marks the form dirty, Discard restores the order and Save persists it', async ({ adminPage }) => {
    test.slow();

    const page = adminPage;
    const { code } = await createFamily(page);

    try {
      const groups = page.locator('#assigned-attribute-groups > div');

      while (await groups.count() < 2) {
        await assignGroup(page);
      }

      await saveFamilyEdit(page);
      await page.reload();

      await expect(groups.nth(1)).toBeVisible();

      const original = await readGroupOrder(page);

      await expect(bar(page)).toHaveCount(0);

      await dragFirstGroupBelowSecond(page);

      await expect.poll(() => readGroupOrder(page)).not.toEqual(original);
      await expect(bar(page)).toBeVisible();

      await page.getByRole('button', { name: 'Discard' }).click();
      await page.locator('button.danger-button').first().click().catch(() => {});

      await expect.poll(() => readGroupOrder(page)).toEqual(original);
      await expect(bar(page)).toBeHidden();
      await expect(page.locator('button.danger-button')).toBeHidden();

      await dragFirstGroupBelowSecond(page);
      await expect.poll(() => readGroupOrder(page)).not.toEqual(original);
      await expect(bar(page)).toBeVisible();

      const reordered = await readGroupOrder(page);

      await saveFamilyEdit(page);
      await page.reload();

      await expect.poll(() => readGroupOrder(page)).toEqual(reordered);
    } finally {
      await deleteFamilyByCode(page, code);
    }
  });
});
