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

    // The desktop sidebar is hidden below the lg breakpoint; make sure it has
    // rendered before any hover interaction (global-setup already dismisses the
    // promo bar that would otherwise shift the layout).
    await expect(adminPage.locator('#unopim-sidebar')).toBeVisible();
  });

  test('keeps the fly-out open while moving from the parent onto a sub-item', async ({ adminPage }) => {
    const sidebar = adminPage.locator('#unopim-sidebar');

    // Top-level menu links carry a decorative icon-font glyph in a ::before, which
    // Playwright folds into the accessible name — so match on the label substring
    // (an exact-name match would miss the leading glyph character).
    const catalog = sidebar.getByRole('link', { name: 'Catalog' });
    // The lowest sub-item is the worst case: the diagonal from the short trigger
    // row down to it crosses the most dead space.
    const families = sidebar.getByRole('link', { name: 'Attribute Families', exact: true });

    // Hovering the inactive parent opens its fly-out submenu.
    await catalog.hover();
    await expect(families).toBeVisible();

    // Moving the pointer onto a sub-item must keep the fly-out open (the hover-intent
    // bridge holds it while the pointer crosses the sidebar/fly-out seam) so the
    // sub-item stays clickable — this is the regression being guarded.
    await families.hover();
    await expect(families).toBeVisible();

    await families.click();

    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families/);
  });
});
