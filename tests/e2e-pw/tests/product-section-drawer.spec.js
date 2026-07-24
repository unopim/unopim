const { test, expect } = require('@playwright/test');

const PRODUCT_EDIT = '/admin/catalog/products/edit/3';

const widthRatio = (page, section) => page.evaluate((sec) => {
  const main = document.getElementById('main-content');
  const panel = document.querySelector(`#main-content [data-section-id="${sec}"]`);
  if (!main || !panel) return null;
  return panel.getBoundingClientRect().width / main.getBoundingClientRect().width;
}, section);

const noOverflow = (page) => page.evaluate(
  () => document.body.scrollWidth <= document.documentElement.clientWidth + 1
);

test.describe('product-edit section drawer', () => {
  for (const [w, h, expected] of [[1440, 900, 0.90], [1024, 800, 0.90], [768, 900, 0.90], [375, 720, 1.0]]) {
    test(`categories drawer at ${w}px ~ ${Math.round(expected * 100)}% and no overflow`, async ({ page }) => {
      await page.setViewportSize({ width: w, height: h });
      await page.goto(PRODUCT_EDIT);

      await page.getByRole('button', { name: /categories/i }).first().click();

      const panel = page.locator('#main-content [data-section-id="categories"]');
      await expect(panel).toBeVisible();

      const ratio = await widthRatio(page, 'categories');
      expect(ratio).toBeGreaterThan(expected - 0.06);
      expect(ratio).toBeLessThan(Math.min(1.02, expected + 0.06));

      expect(await noOverflow(page)).toBe(true);

      // App chrome stays visible while a drawer is open.
      await expect(page.locator('#unopim-sidebar')).toBeVisible();
    });
  }

  test('associations drawer opens and closes via Escape', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(PRODUCT_EDIT);

    await page.getByRole('button', { name: /link|associations/i }).first().click();

    const panel = page.locator('#main-content [data-section-id="associations"]');
    await expect(panel).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(panel).toBeHidden();
  });

  test('no horizontal overflow with associations drawer open at 375px', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 720 });
    await page.goto(PRODUCT_EDIT);

    await page.getByRole('button', { name: /link|associations/i }).first().click();
    await expect(page.locator('#main-content [data-section-id="associations"]')).toBeVisible();

    expect(await noOverflow(page)).toBe(true);
  });
});
