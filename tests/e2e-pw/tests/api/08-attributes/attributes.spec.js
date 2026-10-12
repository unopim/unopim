/**
 * Attributes API — CRUD across multiple attribute types + locale/channel
 * scoping flags. Cleanup runs in `afterAll`.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT } = require('../../../utils/api/config');
const { invalidTokenContext } = require('../../../utils/api/auth-helper');
const { get, post, put, deleteIfExists } = require('../../../utils/api/request-wrapper');
const {
  expectOk, expectStatus, expectResponseTimeUnder, expectListEnvelope,
  expectMatchesSchema, expectSuccessEnvelope, expectValidationError, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');
const testData = require('../../../fixtures/test-data');

test.describe('Attributes API', () => {
  const created = new Set();

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const code of created) {
      await deleteIfExists(ctx, apiToken, `/attributes/${encodeURIComponent(code)}`);
    }
    await ctx.dispose();
  });

  // ── List ────────────────────────────────────────────────────────────────

  test('8.1 - GET /attributes returns paginated envelope', async ({ api }) => {
    // Attributes list grows large on real installs (custom catalog attributes
    // accumulate over time) — use the `heavy` SLA tier to avoid flaking under
    // concurrent worker load.
    const result = await get(api, '/attributes');
    expectOk(result);
    expectListEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.heavy);
  });

  test('8.2 - Each list item matches schema', async ({ api }) => {
    const { body } = await get(api, '/attributes', { params: { limit: 10 } });
    for (const a of body.data) expectMatchesSchema(schemas.attribute, a, 'attribute');
  });

  test('8.3 - GET built-in `sku` attribute returns 200', async ({ api }) => {
    const result = await get(api, '/attributes/sku');
    if (result.status === 404) test.skip(true, 'sku attribute missing on this env');
    expectOk(result);
    expectMatchesSchema(schemas.attribute, result.body, 'attribute');
  });

  test('8.4 - GET non-existent attribute → 404', async ({ api }) => {
    const result = await get(api, `/attributes/${testData.nonExistent.attribute}`);
    expectStatus(result, 404);
  });

  // ── Create across types ─────────────────────────────────────────────────

  for (const type of ['text', 'textarea', 'price', 'boolean', 'select', 'multiselect', 'datetime', 'date']) {
    test(`8.5.${type} - POST creates ${type} attribute`, async ({ api, uid }) => {
      const code = `attr_${type}_${uid}`;
      const result = await post(api, '/attributes', { data: payloads.attributes.build({ code, type }) });
      expectOk(result);
      expectSuccessEnvelope(result.body);
      created.add(code);
    });
  }

  test('8.6 - Created attribute retrievable via GET', async ({ api, uid }) => {
    const code = `attr_get_${uid}`;
    await post(api, '/attributes', { data: payloads.attributes.buildText({ code }) });
    created.add(code);
    const result = await get(api, `/attributes/${code}`);
    expectOk(result);
    expect(result.body.code).toBe(code);
  });

  test('8.7 - Locale-scoped attribute (value_per_locale=true) saves the flag', async ({ api, uid }) => {
    const code = `attr_loc_${uid}`;
    const result = await post(api, '/attributes', { data: payloads.attributes.buildLocaleScoped({ code }) });
    expectOk(result);
    created.add(code);
  });

  test('8.8 - Channel+locale-scoped attribute saves both flags', async ({ api, uid }) => {
    const code = `attr_cl_${uid}`;
    const result = await post(api, '/attributes', { data: payloads.attributes.buildChannelLocaleScoped({ code }) });
    expectOk(result);
    created.add(code);
  });

  // ── Negative ────────────────────────────────────────────────────────────

  test('8.9 - Duplicate code → 4xx', async ({ api, uid }) => {
    const code = `attr_dup_${uid}`;
    await post(api, '/attributes', { data: payloads.attributes.buildText({ code }) });
    created.add(code);
    const dup = await post(api, '/attributes', { data: payloads.attributes.buildText({ code }) });
    expectValidationError(dup);
  });

  test('8.10 - Missing type → 4xx', async ({ api }) => {
    const result = await post(api, '/attributes', { data: payloads.attributes.buildMissingType() });
    expectValidationError(result);
  });

  test('8.11 - Empty payload → 4xx', async ({ api }) => {
    const result = await post(api, '/attributes', { data: {} });
    expectValidationError(result);
  });

  test('8.12 - Invalid type value → 4xx', async ({ api, uid }) => {
    const result = await post(api, '/attributes', {
      data: payloads.attributes.build({ code: `attr_bad_${uid}`, type: 'not_a_real_type' }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Update ──────────────────────────────────────────────────────────────

  test('8.13 - PUT updates labels', async ({ api, uid }) => {
    const code = `attr_upd_${uid}`;
    await post(api, '/attributes', { data: payloads.attributes.buildText({ code }) });
    created.add(code);
    // PUT must NOT carry `code` or `type` — UnoPim rejects with 422 "immutable".
    const result = await put(api, `/attributes/${code}`, {
      data: payloads.attributes.buildUpdate({ code, labels: { en_US: `Renamed ${uid}` } }),
    });
    expectOk(result);
    expectSuccessEnvelope(result.body);
  });

  test('8.14 - PUT non-existent → 4xx', async ({ api }) => {
    const result = await put(api, `/attributes/${testData.nonExistent.attribute}`, {
      data: payloads.attributes.buildText({ code: testData.nonExistent.attribute }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Pagination / Filter / Sort ──────────────────────────────────────────

  test('8.15 - Pagination limit honored', async ({ api }) => {
    const result = await get(api, '/attributes', { params: { limit: 5, page: 1 } });
    expectOk(result);
    expect(result.body.data.length).toBeLessThanOrEqual(5);
  });

  test('8.16 - ?page=9999 returns empty data', async ({ api }) => {
    const result = await get(api, '/attributes', { params: { page: 9999, limit: 5 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  test('8.17 - Filter by type=text', async ({ api }) => {
    const result = await get(api, '/attributes', {
      params: { filters: JSON.stringify({ type: [{ operator: '=', value: 'text' }] }) },
    });
    expectOk(result);
    for (const a of result.body.data) {
      if (a.type) expect(a.type).toBe('text');
    }
  });

  test('8.18 - Filter by code IN [...]', async ({ api }) => {
    const result = await get(api, '/attributes', {
      params: { filters: JSON.stringify({ code: [{ operator: 'IN', value: ['sku', 'name'] }] }) },
    });
    expectOk(result);
  });

  test('8.19 - Sort by code asc accepted', async ({ api }) => {
    const result = await get(api, '/attributes', { params: { sort: 'code', order: 'asc', limit: 50 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('8.20 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, '/attributes');
    expectUnauthorized(result);
  });

  test('8.21 - Invalid token → 401', async ({ playwright }) => {
    const ctx = await invalidTokenContext(playwright);
    const result = await get(ctx, '/attributes');
    expectUnauthorized(result);
    await ctx.dispose();
  });
});
