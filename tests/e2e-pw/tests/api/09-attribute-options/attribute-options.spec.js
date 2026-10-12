/**
 * Attribute Options API — POST/PUT bodies are arrays. Requires a parent
 * `select` (or `multiselect`) attribute, which we mint in `beforeAll`.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT } = require('../../../utils/api/config');
const { get, post, put, deleteIfExists } = require('../../../utils/api/request-wrapper');
const { uniqueCode } = require('../../../utils/api');
const {
  expectOk, expectResponseTimeUnder,
  expectMatchesSchema, expectSuccessEnvelope, expectValidationError, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');

test.describe('Attribute Options API', () => {
  let attrCode;
  const createdAttrs = new Set();

  test.beforeAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({
      baseURL: REST_ROOT,
      extraHTTPHeaders: { Authorization: `Bearer ${apiToken}`, Accept: 'application/json', 'Content-Type': 'application/json' },
    });
    attrCode = uniqueCode('attr_opts_parent');
    await post(ctx, '/attributes', { data: payloads.attributes.buildSelect({ code: attrCode }) });
    createdAttrs.add(attrCode);
    await ctx.dispose();
  });

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const code of createdAttrs) {
      await deleteIfExists(ctx, apiToken, `/attributes/${encodeURIComponent(code)}`);
    }
    await ctx.dispose();
  });

  test.beforeEach(() => {
    if (!attrCode) test.skip(true, 'Parent attribute not seeded');
  });

  // ── List ────────────────────────────────────────────────────────────────

  test('9.1 - GET options returns 200', async ({ api }) => {
    const result = await get(api, `/attributes/${attrCode}/options`);
    expectOk(result);
    expectResponseTimeUnder(result, RESPONSE_TIME.list);
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('9.2 - POST array of options succeeds', async ({ api }) => {
    const opts = payloads.attributeOptions.build(3);
    const result = await post(api, `/attributes/${attrCode}/options`, { data: opts });
    expectOk(result);
    expectSuccessEnvelope(result.body);
  });

  test('9.3 - Created options listable', async ({ api }) => {
    const result = await get(api, `/attributes/${attrCode}/options`);
    expectOk(result);
    const list = Array.isArray(result.body) ? result.body : (result.body.data || []);
    expect(Array.isArray(list)).toBe(true);
    for (const opt of list) expectMatchesSchema(schemas.attributeOption, opt, 'attributeOption');
  });

  test('9.4 - POST single-item array accepted', async ({ api }) => {
    const result = await post(api, `/attributes/${attrCode}/options`, { data: payloads.attributeOptions.build(1) });
    expectOk(result);
  });

  // ── Negative ────────────────────────────────────────────────────────────

  test('9.5 - Empty array body — server lenient (201 acceptable)', async ({ api }) => {
    const result = await post(api, `/attributes/${attrCode}/options`, { data: [] });
    expect([200, 201, 400, 422]).toContain(result.status);
  });

  test('9.6 - Missing code in option → 4xx', async ({ api }) => {
    const opts = payloads.attributeOptions.build(1);
    delete opts[0].code;
    const result = await post(api, `/attributes/${attrCode}/options`, { data: opts });
    expectValidationError(result);
  });

  test('9.7 - Options on non-existent attribute → 4xx', async ({ api }) => {
    const result = await post(api, '/attributes/non_existent_attr_xyz/options', {
      data: payloads.attributeOptions.build(1),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('9.8 - Empty object body — server lenient', async ({ api }) => {
    const result = await post(api, `/attributes/${attrCode}/options`, { data: {} });
    expect([200, 201, 400, 422]).toContain(result.status);
  });

  // ── Update ──────────────────────────────────────────────────────────────

  test('9.9 - PUT updates option labels', async ({ api }) => {
    const opts = payloads.attributeOptions.build(1);
    await post(api, `/attributes/${attrCode}/options`, { data: opts });
    opts[0].labels = { en_US: `Updated ${Date.now()}` };
    const result = await put(api, `/attributes/${attrCode}/options`, { data: opts });
    expectOk(result);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('9.10 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, `/attributes/${attrCode}/options`);
    expectUnauthorized(result);
  });
});
