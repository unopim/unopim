const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

/**
 * Regression: hovering an inactive parent menu opens its fly-out submenu, and
 * moving the pointer across the seam onto a sub-item keeps it open so the
 * sub-item can be clicked. The fly-out is a detached (fixed) box docked at the
 * sidebar edge; without a hover bridge the diagonal path from the row to the
 * items dropped `group-hover/item` and the submenu vanished before a click.
 */
test.describe('Sidebar fly-out submenu hover', () => {
  test.beforeEach(async ({ adminPage }) => {
    // Land on the dashboard so "Catalog" is the inactive (fixed fly-out) variant.
    await navigateTo(adminPage, 'dashboard');
  });

  test('keeps the fly-out open while moving from the parent onto a sub-item', async ({ adminPage }) => {
    const sidebar = adminPage.locator('#unopim-sidebar');

    const catalog = sidebar.getByRole('link', { name: 'Catalog', exact: true });
    // The lowest sub-item is the worst case: the diagonal from the short trigger
    // row down to it crosses the most dead space.
    const families = sidebar.getByRole('link', { name: 'Attribute Families', exact: true });

    await catalog.hover();
    await expect(families).toBeVisible();

    const from = await catalog.boundingBox();
    const to = await families.boundingBox();

    // Walk the pointer diagonally across the sidebar/fly-out seam the way a user
    // does — this is what used to drop the hover mid-travel and hide the submenu.
    await adminPage.mouse.move(from.x + from.width / 2, from.y + from.height / 2);
    await adminPage.mouse.move(to.x + to.width / 2, to.y + to.height / 2, { steps: 10 });

    // The fly-out must survive the transit so the sub-item stays clickable.
    await expect(families).toBeVisible();

    await families.click();

    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families/);
  });
});
