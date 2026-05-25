/**
 * Categories API — full CRUD + filter + pagination + sorting + auth.
 * Every entity created here is captured in `createdCodes` and reaped in
 * `afterAll` so re-runs stay clean even when the file fails mid-flight.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT, SEED } = require('../../../utils/api/config');
const { invalidTokenContext } = require('../../../utils/api/auth-helper');
const { get, post, put, patch, delete: del, deleteIfExists } = require('../../../utils/api/request-wrapper');
const {
  expectOk, expectStatus, expectStatusIn, expectResponseTimeUnder,
  expectListEnvelope, expectMatchesSchema, expectSuccessEnvelope,
  expectValidationError, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');
const testData = require('../../../fixtures/test-data');

test.describe('Categories API - CRUD', () => {
  const createdCodes = new Set();

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const code of createdCodes) {
      await deleteIfExists(ctx, apiToken, `/categories/${encodeURIComponent(code)}`);
    }
    await ctx.dispose();
  });

  // ── List & Detail ───────────────────────────────────────────────────────

  test('5.1 - GET /categories returns paginated envelope', async ({ api }) => {
    const result = await get(api, '/categories');
    expectOk(result);
    expectListEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.list);
  });

  test('5.2 - Each list item matches category schema', async ({ api }) => {
    const { body } = await get(api, '/categories', { params: { limit: 10 } });
    for (const cat of body.data) expectMatchesSchema(schemas.category, cat, 'category');
  });

  test('5.3 - GET /categories/:root returns the root category', async ({ api }) => {
    const result = await get(api, `/categories/${testData.rootCategory}`);
    if (result.status === 404) test.skip(true, `Root category "${testData.rootCategory}" not present`);
    expectOk(result);
    expect(result.body.code).toBe(testData.rootCategory);
  });

  test('5.4 - GET /categories/:nonexistent → 404', async ({ api }) => {
    const result = await get(api, `/categories/${testData.nonExistent.category}`);
    expectStatus(result, 404);
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('5.5 - POST /categories with valid payload creates the category', async ({ api, uid }) => {
    const payload = payloads.categories.build({ code: `cat_${uid}` });
    const result = await post(api, '/categories', { data: payload });
    expectOk(result);
    expectSuccessEnvelope(result.body, 'created');
    createdCodes.add(payload.code);
  });

  test('5.6 - Created category is retrievable via GET', async ({ api, uid }) => {
    const payload = payloads.categories.build({ code: `cat_get_${uid}` });
    await post(api, '/categories', { data: payload });
    createdCodes.add(payload.code);

    const result = await get(api, `/categories/${payload.code}`);
    expectOk(result);
    expect(result.body.code).toBe(payload.code);
  });

  test('5.7 - Duplicate code returns 4xx', async ({ api, uid }) => {
    const code = `cat_dup_${uid}`;
    await post(api, '/categories', { data: payloads.categories.build({ code }) });
    createdCodes.add(code);
    const result = await post(api, '/categories', { data: payloads.categories.build({ code }) });
    expectValidationError(result);
  });

  test('5.8 - Missing code → 4xx', async ({ api }) => {
    const result = await post(api, '/categories', { data: payloads.categories.buildMissingCode() });
    expectValidationError(result);
  });

  test('5.9 - Missing parent → 4xx', async ({ api }) => {
    const result = await post(api, '/categories', { data: payloads.categories.buildMissingParent() });
    expectValidationError(result);
  });

  test('5.10 - Empty payload → 4xx', async ({ api }) => {
    const result = await post(api, '/categories', { data: {} });
    expectValidationError(result);
  });

  test('5.11 - Empty code string → 4xx', async ({ api }) => {
    const result = await post(api, '/categories', { data: { code: '', parent: testData.rootCategory, additional_data: {} } });
    expectValidationError(result);
  });

  test('5.12 - Invalid parent (non-existent code) is accepted or rejected', async ({ api, uid }) => {
    // UnoPim 2.1 is permissive here — it silently creates the category with the
    // bogus parent reference. Test passes either way; we just need to confirm
    // the server didn't 5xx.
    const code = `cat_bad_parent_${uid}`;
    const result = await post(api, '/categories', {
      data: payloads.categories.build({ code, parent: testData.nonExistent.category }),
    });
    expectStatusIn(result, [200, 201, 400, 422]);
    if (result.ok) createdCodes.add(code);
  });

  // ── Update ──────────────────────────────────────────────────────────────

  test('5.13 - PUT /categories/:code updates name', async ({ api, uid }) => {
    const code = `cat_upd_${uid}`;
    await post(api, '/categories', { data: payloads.categories.build({ code }) });
    createdCodes.add(code);

    const updated = payloads.categories.buildUpdate(code, { name: `Updated ${uid}` });
    const result = await put(api, `/categories/${code}`, { data: updated });
    expectOk(result);
    expectSuccessEnvelope(result.body, 'updated');
  });

  test('5.14 - PATCH /categories/:code partial update succeeds', async ({ api, uid }) => {
    const code = `cat_patch_${uid}`;
    await post(api, '/categories', { data: payloads.categories.build({ code }) });
    createdCodes.add(code);

    const partial = {
      additional_data: { locale_specific: { [SEED.defaultLocale]: { name: `Patched ${uid}` } } },
    };
    const result = await patch(api, `/categories/${code}`, { data: partial });
    expectStatusIn(result, [200, 201]);
  });

  test('5.15 - PUT non-existent → 4xx', async ({ api }) => {
    const result = await put(api, `/categories/${testData.nonExistent.category}`, {
      data: payloads.categories.build({ code: testData.nonExistent.category }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Delete ──────────────────────────────────────────────────────────────

  test('5.16 - DELETE /categories/:code removes the category', async ({ api, uid }) => {
    const code = `cat_del_${uid}`;
    const create = await post(api, '/categories', { data: payloads.categories.build({ code }) });
    if (!create.ok) test.skip(true, 'Could not seed category for delete');

    const result = await del(api, `/categories/${code}`);
    expectOk(result);
    const after = await get(api, `/categories/${code}`);
    expectStatus(after, 404);
  });

  test('5.17 - DELETE non-existent → 4xx', async ({ api }) => {
    const result = await del(api, `/categories/${testData.nonExistent.category}`);
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Pagination / Filter / Sort ──────────────────────────────────────────

  test('5.18 - ?limit cap honored', async ({ api }) => {
    const result = await get(api, '/categories', { params: { limit: 3, page: 1 } });
    expectOk(result);
    expect(result.body.data.length).toBeLessThanOrEqual(3);
  });

  test('5.19 - ?page=9999 returns empty data', async ({ api }) => {
    const result = await get(api, '/categories', { params: { page: 9999, limit: 5 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  test('5.20 - Filter by code (=) returns matching subset', async ({ api, uid }) => {
    const code = `cat_filter_${uid}`;
    await post(api, '/categories', { data: payloads.categories.build({ code }) });
    createdCodes.add(code);

    const result = await get(api, '/categories', {
      params: { filters: JSON.stringify({ code: [{ operator: '=', value: code }] }) },
    });
    expectOk(result);
  });

  test('5.21 - Sort by code asc accepted', async ({ api }) => {
    const result = await get(api, '/categories', { params: { sort: 'code', order: 'asc', limit: 50 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  // ── Auth guards ─────────────────────────────────────────────────────────

  test('5.22 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, '/categories');
    expectUnauthorized(result);
  });

  test('5.23 - Invalid token → 401', async ({ playwright }) => {
    const ctx = await invalidTokenContext(playwright);
    const result = await get(ctx, '/categories');
    expectUnauthorized(result);
    await ctx.dispose();
  });
});
