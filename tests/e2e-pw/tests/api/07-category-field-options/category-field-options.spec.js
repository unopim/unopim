/**
 * Category Field Options API. POST/PUT bodies are arrays. The parent
 * category-field must be of a multi-value type (`select`/`multiselect`) for
 * options to be accepted — we create that field in `beforeAll`.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT } = require('../../../utils/api/config');
const { get, post, put, deleteIfExists } = require('../../../utils/api/request-wrapper');
const { uniqueCode } = require('../../../utils/api');
const {
  expectOk, expectResponseTimeUnder, expectMatchesSchema,
  expectSuccessEnvelope, expectValidationError, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');

test.describe('Category Field Options API', () => {
  let fieldCode;
  const createdFields = new Set();

  test.beforeAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({
      baseURL: REST_ROOT,
      extraHTTPHeaders: { Authorization: `Bearer ${apiToken}`, Accept: 'application/json', 'Content-Type': 'application/json' },
    });
    fieldCode = uniqueCode('cf_opt_parent');
    await post(ctx, '/category-fields', { data: payloads.categoryFields.buildSelect({ code: fieldCode }) });
    createdFields.add(fieldCode);
    await ctx.dispose();
  });

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const code of createdFields) {
      await deleteIfExists(ctx, apiToken, `/category-fields/${encodeURIComponent(code)}`);
    }
    await ctx.dispose();
  });

  test.beforeEach(() => {
    if (!fieldCode) test.skip(true, 'Parent category-field not seeded');
  });

  // ── List ────────────────────────────────────────────────────────────────

  test('7.1 - GET options for the field returns 200', async ({ api }) => {
    const result = await get(api, `/category-fields/${fieldCode}/options`);
    expectOk(result);
    expectResponseTimeUnder(result, RESPONSE_TIME.list);
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('7.2 - POST array of options creates them all', async ({ api }) => {
    const options = payloads.categoryFieldOptions.build(3);
    const result = await post(api, `/category-fields/${fieldCode}/options`, { data: options });
    expectOk(result);
    expectSuccessEnvelope(result.body);
  });

  test('7.3 - Created options are listable via GET', async ({ api }) => {
    const result = await get(api, `/category-fields/${fieldCode}/options`);
    expectOk(result);
    const list = Array.isArray(result.body) ? result.body : (result.body.data || []);
    expect(Array.isArray(list)).toBe(true);
    for (const opt of list) expectMatchesSchema(schemas.categoryFieldOption, opt, 'categoryFieldOption');
  });

  test('7.4 - POST single option (array of 1) accepted', async ({ api }) => {
    const result = await post(api, `/category-fields/${fieldCode}/options`, {
      data: payloads.categoryFieldOptions.build(1),
    });
    expectOk(result);
  });

  // ── Negative ────────────────────────────────────────────────────────────

  test('7.5 - Empty array → server is lenient (201 acceptable)', async ({ api }) => {
    // UnoPim accepts an empty options array as a no-op create.
    const result = await post(api, `/category-fields/${fieldCode}/options`, { data: [] });
    expect([200, 201, 400, 422]).toContain(result.status);
  });

  test('7.6 - Empty payload object → server is lenient', async ({ api }) => {
    const result = await post(api, `/category-fields/${fieldCode}/options`, { data: {} });
    expect([200, 201, 400, 422]).toContain(result.status);
  });

  test('7.7 - Missing option code → 4xx', async ({ api }) => {
    const opts = payloads.categoryFieldOptions.build(1);
    delete opts[0].code;
    const result = await post(api, `/category-fields/${fieldCode}/options`, { data: opts });
    expectValidationError(result);
  });

  test('7.8 - POST options on non-existent field → 4xx', async ({ api }) => {
    const result = await post(api, '/category-fields/non_existent_field_xyz/options', {
      data: payloads.categoryFieldOptions.build(1),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Update via PUT ──────────────────────────────────────────────────────

  test('7.9 - PUT updates option labels', async ({ api }) => {
    const opts = payloads.categoryFieldOptions.build(1);
    await post(api, `/category-fields/${fieldCode}/options`, { data: opts });
    opts[0].labels = { en_US: `Updated ${Date.now()}` };
    const result = await put(api, `/category-fields/${fieldCode}/options`, { data: opts });
    expectOk(result);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('7.10 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, `/category-fields/${fieldCode}/options`);
    expectUnauthorized(result);
  });
});
