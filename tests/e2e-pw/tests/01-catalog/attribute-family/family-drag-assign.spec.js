const { test, expect } = require('../../../utils/family-fixtures');
const { createFamily, deleteFamilyByCode } = require('../../../utils/family-helpers');

/**
 * Drag one unassigned attribute onto a group. Sortable listens to raw mouse
 * events, so `dragTo` (HTML5 drag-and-drop) does not drive it.
 */
async function dragUnassignedIntoGroup(page, attributeRow, groupDropZone) {
  const handle = attributeRow.locator('.icon-drag').first();

  const from = await handle.boundingBox();
  const to = await groupDropZone.boundingBox();

  await page.mouse.move(from.x + from.width / 2, from.y + from.height / 2);
  await page.mouse.down();

  await page.mouse.move(from.x + 40, from.y + 20, { steps: 10 });
  await page.mouse.move(to.x + to.width / 2, to.y + to.height / 2, { steps: 20 });
  await page.mouse.move(to.x + to.width / 2, to.y + to.height / 2 + 4, { steps: 5 });

  await page.mouse.up();

  await page.waitForTimeout(1500); // unassigned list refetch
}

// Family create/save round-trips run 20-30s against a full catalogue; the default per-test budget is too tight.
test.describe.configure({ timeout: 180_000 });

test.describe('Attribute Family — drag assign', () => {
  test('edit: dragging an attribute into a group moves it instead of copying it', async ({ adminPage }) => {
    test.slow();

    const page = adminPage;
    const { code } = await createFamily(page);

    const firstGroup = page.locator('#assigned-attribute-groups > div').first();

    const dropZone = firstGroup.locator('div.min-h-8').first();
    await expect(dropZone).toBeVisible();

    const unassignedRow = page.locator('#unassigned-attributes div.group').first();
    await expect(unassignedRow).toBeVisible();

    const attributeLabel = (await unassignedRow.innerText()).trim().split('\n').pop().trim();

    const totalBefore = Number(
      (await page.getByText(/shown \/ /).first().innerText()).match(/\/\s*([\d,]+)/)[1].replace(/,/g, '')
    );

    await dragUnassignedIntoGroup(page, unassignedRow, dropZone);

    await expect(
      page.locator('#unassigned-attributes').getByText(attributeLabel, { exact: true })
    ).toHaveCount(0);

    await expect(
      firstGroup.getByText(attributeLabel, { exact: true })
    ).toHaveCount(1);

    const nextRow = page.locator('#unassigned-attributes div.group').first();
    const nextLabel = (await nextRow.innerText()).trim().split('\n').pop().trim();

    await dragUnassignedIntoGroup(page, nextRow, dropZone);

    await expect(firstGroup.getByText(attributeLabel, { exact: true })).toHaveCount(1);
    await expect(firstGroup.getByText(nextLabel, { exact: true })).toHaveCount(1);

    const totalAfter = Number(
      (await page.getByText(/shown \/ /).first().innerText()).match(/\/\s*([\d,]+)/)[1].replace(/,/g, '')
    );

    expect(totalAfter).toBe(totalBefore - 2);

    await deleteFamilyByCode(page, code);
  });
});
