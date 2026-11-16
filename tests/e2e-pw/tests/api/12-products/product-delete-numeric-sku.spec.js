/**
 * REST product delete with numeric SKUs. The delete events must carry the
 * product id: a SKU payload was read as an id by the publication guard and
 * overflowed the integer column on PostgreSQL for SKUs above 2147483647.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { REST_ROOT } = require('../../../utils/api/config');
const { get, post, delete: del, deleteIfExists } = require('../../../utils/api/request-wrapper');
const { expectOk, expectStatus } = require('../../../utils/api/response-validator');
const payloads = require('../../../payloads');

test.describe('Products API — delete by numeric SKU', () => {
  const created = new Set();

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const { path } of created) {
      await deleteIfExists(ctx, apiToken, path);
    }
    await ctx.dispose();
  });

  const numericSku = () => `9${Date.now()}${Math.floor(Math.random() * 1000)}`;

  test('12.N1 - DELETE a simple product whose numeric SKU exceeds the integer range', async ({ api }) => {
    const sku = numericSku();
    const path = `/products/${sku}`;

    expectOk(await post(api, '/products', { data: payloads.products.build({ sku }) }));
    created.add({ path });

    const result = await del(api, path);
    expectOk(result);
    expect(result.body.sku).toBe(sku);

    expectStatus(await get(api, path), 404);
  });

  test('12.N2 - DELETE a configurable product whose numeric SKU exceeds the integer range', async ({ api }) => {
    const sku = numericSku();
    const path = `/configurable-products/${sku}`;

    expectOk(await post(api, '/configurable-products', {
      data: payloads.configurableProducts.build({ sku }),
    }));
    created.add({ path });

    const result = await del(api, path);
    expectOk(result);

    expectStatus(await get(api, path), 404);
  });
});
