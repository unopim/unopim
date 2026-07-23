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

    // Locate by href, not by label: the collapsed sidebar (the default) hides the
    // `<p>` labels, so the links carry no accessible name in that state.
    const catalog = sidebar.locator('[data-menu-item]', {
      has: adminPage.locator('a[href$="/catalog/attribute-families"]'),
    }).locator('> a');
    // The lowest sub-item is the worst case: the diagonal from the short trigger
    // row down to it crosses the most dead space.
    const families = sidebar.locator('a[href$="/catalog/attribute-families"]');

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

    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attribute-families/);
  });

  /**
   * The fly-out is `position: fixed` with no CSS `top`, so without JS it falls back
   * to its static flow position — one row height below the icon it belongs to. The
   * sidebar sits inside the `#app` Vue root, which re-creates these nodes on mount,
   * so listeners must be delegated from `document` or they are silently discarded.
   */
  test('docks the fly-out to the top of its trigger row', async ({ adminPage }) => {
    const sidebar = adminPage.locator('#unopim-sidebar');

    const catalog = sidebar.locator('[data-menu-item]', {
      has: adminPage.locator('a[href$="/catalog/attribute-families"]'),
    }).locator('> a');

    await catalog.hover();

    const geometry = await adminPage.evaluate(() => {
      const subMenu = document.querySelector('#unopim-sidebar [data-submenu][style*="grid"]');
      const menuItem = subMenu.closest('[data-menu-item]');

      return {
        triggerTop: menuItem.getBoundingClientRect().top,
        subMenuTop: subMenu.getBoundingClientRect().top,
        subMenuBottom: subMenu.getBoundingClientRect().bottom,
        viewportHeight: window.innerHeight,
      };
    });

    expect(Math.abs(geometry.subMenuTop - geometry.triggerTop)).toBeLessThanOrEqual(1);
    expect(geometry.subMenuBottom).toBeLessThanOrEqual(geometry.viewportHeight);
  });

  /**
   * Sweeping down the icon rail used to stack fly-outs on top of each other: the
   * CSS `group-hover` reveal is instant, so a second panel appeared while the first
   * was still held open by its grace period. Only one may ever be on screen.
   */
  test('never shows two fly-outs while sweeping across parents', async ({ adminPage }) => {
    const triggers = await adminPage.evaluate(() =>
      [...document.querySelectorAll('#unopim-sidebar [data-menu-item]')]
        .filter((menuItem) => menuItem.querySelector('[data-submenu]'))
        .map((menuItem) => {
          const rect = menuItem.getBoundingClientRect();

          return { x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 };
        }));

    expect(triggers.length).toBeGreaterThan(2);

    const visibleCount = () => adminPage.evaluate(() =>
      [...document.querySelectorAll('#unopim-sidebar [data-submenu]')]
        .filter((subMenu) => getComputedStyle(subMenu).display !== 'none')
        .length);

    // Fast sweep: faster than the switch delay, which is where panels used to stack.
    for (const trigger of triggers) {
      await adminPage.mouse.move(trigger.x, trigger.y);
      await adminPage.waitForTimeout(120);

      expect(await visibleCount()).toBe(1);
    }

    // Deliberate hovers: each parent replaces the previous fly-out, never adds to it.
    for (const trigger of triggers) {
      await adminPage.mouse.move(trigger.x, trigger.y);
      await adminPage.waitForTimeout(500);

      expect(await visibleCount()).toBe(1);
    }
  });

  /**
   * Admin pages swap through ajax navigation, so the sidebar is never re-parsed
   * after the first load. The fly-out must keep working without a page refresh.
   */
  test('survives an in-app navigation without a reload', async ({ adminPage }) => {
    const sidebar = adminPage.locator('#unopim-sidebar');

    const catalog = sidebar.locator('[data-menu-item]', {
      has: adminPage.locator('a[href$="/catalog/attribute-families"]'),
    }).locator('> a');

    await catalog.hover();
    await sidebar.locator('a[href$="/catalog/categories"]').click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/categories/);

    // Leave the sidebar, then come back the way a user would after landing.
    await adminPage.mouse.move(700, 500);
    await catalog.hover();

    const delta = await adminPage.evaluate(() => {
      const subMenu = document.querySelector('#unopim-sidebar [data-submenu][style*="grid"]');

      return subMenu
        ? subMenu.getBoundingClientRect().top - subMenu.closest('[data-menu-item]').getBoundingClientRect().top
        : null;
    });

    expect(delta).not.toBeNull();
    expect(Math.abs(delta)).toBeLessThanOrEqual(1);
  });
});
