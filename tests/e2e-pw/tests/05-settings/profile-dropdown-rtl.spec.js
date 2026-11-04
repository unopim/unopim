const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

const PROFILE_TOGGLE = 'header .flex.gap-2\\.5.items-center > :last-child button';

async function openProfileMenu(page, direction) {
  await navigateTo(page, 'dashboard');
  await page.evaluate((dir) => { document.documentElement.dir = dir; }, direction);

  await page.locator(PROFILE_TOGGLE).click();

  const panel = page.locator('div.w-max', { has: page.locator('a[href$="/admin/account"]') });

  await expect(panel).toBeVisible();
  await expect(panel).not.toHaveClass(/\bduration-100\b/);

  return panel;
}

test.describe('Profile dropdown alignment', () => {
  for (const direction of ['ltr', 'rtl']) {
    test(`opens inside the viewport and aligned with the profile toggle in ${direction}`, async ({ adminPage }) => {
      const panel = await openProfileMenu(adminPage, direction);

      const panelBox = await panel.boundingBox();
      const toggleBox = await adminPage.locator(PROFILE_TOGGLE).boundingBox();
      const viewportWidth = adminPage.viewportSize().width;

      expect(panelBox.x).toBeGreaterThanOrEqual(0);
      expect(panelBox.x + panelBox.width).toBeLessThanOrEqual(viewportWidth);

      if (direction === 'rtl') {
        expect(Math.abs(panelBox.x - toggleBox.x)).toBeLessThan(1);
      } else {
        expect(Math.abs((panelBox.x + panelBox.width) - (toggleBox.x + toggleBox.width))).toBeLessThan(1);
      }
    });
  }
});
