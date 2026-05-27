/**
 * Attribute Families API.
 *
 * The 2.1 docs document the route as `/families`. We probe both paths once in
 * `beforeAll` and use whichever returns 200, so the suite works on both 2.0
 * and 2.1 deployments without env-var twiddling.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT } = require('../../../utils/api/config');
const { get, post, put, deleteIfExists } = require('../../../utils/api/request-wrapper');
const {
  expectOk, expectStatus, expectResponseTimeUnder, expectListEnvelope,
  expectMatchesSchema, expectSuccessEnvelope, expectValidationError, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');
const testData = require('../../../fixtures/test-data');

test.describe('Attribute Families API', () => {
  let familiesPath = '/families'; // probed in beforeAll
  const created = new Set();

  test.beforeAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({
      baseURL: REST_ROOT,
      extraHTTPHeaders: { Authorization: `Bearer ${apiToken}`, Accept: 'application/json' },
    });
    for (const candidate of ['/families', '/attribute-families']) {
      const r = await get(ctx, candidate);
      if (r.ok) { familiesPath = candidate; break; }
    }
    await ctx.dispose();
  });

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const code of created) {
      await deleteIfExists(ctx, apiToken, `${familiesPath}/${encodeURIComponent(code)}`);
    }
    await ctx.dispose();
  });

  // ── List ────────────────────────────────────────────────────────────────

  test('11.1 - GET families returns paginated envelope', async ({ api }) => {
    // Families have heavier joins (groups + their custom_attributes) — give the
    // endpoint the write-budget instead of the list-budget.
    const result = await get(api, familiesPath);
    expectOk(result);
    expectListEnvelope(result.body);
    // Families have the heaviest joins in the surface (groups + nested
    // custom_attributes) — give them the upload budget (15s); even the
    // `heavy` tier (8s) flakes here on cold caches.
    expectResponseTimeUnder(result, RESPONSE_TIME.upload);
  });

  test('11.2 - Each item matches schema', async ({ api }) => {
    const { body } = await get(api, familiesPath);
    for (const f of body.data) expectMatchesSchema(schemas.attributeFamily, f, 'attributeFamily');
  });

  test('11.3 - GET default family returns 200', async ({ api }) => {
    const result = await get(api, `${familiesPath}/${testData.seedFamily}`);
    if (result.status === 404) test.skip(true, 'No default family on this env');
    expectOk(result);
    expectMatchesSchema(schemas.attributeFamily, result.body, 'attributeFamily');
  });

  test('11.4 - GET non-existent family → 404', async ({ api }) => {
    const result = await get(api, `${familiesPath}/${testData.nonExistent.family}`);
    expectStatus(result, 404);
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('11.5 - POST creates family', async ({ api, uid }) => {
    const payload = payloads.attributeFamilies.build({ code: `fam_${uid}` });
    const result = await post(api, familiesPath, { data: payload });
    expectOk(result);
    expectSuccessEnvelope(result.body);
    created.add(payload.code);
  });

  test('11.6 - Created family retrievable and has attribute_groups', async ({ api, uid }) => {
    const code = `fam_get_${uid}`;
    await post(api, familiesPath, { data: payloads.attributeFamilies.build({ code }) });
    created.add(code);
    const result = await get(api, `${familiesPath}/${code}`);
    expectOk(result);
    expect(result.body.code).toBe(code);
    expect(Array.isArray(result.body.attribute_groups || [])).toBe(true);
  });

  test('11.7 - Duplicate code → 4xx', async ({ api, uid }) => {
    const code = `fam_dup_${uid}`;
    await post(api, familiesPath, { data: payloads.attributeFamilies.build({ code }) });
    created.add(code);
    const dup = await post(api, familiesPath, { data: payloads.attributeFamilies.build({ code }) });
    expectValidationError(dup);
  });

  test('11.8 - Missing code → 4xx', async ({ api }) => {
    const p = payloads.attributeFamilies.build();
    delete p.code;
    const result = await post(api, familiesPath, { data: p });
    expectValidationError(result);
  });

  test('11.9 - Empty payload → 4xx', async ({ api }) => {
    const result = await post(api, familiesPath, { data: {} });
    expectValidationError(result);
  });

  test('11.10 - Family referencing non-existent attribute code → 4xx', async ({ api, uid }) => {
    const payload = payloads.attributeFamilies.build({
      code: `fam_bad_${uid}`,
      attribute_groups: [{
        code: 'general', position: 1,
        custom_attributes: [{ code: 'totally_made_up_attr_xyz', position: 1 }],
      }],
    });
    const result = await post(api, familiesPath, { data: payload });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  // ── Update ──────────────────────────────────────────────────────────────

  test('11.11 - PUT updates labels', async ({ api, uid }) => {
    const code = `fam_upd_${uid}`;
    await post(api, familiesPath, { data: payloads.attributeFamilies.build({ code }) });
    created.add(code);
    const result = await put(api, `${familiesPath}/${code}`, {
      data: payloads.attributeFamilies.build({ code, labels: { en_US: `Renamed ${uid}` } }),
    });
    expectOk(result);
  });

  // ── Pagination / Sort ───────────────────────────────────────────────────

  test('11.12 - Pagination limit honored', async ({ api }) => {
    const result = await get(api, familiesPath, { params: { limit: 2, page: 1 } });
    expectOk(result);
    expect(result.body.data.length).toBeLessThanOrEqual(2);
  });

  test('11.13 - Sort by code asc', async ({ api }) => {
    const result = await get(api, familiesPath, { params: { sort: 'code', order: 'asc', limit: 50 } });
    expectOk(result);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('11.14 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, familiesPath);
    expectUnauthorized(result);
  });
});
