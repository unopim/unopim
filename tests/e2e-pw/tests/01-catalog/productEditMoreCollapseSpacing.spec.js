const { test, expect } = require('../../utils/fixtures');
const { resolveEditableProductId } = require('../../utils/helpers');
const { ensureMagicAITranslationEnabled } = require('../../utils/magic-ai-helpers');

test.describe('Product Edit - More and Collapse Button Spacing (#1228)', { tag: '@global-state' }, () => {
  test.setTimeout(90000);

  test('should keep the More dropdown clear of the side rail collapse toggle', async ({ adminPage }) => {
    await adminPage.setViewportSize({ width: 1440, height: 900 });

    // The More dropdown renders only while Magic AI translation is enabled.
    await ensureMagicAITranslationEnabled(adminPage);

    const productId = await resolveEditableProductId(adminPage);

    await adminPage.goto(`/admin/catalog/products/edit/${productId}`, { waitUntil: 'domcontentloaded' });

    const more = adminPage.locator('div.relative.inline-block.text-left').first();
    await expect(more).toBeVisible();

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
