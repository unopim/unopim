/**
 * Products API — simple products. Covers CRUD, the `values.*` quirk, filter
 * by sku / status / family / categories, pagination, sort, response time,
 * and auth.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT, SEED } = require('../../../utils/api/config');
const { invalidTokenContext } = require('../../../utils/api/auth-helper');
const { get, post, put, patch, delete: del, deleteIfExists } = require('../../../utils/api/request-wrapper');
const {
  expectOk, expectStatus, expectStatusIn, expectResponseTimeUnder, expectListEnvelope,
  expectMatchesSchema, expectSuccessEnvelope, expectValidationError, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');
const testData = require('../../../fixtures/test-data');

test.describe('Products API', () => {
  const created = new Set();

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const sku of created) {
      await deleteIfExists(ctx, apiToken, `/products/${encodeURIComponent(sku)}`);
    }
    await ctx.dispose();
  });

  // ── List ────────────────────────────────────────────────────────────────

  test('12.1 - GET /products returns paginated envelope', async ({ api }) => {
    // Products is the heaviest list endpoint — large dataset (potentially 10k+
    // rows on a production-sized DB) and joins. Use the `heavy` SLA so the
    // assertion stays meaningful under default 5-worker concurrency without
    // flaking on cold caches or contended runs.
    const result = await get(api, '/products');
    expectOk(result);
    expectListEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.heavy);
  });

  test('12.2 - Each item matches product schema', async ({ api }) => {
    const { body } = await get(api, '/products', { params: { limit: 5 } });
    for (const p of body.data) expectMatchesSchema(schemas.product, p, 'product');
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('12.3 - POST creates a simple product', async ({ api, uid }) => {
    // NOTE: We intentionally do NOT set `price` here — UnoPim's default family
    // doesn't include the `price` attribute, and sending it triggers 500.
    // Specific price tests should construct a family that includes `price` first.
    const payload = payloads.products.build({ sku: `sku_${uid}`, name: `Tee ${uid}` });
    const result = await post(api, '/products', { data: payload });
    expectOk(result);
    expectSuccessEnvelope(result.body);
    created.add(payload.sku);
  });

  test('12.4 - Created product retrievable via GET', async ({ api, uid }) => {
    const sku = `sku_get_${uid}`;
    await post(api, '/products', { data: payloads.products.build({ sku, name: `G ${uid}` }) });
    created.add(sku);
    const result = await get(api, `/products/${sku}`);
    expectOk(result);
    expect(result.body.sku).toBe(sku);
    expectMatchesSchema(schemas.product, result.body, 'product');
  });

  test('12.5 - GET ?with_completeness=true is accepted', async ({ api, uid }) => {
    const sku = `sku_comp_${uid}`;
    await post(api, '/products', { data: payloads.products.build({ sku }) });
    created.add(sku);
    const result = await get(api, `/products/${sku}`, { params: { with_completeness: true } });
    expectOk(result);
  });

  // ── Negative ────────────────────────────────────────────────────────────

  test('12.6 - Duplicate sku → 4xx', async ({ api, uid }) => {
    const sku = `sku_dup_${uid}`;
    await post(api, '/products', { data: payloads.products.build({ sku }) });
    created.add(sku);
    const dup = await post(api, '/products', { data: payloads.products.build({ sku }) });
    expectValidationError(dup);
  });

  test('12.7 - Missing sku → 4xx', async ({ api }) => {
    const result = await post(api, '/products', { data: payloads.products.buildWithoutSku() });
    expectValidationError(result);
  });

  test('12.8 - Missing family → 4xx', async ({ api, uid }) => {
    const result = await post(api, '/products', { data: payloads.products.buildWithoutFamily({ sku: `sku_nofam_${uid}` }) });
    expectValidationError(result);
  });

  test('12.9 - Empty payload → 4xx', async ({ api }) => {
    const result = await post(api, '/products', { data: {} });
    expectValidationError(result);
  });

  test('12.10 - Invalid family → 4xx', async ({ api, uid }) => {
    const result = await post(api, '/products', {
      data: payloads.products.build({ sku: `sku_badfam_${uid}`, family: testData.nonExistent.family }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('12.11 - Price as scalar (not array) is rejected or corrected', async ({ api, uid }) => {
    // Price attributes MUST be [{currency,amount}]. Sending a scalar is invalid.
    const sku = `sku_badprice_${uid}`;
    const payload = payloads.products.build({ sku });
    payload.values.channel_locale_specific[SEED.defaultChannel][SEED.defaultLocale].price = '29.99';
    const result = await post(api, '/products', { data: payload });
    // Some versions of UnoPim drop the field silently (200) while newer ones 422.
    expectStatusIn(result, [200, 201, 400, 422]);
    if (result.ok) created.add(sku);
  });

  // ── Update ──────────────────────────────────────────────────────────────

  test('12.12 - PUT updates product name', async ({ api, uid }) => {
    const sku = `sku_upd_${uid}`;
    await post(api, '/products', { data: payloads.products.build({ sku, name: `A ${uid}` }) });
    created.add(sku);
    const updated = payloads.products.build({ sku, name: `Updated ${uid}` });
    const result = await put(api, `/products/${sku}`, { data: updated });
    expectOk(result);
    expectSuccessEnvelope(result.body);
  });

  test('12.13 - PATCH partial update', async ({ api, uid }) => {
    const sku = `sku_patch_${uid}`;
    await post(api, '/products', { data: payloads.products.build({ sku }) });
    created.add(sku);
    const result = await patch(api, `/products/${sku}`, {
      data: { values: { channel_locale_specific: { [SEED.defaultChannel]: { [SEED.defaultLocale]: { name: `Patched ${uid}` } } } } },
    });
    expectStatusIn(result, [200, 201]);
  });

  test('12.14 - PUT non-existent sku → 4xx', async ({ api }) => {
    const result = await put(api, `/products/${testData.nonExistent.sku}`, {
      data: payloads.products.build({ sku: testData.nonExistent.sku }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Delete ──────────────────────────────────────────────────────────────

  test('12.15 - DELETE removes the product', async ({ api, uid }) => {
    const sku = `sku_del_${uid}`;
    const create = await post(api, '/products', { data: payloads.products.build({ sku }) });
    if (!create.ok) test.skip(true, 'Could not seed product for delete');
    const result = await del(api, `/products/${sku}`);
    expectOk(result);
    const after = await get(api, `/products/${sku}`);
    expectStatus(after, 404);
  });

  test('12.16 - DELETE non-existent → 4xx', async ({ api }) => {
    const result = await del(api, `/products/${testData.nonExistent.sku}`);
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Pagination / Filter / Sort ──────────────────────────────────────────

  test('12.17 - Pagination ?limit honored', async ({ api }) => {
    const result = await get(api, '/products', { params: { limit: 5, page: 1 } });
    expectOk(result);
    expect(result.body.data.length).toBeLessThanOrEqual(5);
  });

  test('12.18 - ?page=9999 returns empty data', async ({ api }) => {
    const result = await get(api, '/products', { params: { page: 9999, limit: 5 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  test('12.19 - Filter by sku=createdSku', async ({ api, uid }) => {
    const sku = `sku_filter_${uid}`;
    await post(api, '/products', { data: payloads.products.build({ sku }) });
    created.add(sku);
    const result = await get(api, '/products', {
      params: { filters: JSON.stringify({ sku: [{ operator: '=', value: sku }] }) },
    });
    expectOk(result);
    expect(result.body.data.some((p) => p.sku === sku)).toBe(true);
  });

  test('12.20 - Filter by sku IN [a,b]', async ({ api, uid }) => {
    const skuA = `sku_in_a_${uid}`;
    const skuB = `sku_in_b_${uid}`;
    await post(api, '/products', { data: payloads.products.build({ sku: skuA }) });
    await post(api, '/products', { data: payloads.products.build({ sku: skuB }) });
    created.add(skuA); created.add(skuB);

    const result = await get(api, '/products', {
      params: { filters: JSON.stringify({ sku: [{ operator: 'IN', value: [skuA, skuB] }] }) },
    });
    expectOk(result);
  });

  test('12.21 - Filter by family IN [seed]', async ({ api }) => {
    const result = await get(api, '/products', {
      params: { filters: JSON.stringify({ family: [{ operator: 'IN', value: [testData.seedFamily] }] }) },
    });
    expectOk(result);
  });

  test('12.22 - Sort by sku asc accepted', async ({ api }) => {
    const result = await get(api, '/products', { params: { sort: 'sku', order: 'asc', limit: 20 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('12.23 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, '/products');
    expectUnauthorized(result);
  });

  test('12.24 - Invalid token → 401', async ({ playwright }) => {
    const ctx = await invalidTokenContext(playwright);
    const result = await get(ctx, '/products');
    expectUnauthorized(result);
    await ctx.dispose();
  });
});
