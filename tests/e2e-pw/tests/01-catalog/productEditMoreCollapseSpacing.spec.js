const { test, expect } = require('../../utils/fixtures');

const PRODUCT_EDIT_ID = process.env.PRODUCT_EDIT_ID || 14;

test.describe('Product Edit - More and Collapse Button Spacing (#1228)', () => {
  test.setTimeout(90000);

  test('should keep the More dropdown clear of the side rail collapse toggle', async ({ adminPage }) => {
    await adminPage.setViewportSize({ width: 1440, height: 900 });

    await adminPage.goto(`/admin/catalog/products/edit/${PRODUCT_EDIT_ID}`, { waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(1500);

    const more = adminPage.locator('div.relative.inline-block.text-left').first();

    if (!(await more.isVisible().catch(() => false))) {
      test.skip(true, 'Magic AI translation is disabled, so the More dropdown is not rendered');
    }

    const collapse = adminPage.locator('.right-column button[title]').first();
    await expect(collapse).toBeVisible();

    const geometry = await adminPage.evaluate(() => {
      const moreEl = document.querySelector('div.relative.inline-block.text-left');
      const collapseEl = document.querySelector('.right-column button[title]');

      const m = moreEl.getBoundingClientRect();
      const c = collapseEl.getBoundingClientRect();

      return {
        gap: c.left - m.right,
        overlaps: m.right > c.left && m.left < c.right && m.bottom > c.top && m.top < c.bottom,
      };
    });

    expect(geometry.overlaps).toBe(false);
    expect(geometry.gap).toBeGreaterThanOrEqual(8);
  });
});
