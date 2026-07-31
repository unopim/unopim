const { test, expect } = require('@playwright/test');

// The panel docks (pushes the app aside) only above this width; below it the
// panel is a full-screen overlay and the app keeps the whole viewport.
const DOCK_BREAKPOINT = 1024;

const PANEL_WIDTH = 420;

const openPanel = async (page) => {
  const fab = page.locator('.ap-fab');

  if (await fab.count() === 0) {
    test.skip(true, 'Agentic PIM is disabled on this instance');
  }

  if (await fab.isVisible()) {
    await fab.click();
  }

  await expect(page.locator('.ap-panel')).toBeVisible();
};

// The datagrid toolbar renders its filter trigger as a clickable div, not a button.
const openFilterDrawer = async (page) => {
  await page.getByText('Filter', { exact: true }).first().click();

  await expect(page.locator('.fixed.z-\\[10001\\].inset-y-0:visible').first()).toBeVisible();
};

// Every overlay the admin renders inside #app: these are the layers that used to
// resolve against the viewport and paint over the docked panel.
const appFixedLayers = (page) => page.evaluate(() => {
  return [...document.querySelectorAll('#app *')]
    .filter((el) => {
      const box = el.getBoundingClientRect();

      return getComputedStyle(el).position === 'fixed' && box.width > 50 && box.height > 50;
    })
    .map((el) => ({
      className: el.className.toString().slice(0, 60),
      right: el.getBoundingClientRect().right,
    }));
});

const boxes = (page) => page.evaluate(() => {
  const rect = (el) => (el ? el.getBoundingClientRect().toJSON() : null);

  return {
    // Compared instead of the viewport width: a scrollbar gutter sits outside
    // the containing block that `position: fixed` resolves against.
    dockedWidth: getComputedStyle(document.getElementById('app')).marginRight,
    app: rect(document.getElementById('app')),
    panel: rect(document.querySelector('.ap-panel')),
    panelInsideApp: !! document.querySelector('#app .ap-panel'),
  };
});

test.describe('agenting pim docked layout', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/admin/catalog/products');
  });

  test('the docked panel shrinks the app instead of being covered by it', async ({ page, viewport }) => {
    test.skip(viewport.width <= DOCK_BREAKPOINT, 'Panel only docks on wide viewports');

    await openPanel(page);

    const { app, panel, panelInsideApp, dockedWidth } = await boxes(page);

    expect(panelInsideApp, 'panel must live outside #app so the dock margin cannot displace it').toBe(false);
    expect(dockedWidth).toBe(`${PANEL_WIDTH}px`);
    expect(Math.round(panel.width)).toBe(PANEL_WIDTH);
    expect(Math.round(app.right)).toBe(Math.round(panel.left));
  });

  test('the filter drawer and its overlay stay left of the docked panel', async ({ page, viewport }) => {
    test.skip(viewport.width <= DOCK_BREAKPOINT, 'Panel only docks on wide viewports');

    await openPanel(page);
    await openFilterDrawer(page);

    const { panel } = await boxes(page);

    // The drawer slides in, so let its transform settle before measuring.
    await expect
      .poll(async () => Math.max(...(await appFixedLayers(page)).map((layer) => Math.round(layer.right))))
      .toBeLessThanOrEqual(Math.round(panel.left));

    const layers = await appFixedLayers(page);

    expect(layers.length, 'the drawer and its overlay should be present').toBeGreaterThan(1);

    for (const layer of layers) {
      expect(Math.round(layer.right), `${layer.className} must not cover the panel`)
        .toBeLessThanOrEqual(Math.round(panel.left));
    }

    await expect(page.locator('.ap-panel')).toBeVisible();
  });

  test('closing the panel returns the full viewport to the app', async ({ page, viewport }) => {
    test.skip(viewport.width <= DOCK_BREAKPOINT, 'Panel only docks on wide viewports');

    await openPanel(page);

    const docked = await boxes(page);

    await page.locator('.ap-panel .ap-header-btn').last().click();
    await expect(page.locator('.ap-panel')).toHaveCount(0);
    await expect.poll(async () => (await boxes(page)).dockedWidth).toBe('0px');

    const undocked = await boxes(page);

    expect(Math.round(undocked.app.width - docked.app.width)).toBe(PANEL_WIDTH);
  });

  test('below the dock breakpoint the panel overlays instead of docking', async ({ page }) => {
    await page.setViewportSize({ width: 900, height: 800 });

    await openPanel(page);

    // The dock width animates, so poll it out rather than sampling mid-transition.
    await expect.poll(async () => (await boxes(page)).dockedWidth).toBe('0px');

    const { app, panel } = await boxes(page);

    expect(Math.round(panel.right)).toBe(Math.round(app.right));
  });
});
