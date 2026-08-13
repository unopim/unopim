const { test, expect } = require('@playwright/test');

/**
 * Applying a saved filter rewrites the grid layout — columns, sort, page size
 * and scope all come from its payload. Clearing the filter has to put that
 * layout back, or the grid keeps showing a configuration no filter explains.
 */

const SAVED_FILTERS_TOGGLE = '[data-grid-views]';

const VIEW = '[data-grid-view]';

const CLEAR = '[data-clear-filters]';

const COLUMN = '[data-grid-column]';

const GRID_URL = '/admin/catalog/products';

/**
 * Column indices the grid is rendering, in order. The header is replaced by a
 * shimmer while a request is in flight, so wait for it back before reading.
 */
const gridColumns = async (page) => {
  await expect(page.locator(COLUMN).first()).toBeVisible();

  return page.locator(COLUMN).evaluateAll((nodes) => nodes.map((node) => node.dataset.gridColumn));
};

/** Click something that reloads the grid and wait for that request to land. */
const actAndReload = async (page, action) => {
  const response = page.waitForResponse(
    (res) => res.url().includes(GRID_URL) && res.request().resourceType() === 'xhr'
  );

  await action();

  await response;
};

const openSavedFilters = async (page) => {
  await page.locator(SAVED_FILTERS_TOGGLE).first().click();

  await expect(page.locator(VIEW).first()).toBeVisible();
};

