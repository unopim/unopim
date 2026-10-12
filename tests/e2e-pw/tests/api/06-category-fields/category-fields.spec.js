/**
 * Category Fields API — CRUD on typed columns attached to categories.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT } = require('../../../utils/api/config');
const { get, post, put, deleteIfExists } = require('../../../utils/api/request-wrapper');
const {
  expectOk, expectResponseTimeUnder, expectListEnvelope,
  expectMatchesSchema, expectSuccessEnvelope, expectValidationError, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');

test.describe('Category Fields API', () => {
  const created = new Set();

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const code of created) {
      await deleteIfExists(ctx, apiToken, `/category-fields/${encodeURIComponent(code)}`);
    }
    await ctx.dispose();
  });

  // ── List & Schema ───────────────────────────────────────────────────────

  test('6.1 - GET /category-fields returns paginated envelope', async ({ api }) => {
    const result = await get(api, '/category-fields');
    expectOk(result);
    expectListEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.list);
  });

  test('6.2 - Each list item matches schema', async ({ api }) => {
    const { body } = await get(api, '/category-fields', { params: { limit: 10 } });
    for (const f of body.data) expectMatchesSchema(schemas.categoryField, f, 'categoryField');
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('6.3 - POST creates a text category-field', async ({ api, uid }) => {
    const payload = payloads.categoryFields.build({ code: `cf_text_${uid}` });
    const result = await post(api, '/category-fields', { data: payload });
    expectOk(result);
    expectSuccessEnvelope(result.body);
    created.add(payload.code);
  });

  test('6.4 - POST creates a boolean category-field', async ({ api, uid }) => {
    const payload = payloads.categoryFields.buildBoolean({ code: `cf_bool_${uid}` });
    const result = await post(api, '/category-fields', { data: payload });
    expectOk(result);
    created.add(payload.code);
  });

  test('6.5 - POST creates a select category-field', async ({ api, uid }) => {
    const payload = payloads.categoryFields.buildSelect({ code: `cf_sel_${uid}` });
    const result = await post(api, '/category-fields', { data: payload });
    expectOk(result);
    created.add(payload.code);
  });

  test('6.6 - Created field retrievable via GET', async ({ api, uid }) => {
    const code = `cf_get_${uid}`;
    await post(api, '/category-fields', { data: payloads.categoryFields.build({ code }) });
    created.add(code);
    const result = await get(api, `/category-fields/${code}`);
    expectOk(result);
    expect(result.body.code).toBe(code);
    expectMatchesSchema(schemas.categoryField, result.body, 'categoryField');
  });

  // ── Negative ────────────────────────────────────────────────────────────

  test('6.7 - Duplicate code → 4xx', async ({ api, uid }) => {
    const code = `cf_dup_${uid}`;
    await post(api, '/category-fields', { data: payloads.categoryFields.build({ code }) });
    created.add(code);
    const result = await post(api, '/category-fields', { data: payloads.categoryFields.build({ code }) });
    expectValidationError(result);
  });

  test('6.8 - Missing type → 4xx', async ({ api }) => {
    const result = await post(api, '/category-fields', { data: payloads.categoryFields.buildMissingType() });
    expectValidationError(result);
  });

  test('6.9 - Empty payload → 4xx', async ({ api }) => {
    const result = await post(api, '/category-fields', { data: {} });
    expectValidationError(result);
  });

  test('6.10 - Invalid type value → 4xx', async ({ api, uid }) => {
    const result = await post(api, '/category-fields', {
      data: payloads.categoryFields.build({ code: `cf_bad_type_${uid}`, type: 'not_a_real_type_xyz' }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Update ──────────────────────────────────────────────────────────────

  test('6.11 - PUT updates an existing category-field', async ({ api, uid }) => {
    const code = `cf_upd_${uid}`;
    await post(api, '/category-fields', { data: payloads.categoryFields.build({ code }) });
    created.add(code);
    // PUT must NOT carry `code` or `type` — UnoPim rejects with 422 "immutable".
    const result = await put(api, `/category-fields/${code}`, {
      data: payloads.categoryFields.buildUpdate({ code, labels: { en_US: `Renamed ${uid}` } }),
    });
    expectOk(result);
    expectSuccessEnvelope(result.body);
  });

  test('6.12 - PUT non-existent → 4xx', async ({ api }) => {
    const result = await put(api, '/category-fields/non_existent_cf_xyz', {
      data: payloads.categoryFields.build({ code: 'non_existent_cf_xyz' }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Pagination / Filter / Sort ──────────────────────────────────────────

  test('6.13 - Pagination ?limit honored', async ({ api }) => {
    const result = await get(api, '/category-fields', { params: { limit: 2, page: 1 } });
    expectOk(result);
    expect(result.body.data.length).toBeLessThanOrEqual(2);
  });

  test('6.14 - Filter by type=text', async ({ api }) => {
    const result = await get(api, '/category-fields', {
      params: { filters: JSON.stringify({ type: [{ operator: '=', value: 'text' }] }) },
    });
    expectOk(result);
    for (const f of result.body.data) {
      if (f.type) expect(f.type).toBe('text');
    }
  });

  test('6.15 - Sort by position asc', async ({ api }) => {
    const result = await get(api, '/category-fields', { params: { sort: 'position', order: 'asc', limit: 50 } });
    expectOk(result);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('6.16 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, '/category-fields');
    expectUnauthorized(result);
  });
});
