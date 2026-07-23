const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

async function openFilterDrawer(adminPage) {
  await navigateTo(adminPage, 'products');
  await adminPage.evaluate(() => localStorage.removeItem('datagrids'));
  await adminPage.reload({ waitUntil: 'networkidle' });
  await adminPage.getByText('Filter', { exact: true }).click();
}

async function addFilter(adminPage, label) {
  await adminPage.getByRole('button', { name: 'Add Filter' }).click();
  await adminPage.locator('.max-h-48.overflow-auto p').filter({ hasText: new RegExp(`^${label}$`) }).first().click();
}

function filterLabels(adminPage) {
  return adminPage.evaluate(() =>
    [...document.querySelectorAll('[data-datagrid-filter] [data-filter-name]')]
      .map((label) => label.textContent.trim())
      .filter(Boolean));
}

test.describe('Product DataGrid filter panel', () => {

  test('Every filter collapses to a label and a summary of its value', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    expect(await filterLabels(adminPage)).toContain('SKU');

    // An unset filter shows its "All" state, and the editor stays collapsed.
    await expect(adminPage.locator('[data-datagrid-filter="sku"] [data-filter-summary]')).toBeVisible();
    await expect(adminPage.locator('[data-datagrid-filter="sku"] [data-filter-summary]')).toHaveText('All');
    await expect(adminPage.locator('[data-datagrid-filter="sku"] input')).toBeHidden();
  });

  test('Clicking a row opens only that filter\'s editor', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    await adminPage.locator('[data-datagrid-filter="status"] [data-filter-toggle]').click();

    await expect(adminPage.locator('[data-datagrid-filter="status"] [data-filter-toggle]'))
      .toHaveAttribute('aria-expanded', 'true');

    await adminPage.locator('[data-datagrid-filter="type"] [data-filter-toggle]').click();

    await expect(adminPage.locator('[data-datagrid-filter="status"] [data-filter-toggle]'))
      .toHaveAttribute('aria-expanded', 'false');
  });

  test('Name, family, status and type can be removed; sku cannot', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    for (const index of ['name', 'attribute_family', 'status', 'type']) {
      await adminPage.locator(`[data-datagrid-filter="${index}"] [data-remove-filter]`).click();
    }

    expect(await filterLabels(adminPage)).toEqual(['SKU']);
  });

  test('A removed grid filter goes back into the Add Filter list', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    await adminPage.locator('[data-datagrid-filter="status"] .icon-cancel').click();

    await adminPage.getByRole('button', { name: 'Add Filter' }).click();

    await expect(
      adminPage.locator('.max-h-48.overflow-auto p').filter({ hasText: /^Status$/ })
    ).toBeVisible();
  });

  test('The picker offers the property filters kept out of the panel', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    await adminPage.getByRole('button', { name: 'Add Filter' }).click();

    for (const label of ['Created At', 'Updated At', 'Completeness', 'Categories']) {
      await expect(
        adminPage.locator('.max-h-48.overflow-auto p').filter({ hasText: new RegExp(`^${label}$`) })
      ).toBeVisible();
    }
  });

  test('Picked filters keep the order they were added in', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    await addFilter(adminPage, 'Created At');
    await addFilter(adminPage, 'Updated At');
    await addFilter(adminPage, 'Completeness');

    expect(await filterLabels(adminPage)).toEqual([
      'SKU',
      'Name',
      'Attribute Family',
      'Status',
      'Type',
      'Created At',
      'Updated At',
      'Completeness',
    ]);
  });

  test('Applying a filter from a later page returns to the first page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await adminPage.evaluate(() => localStorage.removeItem('datagrids'));
    await adminPage.reload({ waitUntil: 'networkidle' });

    const nextPage = adminPage.getByRole('button', { name: /next page/i });

    await nextPage.click();
    await adminPage.waitForLoadState('networkidle');
    await nextPage.click();
    await adminPage.waitForLoadState('networkidle');

    await adminPage.getByText('Filter', { exact: true }).click();
    await adminPage.locator('[data-datagrid-filter="status"] [data-filter-toggle]').click();
    await adminPage.locator('[data-datagrid-filter="status"] .icon-chevron-down').last().click();
    await adminPage.getByRole('listitem').filter({ hasText: /^True$/ }).first().click();
    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');

    await expect(adminPage.locator('input[aria-label*="Page" i]').first()).toHaveValue('1');
    await expect(adminPage.getByText('No Records Available.')).toHaveCount(0);
  });

  test('Operator and value share a row so the panel stays short', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    await addFilter(adminPage, 'Completeness');

    const block = adminPage.locator('[data-attribute-filter="completeness"]');

    const [operator, value] = await Promise.all([
      block.locator('[data-filter-operator]').boundingBox(),
      block.locator('[data-filter-value]').boundingBox(),
    ]);

    expect(Math.abs(operator.y - value.y)).toBeLessThan(4);
    expect(value.x).toBeGreaterThan(operator.x + operator.width - 4);
  });

  test('The categories filter narrows the grid and keeps only the picked chips', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    await addFilter(adminPage, 'Categories');

    const block = adminPage.locator('[data-attribute-filter="categories"]');

    await block.getByText('Select', { exact: true }).last().click();
    await adminPage.locator('.multiselect__element').nth(3).waitFor();

    const option = adminPage.locator('.multiselect__element').nth(3);

    const picked = (await option.innerText()).trim();

    await option.click();
    await adminPage.getByText('Apply Filters').first().click({ force: true });

    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');

    await adminPage.getByText('Filter', { exact: true }).click();

    const chips = adminPage.locator('[data-attribute-filter="categories"] .multiselect__tag');

    await expect(chips).toHaveCount(1);
    await expect(chips.first()).toContainText(picked);
  });
});

