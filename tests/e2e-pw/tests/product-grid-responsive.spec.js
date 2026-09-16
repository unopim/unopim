const { test, expect } = require('@playwright/test');

// Below this the Agentic PIM panel overlays the app instead of docking beside it.
const DOCK_BREAKPOINT = 1024;

// Under this toolbar width the controls dock into a bottom bar and the rows stack.
const STACK_WIDTH = 460;

const VIEWPORTS = [
  { name: 'phone-360', width: 360, height: 740 },
  { name: 'phone-390', width: 390, height: 844 },
  { name: 'phone-414', width: 414, height: 896 },
  { name: 'tablet-768', width: 768, height: 1024 },
  { name: 'tablet-1024', width: 1024, height: 768 },
  { name: 'laptop-1280', width: 1280, height: 800 },
  { name: 'laptop-1280-zoom-150', width: 853, height: 533 },
  { name: 'laptop-1280-zoom-175', width: 731, height: 457 },
  { name: 'desktop-1440', width: 1440, height: 900 },
  { name: 'desktop-1920', width: 1920, height: 1080 },
];

const openPanel = async (page) => {
  const fab = page.locator('.ap-fab');

  if (await fab.count() === 0) {
    return false;
  }

  if (await fab.isVisible().catch(() => false)) {
    await fab.click();
  }

  await expect(page.locator('.ap-panel')).toBeVisible();
  await settleDock(page);

  return true;
};

const settleDock = async (page) => {
  await expect.poll(async () => {
    const before = await page.evaluate(() => getComputedStyle(document.getElementById('app')).marginRight);

    await page.waitForTimeout(120);

    return before === await page.evaluate(() => getComputedStyle(document.getElementById('app')).marginRight);
  }).toBe(true);
};

/**
 * The drawer slides in from the viewport edge, so its box only reflects the
 * docked layout once the enter transition has finished.
 */
const settleRect = async (page, locator) => {
  await expect.poll(async () => {
    const before = await locator.evaluate((el) => el.getBoundingClientRect().right);

    await page.waitForTimeout(120);

    return before === await locator.evaluate((el) => el.getBoundingClientRect().right);
  }).toBe(true);
};

const layout = (page) => page.evaluate(() => {
  const toolbar = document.querySelector('.datagrid-toolbar');
  const main = document.querySelector('main#main-content');
  const search = toolbar.querySelector('input[type="text"], input');
  const bar = toolbar.querySelector('[class*="is-stacked"][class*="fixed"]')
    ?? [...toolbar.querySelectorAll('div')].find((el) => getComputedStyle(el).position === 'fixed' && el.querySelector('[data-grid-views]'));

  const rect = (el) => (el ? el.getBoundingClientRect().toJSON() : null);
  const box = toolbar.getBoundingClientRect();

  const overflowing = [...toolbar.querySelectorAll('*')]
    .filter((el) => {
      const r = el.getBoundingClientRect();

      return r.width > 0 && getComputedStyle(el).position !== 'fixed' && (r.right > box.right + 1 || r.left < box.left - 1);
    })
    .map((el) => el.className.toString().slice(0, 50));

  const groups = [...toolbar.querySelectorAll(':scope > div, :scope > div > div')]
    .filter((el) => el.getBoundingClientRect().width > 0 && getComputedStyle(el).position !== 'fixed');

  const rows = groups
    .map((el) => el.getBoundingClientRect())
    .reduce((lines, r) => {
      const centre = r.top + r.height / 2;

      if (! lines.some((line) => Math.abs(line - centre) < 24)) {
        lines.push(centre);
      }

      return lines;
    }, []);

  return {
    toolbar: { width: Math.round(box.width), height: Math.round(box.height) },
    classes: toolbar.className,
    rowTops: rows.map((centre) => Math.round(centre)).sort((a, b) => a - b),
    search: rect(search),
    bottomBar: bar ? { ...rect(bar), position: getComputedStyle(bar).position } : null,
    mainOverflow: main.scrollWidth - main.clientWidth,
    pageOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    overflowing,
    triggers: {
      columns: rect(document.querySelector('.datagrid-toolbar .icon-manage-column')),
      views: rect(document.querySelector('.datagrid-toolbar [data-grid-views]')),
      filter: rect(document.querySelector('.datagrid-toolbar [data-grid-filter]')),
    },
  };
});

const assertHealthy = (info, label) => {
  expect(info.mainOverflow, `${label}: main must not scroll sideways`).toBeLessThanOrEqual(1);
  expect(info.pageOverflow, `${label}: the page must not scroll sideways`).toBeLessThanOrEqual(1);
  expect(info.overflowing, `${label}: nothing may spill out of the toolbar`).toEqual([]);

  expect(info.triggers.columns, `${label}: the columns trigger must render`).not.toBeNull();
  expect(info.triggers.views, `${label}: the saved views trigger must render`).not.toBeNull();
  expect(info.triggers.filter, `${label}: the filter trigger must render`).not.toBeNull();

  expect(info.search.width, `${label}: the search field must stay usable`).toBeGreaterThanOrEqual(20);

  if (info.toolbar.width < STACK_WIDTH) {
    expect(info.bottomBar, `${label}: the controls must dock into a bottom bar`).not.toBeNull();
    expect(info.bottomBar.position).toBe('fixed');

    return;
  }

  expect(info.rowTops.length, `${label}: the toolbar must stay on one line (rows: ${info.rowTops.join()})`).toBe(1);
};

test.describe('product grid responsive layout', () => {
  for (const viewport of VIEWPORTS) {
    test(`keeps the toolbar usable at ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await page.goto('/admin/catalog/products');
      await page.waitForSelector('.datagrid-toolbar');
      await page.waitForTimeout(400);

      const info = await layout(page);

      await page.screenshot({ path: `test-results/grid-responsive/${viewport.name}.png` });

      assertHealthy(info, viewport.name);
    });

    if (viewport.width > DOCK_BREAKPOINT) {
      test(`keeps the toolbar usable at ${viewport.name} with the panel docked`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await page.goto('/admin/catalog/products');
        await page.waitForSelector('.datagrid-toolbar');

        test.skip(! (await openPanel(page)), 'Agentic PIM is disabled on this instance');

        await page.waitForTimeout(400);

        const info = await layout(page);

        await page.screenshot({ path: `test-results/grid-responsive/${viewport.name}-panel.png` });

        assertHealthy(info, `${viewport.name}+panel`);
      });
    }
  }

  test('opens the filter drawer from the docked toolbar without covering the panel', async ({ page, viewport }) => {
    test.skip(viewport.width <= DOCK_BREAKPOINT, 'Panel only docks on wide viewports');

    await page.goto('/admin/catalog/products');
    await page.waitForSelector('.datagrid-toolbar');

    test.skip(! (await openPanel(page)), 'Agentic PIM is disabled on this instance');

    await page.locator('.datagrid-toolbar [data-grid-filter]').first().click();

    const drawer = page.locator('[data-drawer-panel]:visible').first();

    await expect(drawer).toBeVisible();

    await settleRect(page, drawer);

    const panelLeft = await page.evaluate(() => document.querySelector('.ap-panel').getBoundingClientRect().left);
    const drawerRight = await drawer.evaluate((el) => el.getBoundingClientRect().right);

    expect(Math.round(drawerRight)).toBeLessThanOrEqual(Math.round(panelLeft));
  });
});
