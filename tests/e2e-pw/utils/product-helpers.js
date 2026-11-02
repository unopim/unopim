// utils/product-helpers.js — product fixtures safe to clean up while other workers share the catalog.

const { xsrfToken } = require('./category-field-helpers');

/**
 * Delete the product whose SKU is exactly `sku`; a no-op when there is none.
 * It resolves the id from the grid's JSON instead of clicking the first row of
 * a search, which deletes another worker's product when the search has not
 * refreshed yet or the SKU is a prefix of someone else's.
 * @param {import('@playwright/test').Page} page
 * @param {string} sku
 */
async function deleteProductBySku(page, sku) {
  if (! (await xsrfToken(page.context()))) {
    await page.goto('/admin/catalog/products', { waitUntil: 'load' });
  }

  const grid = await page.request.get('/admin/catalog/products', {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    params: { 'filters[sku][]': sku },
  });

  const records = (await grid.json()).records ?? [];

  for (const record of records.filter((row) => row.sku === sku)) {
    await page.request.delete(`/admin/catalog/products/edit/${record.product_id}`, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': await xsrfToken(page.context()),
      },
    });
  }
}

module.exports = { deleteProductBySku };
