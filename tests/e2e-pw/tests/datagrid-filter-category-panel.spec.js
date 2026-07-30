const { test, expect } = require('@playwright/test');

const PANEL = '.fixed[data-section-id="datagrid-filter-categories"]';

const pinAgenticPim = (page, isOpen) => page.addInitScript((open) => {
  sessionStorage.setItem('agenting_pim_state', JSON.stringify({ isOpen: open }));
}, isOpen);

const topmostAtPanelCentre = (page) => page.evaluate((sel) => {
  const panel = document.querySelector(sel);

  if (! panel) {
    return 'panel-missing';
  }

  const box = panel.getBoundingClientRect();
  const hit = document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2);

  if (! hit) {
    return 'nothing-hit';
  }

  return panel.contains(hit) ? 'panel' : (hit.className || hit.tagName);
}, PANEL);

const openCategoryFilterPanel = async (page) => {
  const filterToggle = page.locator('.icon-filter').first();
  await expect(filterToggle).toBeVisible({ timeout: 60_000 });
  await filterToggle.click();

  const drawer = page.locator('[data-drawer-panel]');
  await expect(drawer).toBeVisible();

  await drawer.getByRole('button', { name: 'Add Filter' }).click();

  await drawer.locator('input[placeholder="Search..."]').fill('categor');

  const option = drawer.locator('p.cursor-pointer', { hasText: /^Categories$/ }).first();
  await expect(option).toBeVisible();
  await option.click();

  const toggle = page.locator('[data-open-tree-panel]');
  await expect(toggle).toBeVisible();
  await toggle.click();

  await expect(page.locator(PANEL)).toBeVisible();
};

test.describe('datagrid filter category panel stacking', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
  });

  test('the category panel is reachable above the filter drawer', async ({ page }) => {
    await pinAgenticPim(page, false);
    await page.goto('/admin/catalog/products');

    await openCategoryFilterPanel(page);

    await expect.poll(() => topmostAtPanelCentre(page)).toBe('panel');
  });

  test('a category stays selectable in the panel', async ({ page }) => {
    await pinAgenticPim(page, false);
    await page.goto('/admin/catalog/products');

    await openCategoryFilterPanel(page);

    const checkbox = page.locator(`${PANEL} input[type="checkbox"]`).first();
    await expect(checkbox).toHaveCount(1);

    const id = await checkbox.getAttribute('id');
    await page.locator(`${PANEL} label[for="${id}"]`).click();

    await expect(checkbox).toBeChecked();
  });

  test('the panel stays reachable while the Agentic PIM panel is docked', async ({ page }) => {
    await pinAgenticPim(page, true);
    await page.goto('/admin/catalog/products');

    await expect(page.locator('.ap-panel')).toBeVisible({ timeout: 60_000 });

    await openCategoryFilterPanel(page);

    await expect.poll(() => topmostAtPanelCentre(page)).toBe('panel');
  });
});
