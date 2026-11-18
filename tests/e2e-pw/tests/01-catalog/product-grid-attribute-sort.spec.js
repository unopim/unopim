const { test, expect } = require('../../utils/fixtures');

/**
 * Product DataGrid attribute sorting.
 *
 * Regression: the numeric cast used for sorting was MySQL's bare DECIMAL
 * (DECIMAL(10,0)), which rounds to whole numbers. 2.20 / 2.10 / 2.18 all tied
 * at 2 and the grid looked unsorted. Measurement attributes must also sort on
 * their normalised base value, not the raw amount.
 *
 * Run only this file:
 *   cd tests/e2e-pw && npx playwright test tests/01-catalog/product-grid-attribute-sort.spec.js
 */

const GRID_URL = '/admin/catalog/products';

async function fetchGrid(adminPage, { column, order, columns = [] }) {
  const params = new URLSearchParams();

  params.set('sort[column]', column);
  params.set('sort[order]', order);
  params.set('pagination[page]', '1');
  params.set('pagination[per_page]', '50');
  columns.forEach((code) => params.append('managedColumns[]', code));

  const response = await adminPage.request.get(`${GRID_URL}?${params.toString()}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
  });

  expect(response.status()).toBe(200);

  return response.json();
}

/** Pull the leading number out of a rendered cell, e.g. "2.20 Kilogram" -> 2.2 */
function leadingNumber(cell) {
  const text = String(cell ?? '').replace(/<[^>]*>/g, '').replace(/,/g, '').trim();
  const match = text.match(/-?\d+(?:\.\d+)?/);

  return match ? parseFloat(match[0]) : null;
}

function isSorted(values, order) {
  return values.every((value, i) => i === 0 || (order === 'asc' ? values[i - 1] <= value : values[i - 1] >= value));
}

test.describe('Product DataGrid attribute sorting', () => {
  for (const order of ['asc', 'desc']) {
    test(`Measurement attribute (weight) sorts numerically, ${order}`, async ({ adminPage }) => {
      const grid = await fetchGrid(adminPage, { column: 'weight', order, columns: ['sku', 'weight'] });
      const rows = grid.records ?? [];

      expect(rows.length, 'Needs at least 3 products to verify ordering').toBeGreaterThanOrEqual(3);

      // Compare like with like: sorting is on base_data, so only rows that share a
      // unit have a meaningful relationship between the displayed amount and order.
      const withWeight = rows
        .map((row) => ({ text: String(row.weight ?? ''), value: leadingNumber(row.weight) }))
        .filter((row) => row.value !== null);

      expect(withWeight.length, 'Needs at least 3 products with a weight').toBeGreaterThanOrEqual(3);

      const unit = (text) => text.replace(/<[^>]*>/g, '').replace(/[\d.,\s-]/g, '').toLowerCase();
      const sameUnit = withWeight.filter((row) => unit(row.text) === unit(withWeight[0].text));

      expect(isSorted(sameUnit.map((row) => row.value), order),
        `weight values not ${order}: ${sameUnit.map((row) => row.text).join(' | ')}`).toBe(true);
    });
  }

  test('Fractional values are not rounded to whole numbers when sorting', async ({ adminPage }) => {
    const grid = await fetchGrid(adminPage, { column: 'weight', order: 'asc', columns: ['sku', 'weight'] });
    const values = (grid.records ?? [])
      .map((row) => leadingNumber(row.weight))
      .filter((value) => value !== null);

    expect(values.length, 'Needs at least 3 products with a weight').toBeGreaterThanOrEqual(3);

    // With DECIMAL(10,0), 0.012 and 0.6 and 0.9 all collapse to 0/1 and tie, so the
    // sequence would not be monotonic once rows with the same integer part interleave.
    expect(isSorted(values.slice(0, values.length), 'asc')).toBe(true);
  });

  test('Sorting by a measurement attribute does not error', async ({ adminPage }) => {
    const grid = await fetchGrid(adminPage, { column: 'weight', order: 'asc', columns: ['sku', 'weight'] });

    expect(Array.isArray(grid.records)).toBe(true);
  });
});
