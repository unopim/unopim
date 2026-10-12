const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

/**
 * Regression: product grid "Complete" column rendered the closure HTML inside the
 * cell correctly via v-html, but bound the same raw HTML to the native :title
 * tooltip, so hovering showed markup like `<span class="...pill-medium">41%</span>`
 * instead of just "41%". Fix sets :title via a stripHtml() helper.
 */
test.describe('Product grid Complete column tooltip', () => {
  test('title attribute on closure-rendered cells is plain text, not raw HTML', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');

    const firstRow = adminPage.locator('div.row.grid').nth(1);
    await firstRow.waitFor({ state: 'visible', timeout: 30_000 });

    const completeCell = firstRow.locator('p.truncate', { hasText: /%|N\/A/ }).first();
    await completeCell.waitFor({ state: 'visible', timeout: 15_000 });

    const renderedHtml = await completeCell.innerHTML();
    const titleAttr = await completeCell.getAttribute('title');

    expect(renderedHtml).toMatch(/<span/);
    expect(titleAttr).not.toBeNull();
    expect(titleAttr).not.toMatch(/<[^>]+>/);
    expect(titleAttr.trim()).toMatch(/^(\d+%|N\/A)$/);
  });
});
