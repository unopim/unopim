const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

async function setDirection(page, direction) {
  await page.evaluate((dir) => { document.documentElement.dir = dir; }, direction);
}

async function openPanel(page, toggle, panel) {
  await expect(toggle).toBeVisible();
  await toggle.click();

  await expect(panel).toBeVisible();
  await expect(panel).not.toHaveClass(/\bduration-100\b/);
}

function dropdownToggleFor(page, inner) {
  return page.getByTestId('dropdown-toggle').filter({ has: inner });
}

function dropdownPanelFor(page, inner) {
  return page.getByTestId('dropdown-panel').filter({ has: inner });
}

async function boxes(page, anchor, panel) {
  return {
    anchorBox: await anchor.boundingBox(),
    panelBox: await panel.boundingBox(),
    viewportWidth: page.viewportSize().width,
  };
}

function expectInsideViewport({ panelBox, viewportWidth }) {
  expect(panelBox.x).toBeGreaterThanOrEqual(0);
  expect(panelBox.x + panelBox.width).toBeLessThanOrEqual(viewportWidth);
}

function expectAlignedWithStartEdge({ anchorBox, panelBox }, direction) {
  if (direction === 'rtl') {
    expect(Math.abs((panelBox.x + panelBox.width) - (anchorBox.x + anchorBox.width))).toBeLessThan(1);
  } else {
    expect(Math.abs(panelBox.x - anchorBox.x)).toBeLessThan(1);
  }
}

function expectAlignedWithEndEdge({ anchorBox, panelBox }, direction) {
  expectAlignedWithStartEdge({ anchorBox, panelBox }, direction === 'rtl' ? 'ltr' : 'rtl');
}

test.describe('Profile dropdown alignment (bottom-right)', () => {
  for (const direction of ['ltr', 'rtl']) {
    test(`opens inside the viewport and aligned with the profile toggle end edge in ${direction}`, async ({ adminPage }) => {
      await navigateTo(adminPage, 'dashboard');
      await setDirection(adminPage, direction);

      const toggle = adminPage.getByTestId('profile-toggle');
      const panel = dropdownPanelFor(adminPage, adminPage.getByTestId('profile-menu'));

      await openPanel(adminPage, toggle, panel);

      const measured = await boxes(adminPage, toggle, panel);

      expectInsideViewport(measured);
      expectAlignedWithEndEdge(measured, direction);
    });
  }
});

test.describe('Per-page dropdown alignment (bottom-left)', () => {
  for (const direction of ['ltr', 'rtl']) {
    test(`opens inside the viewport and aligned with the per-page toggle start edge in ${direction}`, async ({ adminPage }) => {
      await navigateTo(adminPage, 'products');
      await setDirection(adminPage, direction);

      const button = adminPage.getByTestId('datagrid-per-page-toggle');
      const anchor = dropdownToggleFor(adminPage, button);
      const panel = dropdownPanelFor(adminPage, adminPage.getByTestId('datagrid-per-page-menu'));

      await openPanel(adminPage, button, panel);

      const measured = await boxes(adminPage, anchor, panel);

      expectInsideViewport(measured);
      expectAlignedWithStartEdge(measured, direction);
    });
  }
});

test.describe('Teleported dropdown alignment (bottom-left)', () => {
  for (const direction of ['ltr', 'rtl']) {
    test(`saved views panel opens inside the viewport and aligned with the toggle start edge in ${direction}`, async ({ adminPage }) => {
      await navigateTo(adminPage, 'products');
      await setDirection(adminPage, direction);

      const button = adminPage.locator('[data-grid-views]');
      const anchor = dropdownToggleFor(adminPage, button);
      const panel = dropdownPanelFor(adminPage, adminPage.locator('[data-view-search]'));

      await openPanel(adminPage, button, panel);

      const measured = await boxes(adminPage, anchor, panel);

      expectInsideViewport(measured);
      expectAlignedWithStartEdge(measured, direction);
    });
  }
});