test.describe('product grid saved filters', () => {
  test.beforeEach(async ({ page }) => {
    /* The grid restores its last layout from storage, so start each test clean. */
    await page.addInitScript(() => localStorage.removeItem('datagrids'));

    await page.goto(GRID_URL);
    await page.waitForLoadState('networkidle');
  });

  test('restores the default columns after the saved filter is cleared', async ({ page }) => {
    const defaults = await gridColumns(page);

    expect(defaults.length).toBeGreaterThan(0);

    await openSavedFilters(page);
    await actAndReload(page, () => page.locator(VIEW).first().click());

    expect(await gridColumns(page)).not.toEqual(defaults);

    await openSavedFilters(page);
    await actAndReload(page, () => page.locator(CLEAR).first().click());

    expect(await gridColumns(page)).toEqual(defaults);
  });

  test('drops a view\'s own column selection when it is cleared', async ({ page }) => {
    const defaults = await gridColumns(page);

    const narrowed = ['sku', 'image', 'name', 'status'];

    expect(defaults).not.toEqual(narrowed);

    const cookies = await page.context().cookies();
    const token = decodeURIComponent(cookies.find((cookie) => cookie.name === 'XSRF-TOKEN')?.value ?? '');

    expect(token).not.toBe('');

    const created = await page.request.post('/admin/catalog/products/grid-views', {
      headers: { 'X-XSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
      data: {
        name: 'PW narrow columns',
        is_shared: false,
        payload: {
          filters: [],
          activeFilterIndices: [],
          columns: narrowed,
          sort: { column: 'sku', order: 'asc' },
          perPage: 25,
        },
      },
    });

    expect(created.ok()).toBeTruthy();

    await page.reload();
    await page.waitForLoadState('networkidle');

    await openSavedFilters(page);
    await actAndReload(page, () => page.getByText('PW narrow columns').first().click());

    expect(await gridColumns(page)).toEqual(narrowed);

    await openSavedFilters(page);
    await actAndReload(page, () => page.locator(CLEAR).first().click());

    expect(await gridColumns(page)).toEqual(defaults);

    await page.request.delete(`/admin/catalog/products/grid-views/${(await created.json()).view.id}`, {
      headers: { 'X-XSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
    });
  });

  test('writes a filter change back into the applied view instead of prompting to save', async ({ page }) => {
    const cookies = await page.context().cookies();
    const token = decodeURIComponent(cookies.find((cookie) => cookie.name === 'XSRF-TOKEN')?.value ?? '');

    const created = await page.request.post('/admin/catalog/products/grid-views', {
      headers: { 'X-XSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
      data: {
        name: 'PW filter autosave',
        is_shared: false,
        payload: {
          filters: [],
          activeFilterIndices: [],
          columns: ['sku', 'name', 'status'],
          sort: { column: 'sku', order: 'asc' },
          perPage: 25,
        },
      },
    });

    expect(created.ok()).toBeTruthy();

    const id = (await created.json()).view.id;

    await page.reload();
    await page.waitForLoadState('networkidle');

    await openSavedFilters(page);
    await actAndReload(page, () => page.getByText('PW filter autosave').first().click());

    await page.getByText('Filter', { exact: true }).first().click();

    const skuFilter = page.locator('[data-datagrid-filter="sku"]');

    await expect(skuFilter).toHaveCount(1);

    await skuFilter.locator('[data-filter-toggle]').click();
    await skuFilter.locator('input[type="text"]').first().fill('aurex');

    await actAndReload(page, () => page.getByRole('button', { name: 'Apply' }).first().click());

    await expect.poll(async () => {
      const stored = await page.request.get('/admin/catalog/products/grid-views', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      }).then((res) => res.json());

      return stored.views.find((view) => view.id === id)?.payload?.filters?.some(
        (filter) => filter.index === 'sku'
      );
    }).toBe(true);

    await openSavedFilters(page);

    await expect(page.getByText('Unsaved changes')).toHaveCount(0);

    await page.request.delete(`/admin/catalog/products/grid-views/${id}`, {
      headers: { 'X-XSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
    });
  });

  test('keeps the default filter rows visible under a seeded view', async ({ page }) => {
    await page.getByText('Filter', { exact: true }).first().click();

    const defaults = await page.locator('[data-datagrid-filter]').evaluateAll(
      (nodes) => nodes.map((node) => node.dataset.datagridFilter)
    );

    expect(defaults.length).toBeGreaterThan(1);

    /* The drawer overlay swallows clicks, so leave it behind rather than closing it. */
    await page.goto(GRID_URL);
    await page.waitForLoadState('networkidle');

    await openSavedFilters(page);
    await actAndReload(page, () => page.locator(VIEW).first().click());

    await page.getByText('Filter', { exact: true }).first().click();

    const underView = await page.locator('[data-datagrid-filter]').evaluateAll(
      (nodes) => nodes.map((node) => node.dataset.datagridFilter)
    );

    for (const index of defaults) {
      expect(underView).toContain(index);
    }
  });

  test('shows the seeded filter value in the drawer, not an empty control', async ({ page }) => {
    /**
     * The chosen value, as the drawer prints it. A condition the drawer cannot
     * read leaves the control on its "Select" placeholder while the operator
     * still shows, so the value itself is the only reliable assertion.
     */
    for (const view of [
      { name: 'Featured this season', index: 'is_featured', shows: 'True' },
      { name: 'Repairable range', index: 'features', shows: 'repairable' },
    ]) {
      await page.goto(GRID_URL);
      await page.waitForLoadState('networkidle');

      await openSavedFilters(page);
      await actAndReload(page, () => page.getByText(view.name).first().click());

      await page.getByText('Filter', { exact: true }).first().click();

      const row = page.locator(`[data-datagrid-filter="${view.index}"]`);

      await expect(row).toHaveCount(1);

      const shown = [
        await row.first().innerText(),
        ...(await row.locator('[data-filter-value]').allInnerTexts()),
      ].join(' ');

      expect(shown).toContain(view.shows);
      expect(shown).not.toContain('Select');
    }
  });

  test('leaves a view untouched when it is applied but nothing is changed', async ({ page }) => {
    const read = async () => page.request.get('/admin/catalog/products/grid-views', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).then((res) => res.json());

    const before = JSON.stringify((await read()).views.map((view) => view.payload));

    await openSavedFilters(page);
    await actAndReload(page, () => page.getByText('Featured this season').first().click());

    await page.getByText('Filter', { exact: true }).first().click();
    await page.waitForTimeout(1200);

    expect(JSON.stringify((await read()).views.map((view) => view.payload))).toBe(before);
  });

  test('drops the applied view\'s filters and columns when it is deleted', async ({ page }) => {
    const defaults = await gridColumns(page);

    const cookies = await page.context().cookies();
    const token = decodeURIComponent(cookies.find((cookie) => cookie.name === 'XSRF-TOKEN')?.value ?? '');

    const created = await page.request.post('/admin/catalog/products/grid-views', {
      headers: { 'X-XSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
      data: {
        name: 'PW delete me',
        is_shared: false,
        payload: {
          filters: [{ index: 'sku', value: ['aurex'] }],
          activeFilterIndices: ['sku'],
          columns: ['sku', 'name'],
          sort: { column: 'sku', order: 'asc' },
          perPage: 25,
        },
      },
    });

    expect(created.ok()).toBeTruthy();

    await page.reload();
    await page.waitForLoadState('networkidle');

    await openSavedFilters(page);
    await actAndReload(page, () => page.getByText('PW delete me').first().click());

    expect(await gridColumns(page)).toEqual(['sku', 'name']);

    await openSavedFilters(page);

    const row = page.locator(VIEW).filter({ hasText: 'PW delete me' });

    await actAndReload(page, async () => {
      await row.locator('[data-delete-view]').click();
      await page.getByRole('button', { name: 'Delete', exact: true }).first().click();
    });

    expect(await gridColumns(page)).toEqual(defaults);

    await page.getByText('Filter', { exact: true }).first().click();

    await expect(page.locator('[data-applied-filter-count]')).toHaveCount(0);
  });

  test('clears back to the defaults rather than to the previously applied view', async ({ page }) => {
    const defaults = await gridColumns(page);

    await openSavedFilters(page);

    const viewCount = await page.locator(VIEW).count();

    test.skip(viewCount < 2, 'needs at least two saved views');

    await actAndReload(page, () => page.locator(VIEW).nth(0).click());

    await openSavedFilters(page);
    await actAndReload(page, () => page.locator(VIEW).nth(1).click());

    await openSavedFilters(page);
    await actAndReload(page, () => page.locator(CLEAR).first().click());

    expect(await gridColumns(page)).toEqual(defaults);
  });
});
