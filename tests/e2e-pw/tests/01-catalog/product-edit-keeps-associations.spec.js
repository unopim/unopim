const { test, expect } = require('../../utils/fixtures');
const { generateUid, clickSave, gotoPage } = require('../../utils/helpers');
const { xsrfToken } = require('../../utils/category-field-helpers');
const { createEditableSimpleProduct, deleteProductBySku } = require('../../utils/product-helpers');

/**
 * Regression: editing a product through the form (which submits attribute values only,
 * no `associations` key) cleared the product's existing associations.
 *
 * Run only this file:
 *   cd tests/e2e-pw && BASE_URL=http://localhost:8000 npx playwright test tests/01-catalog/product-edit-keeps-associations.spec.js
 */

const TYPE = 'related_products';

/** Link `relatedSku` to a product with the same request the product form's associations section sends. */
async function linkRelatedProduct(page, productId, sku, relatedSku) {
  const response = await page.request.put(`/admin/catalog/products/edit/${productId}`, {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': await xsrfToken(page.context()),
    },
    maxRedirects: 0,
    form: {
      sku,
      [`associations[${TYPE}][__present]`]: '1',
      [`associations[${TYPE}][0][sku]`]: relatedSku,
    },
  });

  expect([200, 302].includes(response.status()), `linking failed: HTTP ${response.status()} ${await response.text()}`).toBe(true);
}

/** SKUs currently linked to the product, read from the edit page's association data. */
async function linkedSkus(page, productId, candidateSkus) {
  const response = await page.request.get(`/admin/catalog/products/edit/${productId}`);
  const html = await response.text();

  return candidateSkus.filter((sku) => html.includes(`"${sku}"`));
}

test.describe('Product edit keeps associations', () => {
  test.setTimeout(150000);

  test('Changing a product value does not remove its associations', async ({ adminPage }) => {
    const uid = generateUid();
    const sourceSku = `assoc-src-${uid}`;
    const relatedSku = `assoc-rel-${uid}`;

    try {
      const sourceId = await createEditableSimpleProduct(adminPage, sourceSku, `Assoc source ${uid}`);
      await createEditableSimpleProduct(adminPage, relatedSku, `Assoc related ${uid}`);

      await linkRelatedProduct(adminPage, sourceId, sourceSku, relatedSku);

      expect(await linkedSkus(adminPage, sourceId, [relatedSku])).toEqual([relatedSku]);

      // An ordinary edit from the UI: change the name and save.
      await gotoPage(adminPage, `/admin/catalog/products/edit/${sourceId}`);
      await adminPage.locator('#name').fill(`Assoc source edited ${uid}`);
      await clickSave(adminPage, 'Save Product');
      await expect(adminPage.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 20000 });

      // The association is still there after the save and a fresh load.
      expect(await linkedSkus(adminPage, sourceId, [relatedSku])).toEqual([relatedSku]);
    } finally {
      await deleteProductBySku(adminPage, sourceSku);
      await deleteProductBySku(adminPage, relatedSku);
    }
  });
});