test.describe('Product DataGrid saved filters', () => {

  /** Workers share the database, so each one owns a differently named saved filter. */
  const FILTER_NAME = `E2E saved filter ${process.env.TEST_PARALLEL_INDEX ?? '0'}`;

  async function openSavedFilters(adminPage) {
    await adminPage.locator('[data-grid-views]').click();
  }

  async function deleteSavedFilter(adminPage) {
    await openSavedFilters(adminPage);

    const row = adminPage.locator('[data-grid-view]').filter({ hasText: FILTER_NAME });

    if (await row.count()) {
      await row.first().locator('[data-delete-view]').click();
      await adminPage.getByRole('button', { name: 'Delete' }).click();
      await adminPage.waitForTimeout(500);
    }

    await openSavedFilters(adminPage);
  }

  async function applyStatusFilter(adminPage) {
    await adminPage.getByText('Filter', { exact: true }).click();
    await adminPage.locator('[data-datagrid-filter="status"] [data-filter-toggle]').click();
    await adminPage.locator('[data-datagrid-filter="status"] .icon-chevron-down').last().click();
    await adminPage.getByRole('listitem').filter({ hasText: /^True$/ }).first().click();
    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');
  }

  test.afterEach(async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await deleteSavedFilter(adminPage);
  });

  test('Offers to save only once the filters differ from what is stored', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await adminPage.evaluate(() => localStorage.removeItem('datagrids'));
    await adminPage.reload({ waitUntil: 'networkidle' });

    await openSavedFilters(adminPage);
    await expect(adminPage.locator('[data-save-filter-form]')).toHaveCount(0);
    await expect(adminPage.locator('[data-clear-filters]')).toBeVisible();
    await openSavedFilters(adminPage);

    await applyStatusFilter(adminPage);

    await openSavedFilters(adminPage);
    await expect(adminPage.locator('[data-save-filter-form]')).toBeVisible();
  });

  test('Clearing drops every applied filter', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await adminPage.evaluate(() => localStorage.removeItem('datagrids'));
    await adminPage.reload({ waitUntil: 'networkidle' });

    const total = await adminPage.locator('#app').getByText(/\d+ Results?/).first().innerText();

    await applyStatusFilter(adminPage);

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-clear-filters]').click();
    await adminPage.waitForLoadState('networkidle');

    await expect(adminPage.locator('#app').getByText(/\d+ Results?/).first()).toHaveText(total);

    await adminPage.getByText('Filter', { exact: true }).click();

    await expect(adminPage.locator('[data-datagrid-filter="status"] [data-filter-summary]')).toHaveText('All');
  });

  test('Deleting a saved filter asks for confirmation first', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await deleteSavedFilter(adminPage);

    await applyStatusFilter(adminPage);

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-view-name]').fill(FILTER_NAME);
    await adminPage.locator('[data-save-view]').click();
    await adminPage.waitForTimeout(1000);

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-grid-view]').filter({ hasText: FILTER_NAME }).locator('[data-delete-view]').click();

    await expect(adminPage.getByText('Are you sure you want to delete?')).toBeVisible();

    await adminPage.getByRole('button', { name: 'Cancel' }).click();
    await adminPage.waitForTimeout(500);

    await openSavedFilters(adminPage);

    await expect(adminPage.locator('[data-grid-view]').filter({ hasText: FILTER_NAME })).toHaveCount(1);
  });

  test('Drops the selection once the filters are edited by hand', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await deleteSavedFilter(adminPage);

    await applyStatusFilter(adminPage);

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-view-name]').fill(FILTER_NAME);
    await adminPage.locator('[data-save-view]').click();
    await adminPage.waitForTimeout(1000);

    await expect(adminPage.locator('[data-grid-views]')).toContainText(FILTER_NAME);

    await adminPage.getByText('Filter', { exact: true }).click();
    await adminPage.locator('[data-datagrid-filter="type"] [data-filter-toggle]').click();
    await adminPage.locator('[data-datagrid-filter="type"] .icon-chevron-down').last().click();
    await adminPage.getByRole('listitem').filter({ hasText: /^Simple$/ }).first().click();
    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');

    await expect(adminPage.locator('[data-grid-views]')).not.toContainText(FILTER_NAME);

    await openSavedFilters(adminPage);

    await expect(adminPage.locator('[data-grid-view]').filter({ hasText: FILTER_NAME })).not.toHaveClass(/font-medium/);
    await expect(adminPage.locator('[data-save-filter-form]')).toBeVisible();
  });

  test('Marks the applied filter and keeps it selected across a reload', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await deleteSavedFilter(adminPage);

    await applyStatusFilter(adminPage);

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-view-name]').fill(FILTER_NAME);
    await adminPage.locator('[data-save-view]').click();
    await adminPage.waitForTimeout(1000);

    await adminPage.reload({ waitUntil: 'networkidle' });

    await expect(adminPage.locator('[data-grid-views]')).toContainText(FILTER_NAME);

    await openSavedFilters(adminPage);

    await expect(adminPage.locator('[data-grid-view]').filter({ hasText: FILTER_NAME })).toHaveClass(/font-medium/);
    await expect(adminPage.locator('[data-save-filter-form]')).toHaveCount(0);
    await expect(adminPage.locator('[data-clear-filters]')).toBeVisible();
  });

  test('Searches through the saved filters', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await deleteSavedFilter(adminPage);

    await applyStatusFilter(adminPage);

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-view-name]').fill(FILTER_NAME);
    await adminPage.locator('[data-save-view]').click();
    await adminPage.waitForTimeout(1000);

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-view-search]').fill('no-such-filter');
    await adminPage.waitForTimeout(700);

    await expect(adminPage.locator('[data-grid-view]')).toHaveCount(0);

    await adminPage.locator('[data-view-search]').fill(FILTER_NAME);
    await adminPage.waitForTimeout(700);

    await expect(adminPage.locator('[data-grid-view]')).toHaveCount(1);
  });

  test('Saves the current filters and restores them on a later visit', async ({ adminPage }) => {
    await navigateTo(adminPage, 'products');
    await deleteSavedFilter(adminPage);

    await applyStatusFilter(adminPage);

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-view-name]').fill(FILTER_NAME);
    await adminPage.locator('[data-save-view]').click();
    await adminPage.waitForTimeout(1000);

    const filtered = await adminPage.locator('#app').getByText(/\d+ Results?/).first().innerText();

    await navigateTo(adminPage, 'products');
    await adminPage.evaluate(() => localStorage.removeItem('datagrids'));
    await adminPage.reload({ waitUntil: 'networkidle' });

    await openSavedFilters(adminPage);
    await adminPage.locator('[data-grid-view]').filter({ hasText: FILTER_NAME }).first().click();
    await adminPage.waitForLoadState('networkidle');

    await expect(adminPage.locator('[data-grid-views]')).toContainText(FILTER_NAME);
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/).first()).toHaveText(filtered);

    await adminPage.getByText('Filter', { exact: true }).click();

    await expect(adminPage.locator('[data-datagrid-filter="status"] [data-filter-summary]')).toHaveText('True');
  });
});
