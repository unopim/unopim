/**
 * Attribute Groups API — CRUD on attribute groupings used inside families.
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
const testData = require('../../../fixtures/test-data');

test.describe('Attribute Groups API', () => {
  const created = new Set();

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const code of created) {
      await deleteIfExists(ctx, apiToken, `/attribute-groups/${encodeURIComponent(code)}`);
    }
    await ctx.dispose();
  });

  // ── List ────────────────────────────────────────────────────────────────

  test('10.1 - GET /attribute-groups returns paginated envelope', async ({ api }) => {
    const result = await get(api, '/attribute-groups');
    expectOk(result);
    expectListEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.list);
  });

  test('10.2 - Each item matches schema', async ({ api }) => {
    const { body } = await get(api, '/attribute-groups');
    for (const g of body.data) expectMatchesSchema(schemas.attributeGroup, g, 'attributeGroup');
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('10.3 - POST creates group', async ({ api, uid }) => {
    const payload = payloads.attributeGroups.build({ code: `grp_${uid}` });
    const result = await post(api, '/attribute-groups', { data: payload });
    expectOk(result);
    expectSuccessEnvelope(result.body);
    created.add(payload.code);
  });

  test('10.4 - Created group retrievable', async ({ api, uid }) => {
    const code = `grp_get_${uid}`;
    await post(api, '/attribute-groups', { data: payloads.attributeGroups.build({ code }) });
    created.add(code);
    const result = await get(api, `/attribute-groups/${code}`);
    expectOk(result);
    expect(result.body.code).toBe(code);
  });

  test('10.5 - Duplicate code → 4xx', async ({ api, uid }) => {
    const code = `grp_dup_${uid}`;
    await post(api, '/attribute-groups', { data: payloads.attributeGroups.build({ code }) });
    created.add(code);
    const dup = await post(api, '/attribute-groups', { data: payloads.attributeGroups.build({ code }) });
    expectValidationError(dup);
  });

  test('10.6 - Missing code → 4xx', async ({ api }) => {
    const result = await post(api, '/attribute-groups', { data: payloads.attributeGroups.buildMissingCode() });
    expectValidationError(result);
  });

  test('10.7 - Empty payload → 4xx', async ({ api }) => {
    const result = await post(api, '/attribute-groups', { data: {} });
    expectValidationError(result);
  });

  // ── Update ──────────────────────────────────────────────────────────────

  test('10.8 - PUT updates labels', async ({ api, uid }) => {
    const code = `grp_upd_${uid}`;
    await post(api, '/attribute-groups', { data: payloads.attributeGroups.build({ code }) });
    created.add(code);
    const result = await put(api, `/attribute-groups/${code}`, {
      data: payloads.attributeGroups.build({ code, labels: { en_US: `Renamed ${uid}` } }),
    });
    expectOk(result);
  });

  test('10.9 - PUT non-existent → 4xx', async ({ api }) => {
    const result = await put(api, `/attribute-groups/${testData.nonExistent.attributeGroup}`, {
      data: payloads.attributeGroups.build({ code: testData.nonExistent.attributeGroup }),
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Pagination / Sort ───────────────────────────────────────────────────

  test('10.10 - Pagination limit honored', async ({ api }) => {
    const result = await get(api, '/attribute-groups', { params: { limit: 2, page: 1 } });
    expectOk(result);
    expect(result.body.data.length).toBeLessThanOrEqual(2);
  });

  test('10.11 - Sort by code asc', async ({ api }) => {
    const result = await get(api, '/attribute-groups', { params: { sort: 'code', order: 'asc', limit: 50 } });
    expectOk(result);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('10.12 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, '/attribute-groups');
    expectUnauthorized(result);
  });
});
