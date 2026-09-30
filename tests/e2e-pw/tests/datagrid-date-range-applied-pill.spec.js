const { test, expect } = require('@playwright/test');
const { openFilterDrawer } = require('../utils/helpers');

/**
 * The date-range editor lays its quick picks and pickers out in a two-column
 * grid; the applied-range pill row sits in the same grid and must span both
 * columns, or the applied range is squeezed into half the panel (#554).
 */

const GRID_URL = '/admin/catalog/attributes';

const FILTER = '[data-datagrid-filter="created_at"]';

test.describe('datagrid date-range filter', () => {
  test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.removeItem('datagrids'));

    await page.goto(GRID_URL);
    await page.waitForLoadState('networkidle');
  });

  test('applied-range pill spans the full filter panel width', async ({ page }) => {
    await openFilterDrawer(page);

    const drawer = page.locator('[data-drawer-panel]');
    await expect(drawer).toBeVisible();

    if (! await drawer.locator(FILTER).count()) {
      await drawer.getByRole('button', { name: 'Add Filter' }).click();
      await drawer.locator('input[placeholder="Search..."]').fill('created');
      await drawer.locator('p.cursor-pointer', { hasText: /^Created At$/ }).first().click();
    }

    const filter = drawer.locator(FILTER);
    await expect(filter).toBeVisible();

    const grid = filter.locator('.grid.grid-cols-2').first();

    if (! await grid.isVisible()) {
      await filter.locator('[data-filter-toggle]').click();
    }

    await expect(grid).toBeVisible();

    await grid.locator('p.cursor-pointer').first().click();

    const pills = grid.locator(':scope > div.flex-wrap');
    await expect(pills).toBeVisible();

    const gridWidth = await grid.evaluate((el) => el.getBoundingClientRect().width);
    const pillsWidth = await pills.evaluate((el) => el.getBoundingClientRect().width);

    expect(pillsWidth).toBeCloseTo(gridWidth, 0);
  });
});
