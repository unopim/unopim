const { test, expect } = require('../../utils/fixtures');
const {
  navigateTo,
  clickSaveAndExpect,
  searchInDataGrid,
  clickDeleteOnRow,
  confirmDelete,
  generateUid,
} = require('../../utils/helpers');

/**
 * An image category field in the General section must render its Add Image
 * tile in the first grid column, aligned with the field label.
 */
test.describe('Category edit — image field in the General section', () => {
  test.use({ viewport: { width: 1440, height: 900 } });

  test('the Add Image tile is aligned with its label', async ({ adminPage }) => {
    const page = adminPage;
    const code = `pw_image_${generateUid()}`;

    await navigateTo(page, 'categoryFields');
    await page.getByRole('button', { name: 'Create Category Field' }).click();
    await page.locator('input[name="code"]').waitFor({ state: 'visible', timeout: 30000 });
    await page.locator('input[name$="[name]"]').first().fill(`PW Image ${code}`);
    await page.locator('input[name="code"]').fill(code);
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__tags').click();
    await page.locator('.multiselect__content-wrapper li span span', { hasText: /^Image$/ }).first().click();
    await clickSaveAndExpect(page, 'Save Category Field', /Category Field Created Successfully/i);

    try {
      await navigateTo(page, 'categoriesList');
      await page.locator('span[title="Edit"]').first().click();
      await expect(page).toHaveURL(/\/admin\/catalog\/categories\/edit/);

      const label = page.getByText(`PW Image ${code}`, { exact: true });
      await expect(label).toBeVisible();

      const tile = page
        .locator('div')
        .filter({ has: label })
        .filter({ has: page.locator('[data-media-control]') })
        .last()
        .locator('[data-media-control] label')
        .first();

      await expect(tile).toBeVisible();

      const labelBox = await label.boundingBox();
      const tileBox = await tile.boundingBox();

      expect(Math.abs(tileBox.x - labelBox.x)).toBeLessThan(4);
    } finally {
      await navigateTo(page, 'categoryFields');
      await searchInDataGrid(page, code);
      await clickDeleteOnRow(page, code).catch(() => {});
      await confirmDelete(page).catch(() => {});
    }
  });
});
