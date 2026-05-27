/**
 * Configurable Products API.
 *
 * Configurables declare `super_attributes` — codes of `select` attributes
 * variants must differ by. UnoPim enforces that super_attributes must already
 * be present in the product's family, so we reuse the seed `color` + `size`
 * attributes shipped in the `default` family rather than minting custom ones.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT } = require('../../../utils/api/config');
const { get, post, put, deleteIfExists } = require('../../../utils/api/request-wrapper');
const {
  expectOk, expectResponseTimeUnder,
  expectMatchesSchema, expectSuccessEnvelope, expectValidationError, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');
const testData = require('../../../fixtures/test-data');

test.describe('Configurable Products API', () => {
  const createdProducts = new Set();
  // UnoPim 2.1 exposes /configurable-products for both GET and POST.
  const configurablePath = process.env.API_CONFIGURABLE_PATH || '/configurable-products';
  // Super-attributes MUST be present in the product's family. Default UnoPim
  // ships `color` and `size` in the `default` family, both as select-typed
  // attributes — use them directly so we don't have to mutate the family.
  const superAttrs = (process.env.API_SUPER_ATTRS || 'color,size').split(',').map(s => s.trim());

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const sku of createdProducts) {
      await deleteIfExists(ctx, apiToken, `/products/${encodeURIComponent(sku)}`);
    }
    await ctx.dispose();
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('13.1 - POST creates a configurable parent', async ({ api, uid }) => {
    const payload = payloads.configurableProducts.build({ sku: `cfg_${uid}`, superAttributes: superAttrs });
    const result = await post(api, configurablePath, { data: payload });
    expectOk(result);
    expectSuccessEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.write);
    createdProducts.add(payload.sku);
  });

  test('13.2 - GET configurable returns parent + variants array', async ({ api, uid }) => {
    const sku = `cfg_get_${uid}`;
    await post(api, configurablePath, { data: payloads.configurableProducts.build({ sku, superAttributes: superAttrs }) });
    createdProducts.add(sku);

    const result = await get(api, `${configurablePath}/${sku}`);
    expectOk(result);
    expectMatchesSchema(schemas.configurableProduct, result.body, 'configurableProduct');
    expect(Array.isArray(result.body.variants || [])).toBe(true);
  });

  // ── Negative ────────────────────────────────────────────────────────────

  test('13.3 - Duplicate sku → 4xx', async ({ api, uid }) => {
    const sku = `cfg_dup_${uid}`;
    await post(api, configurablePath, { data: payloads.configurableProducts.build({ sku, superAttributes: superAttrs }) });
    createdProducts.add(sku);
    const dup = await post(api, configurablePath, { data: payloads.configurableProducts.build({ sku, superAttributes: superAttrs }) });
    expectValidationError(dup);
  });

  test('13.4 - Missing super_attributes → 4xx', async ({ api, uid }) => {
    const payload = payloads.configurableProducts.build({ sku: `cfg_nosa_${uid}`, superAttributes: superAttrs });
    delete payload.super_attributes;
    const result = await post(api, configurablePath, { data: payload });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('13.5 - super_attributes referencing non-existent attribute → 4xx', async ({ api, uid }) => {
    const payload = payloads.configurableProducts.build({ sku: `cfg_bad_sa_${uid}`, superAttributes: ['no_such_attr_xyz'] });
    const result = await post(api, configurablePath, { data: payload });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('13.6 - Empty payload → 4xx', async ({ api }) => {
    const result = await post(api, configurablePath, { data: {} });
    expectValidationError(result);
  });

  // ── Update ──────────────────────────────────────────────────────────────

  test('13.7 - PUT updates configurable name', async ({ api, uid }) => {
    const sku = `cfg_upd_${uid}`;
    await post(api, configurablePath, { data: payloads.configurableProducts.build({ sku, superAttributes: superAttrs }) });
    createdProducts.add(sku);

    const updated = payloads.configurableProducts.build({ sku, superAttributes: superAttrs, name: `Renamed ${uid}` });
    const result = await put(api, `${configurablePath}/${sku}`, { data: updated });
    expectOk(result);
  });

  test('13.8 - PUT non-existent → 4xx', async ({ api }) => {
    const result = await put(api, `${configurablePath}/${testData.nonExistent.sku}`, {
      data: payloads.configurableProducts.build({ sku: testData.nonExistent.sku, superAttributes: superAttrs }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Pagination / Auth ───────────────────────────────────────────────────

  test('13.9 - Pagination limit honored', async ({ api }) => {
    const result = await get(api, configurablePath, { params: { limit: 5, page: 1 } });
    expectOk(result);
  });

  test('13.10 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, configurablePath);
    expectUnauthorized(result);
  });
});
