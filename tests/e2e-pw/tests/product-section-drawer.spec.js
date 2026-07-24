const { test, expect } = require('@playwright/test');

const PRODUCT_EDIT = '/admin/catalog/products/edit/3';

// The drawer panel is teleported to <body> and carries both `.fixed` and
// data-section-id; the association content wrapper reuses data-section-id
// without `.fixed`, so scope every panel lookup to `.fixed`.
const panelSel = (sec) => `.fixed[data-section-id="${sec}"]`;

const widthRatio = (page, section) => page.evaluate((sec) => {
  const main = document.getElementById('main-content');
  const panel = document.querySelector(`.fixed[data-section-id="${sec}"]`);
  if (!main || !panel) return null;
  return panel.getBoundingClientRect().width / main.getBoundingClientRect().width;
}, section);

const noOverflow = (page) => page.evaluate(
  () => document.body.scrollWidth <= document.documentElement.clientWidth + 1
);

// Dispatch the click on the card element directly: at narrow widths the cards
// sit at the page bottom where the dev Debugbar overlay intercepts real pointer
// events, and a native element.click() still bubbles to the drawer's @click.
const openDrawer = async (page, nameRe) => {
  const card = page.getByRole('button', { name: nameRe }).first();
  await card.evaluate((el) => el.click());
};

test.describe('product-edit section drawer', () => {
  for (const [w, h, expected] of [[1440, 900, 0.90], [1024, 800, 0.90], [768, 900, 0.90], [375, 720, 1.0]]) {
    test(`categories drawer at ${w}px ~ ${Math.round(expected * 100)}% and no overflow`, async ({ page }) => {
      await page.setViewportSize({ width: w, height: h });
      await page.goto(PRODUCT_EDIT);

      await openDrawer(page, /categories/i);

      const panel = page.locator(panelSel('categories'));
      await expect(panel).toBeVisible();

      const ratio = await widthRatio(page, 'categories');
      expect(ratio).toBeGreaterThan(expected - 0.06);
      expect(ratio).toBeLessThan(Math.min(1.02, expected + 0.06));

      expect(await noOverflow(page)).toBe(true);

      // On desktop the left sidebar stays visible/undimmed alongside the drawer;
      // below lg UnoPim collapses it off-canvas, so only assert where it shows.
      if (w >= 1024) {
        await expect(page.locator('#unopim-sidebar')).toBeVisible();
      }
    });
  }

  test('associations drawer opens and closes via Escape', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(PRODUCT_EDIT);

    await openDrawer(page, /link|associations/i);

    const panel = page.locator(panelSel('associations'));
    await expect(panel).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(panel).toBeHidden();
  });

  test('no horizontal overflow with associations drawer open at 375px', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 720 });
    await page.goto(PRODUCT_EDIT);

    await openDrawer(page, /link|associations/i);
    await expect(page.locator(panelSel('associations'))).toBeVisible();

    expect(await noOverflow(page)).toBe(true);
  });
});
