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

/**
 * Create a configurable product on the default family through the create
 * endpoint and return its id. When the family has no variant structure yet it
 * gets the same `e2e_variant_structure` the product specs share.
 * @param {import('@playwright/test').Page} page
 * @param {string} sku
 * @returns {Promise<number>}
 */
async function createConfigurableProduct(page, sku) {
  if (! (await xsrfToken(page.context()))) {
    await page.goto('/admin/catalog/products', { waitUntil: 'load' });
  }

  const headers = async () => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-XSRF-TOKEN': await xsrfToken(page.context()),
  });

  const post = async (extra) => page.request.post('/admin/catalog/products/create', {
    headers: await headers(),
    data: { type: 'configurable', attribute_family_id: 1, sku, ...extra },
  });

  let response = await post();

  if (response.status() === 422 && (await response.json()).errors?.attribute_family_id) {
    const structure = await page.request.put('/admin/catalog/attribute-families/edit/1/variant-structures', {
      headers: await headers(),
      data: {
        structures: [{
          code: 'e2e_variant_structure',
          name: 'E2E Variant Structure',
          levels: 1,
          axes: { level_1: ['color'] },
          placements: {},
        }],
      },
    });

    if (! structure.ok()) {
      throw new Error(`Adding a variant structure to the default family failed: HTTP ${structure.status()}`);
    }

    response = await post();
  }

  const structures = (await response.json()).data?.variant_structures ?? [];

  if (! structures.length) {
    throw new Error(`No variant structure offered for a configurable product: HTTP ${response.status()}`);
  }

  const created = await post({ variant_structure_id: structures[0].id });
  const body = await created.json();
  const id = String(body.data?.redirect_url ?? body.redirect_url ?? '').match(/products\/edit\/(\d+)/)?.[1];

  if (! created.ok() || ! id) {
    throw new Error(`Creating configurable product "${sku}" failed: HTTP ${created.status()} ${JSON.stringify(body)}`);
  }

  return Number(id);
}

module.exports = { deleteProductBySku, createConfigurableProduct };
