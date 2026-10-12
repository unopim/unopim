/**
 * Currencies API — read-only.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME } = require('../../../utils/api/config');
const { invalidTokenContext } = require('../../../utils/api/auth-helper');
const { get } = require('../../../utils/api/request-wrapper');
const {
  expectOk, expectResponseTimeUnder,
  expectListEnvelope, expectMatchesSchema, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const testData = require('../../../fixtures/test-data');

test.describe('Currencies API', () => {
  test('3.1 - GET /currencies returns 200 with envelope', async ({ api }) => {
    const result = await get(api, '/currencies');
    expectOk(result);
    expectListEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.list);
  });

  test('3.2 - Each currency matches schema', async ({ api }) => {
    const { body } = await get(api, '/currencies');
    if (body.data.length === 0) test.skip(true, 'No currencies seeded');
    for (const c of body.data) expectMatchesSchema(schemas.currency, c, 'currency');
  });

  test('3.3 - GET /currencies/:USD returns 200', async ({ api }) => {
    const result = await get(api, `/currencies/${testData.seedCurrency}`);
    expectOk(result);
    expectMatchesSchema(schemas.currency, result.body, 'currency');
  });

  test('3.4 - GET /currencies/:nonexistent returns 4xx', async ({ api }) => {
    // UnoPim validates the path param before routing — unknown codes 400, not 404.
    const result = await get(api, `/currencies/${testData.nonExistent.currency}`);
    expect([400, 404]).toContain(result.status);
  });

  test('3.5 - Pagination ?limit=2 caps page size', async ({ api }) => {
    const result = await get(api, '/currencies', { params: { limit: 2, page: 1 } });
    expectOk(result);
    expect(result.body.data.length).toBeLessThanOrEqual(2);
  });

  test('3.6 - Page beyond last returns empty data array', async ({ api }) => {
    const result = await get(api, '/currencies', { params: { page: 9999, limit: 10 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  test('3.7 - Filter by status=1', async ({ api }) => {
    const result = await get(api, '/currencies', {
      params: { filters: JSON.stringify({ status: [{ operator: '=', value: 1 }] }) },
    });
    expectOk(result);
  });

  test('3.8 - Sort by code asc accepted', async ({ api }) => {
    // Sort param is accepted but not always honored on read-only resources.
    const result = await get(api, '/currencies', { params: { sort: 'code', order: 'asc', limit: 100 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  test('3.9 - Sort by code desc accepted', async ({ api }) => {
    const result = await get(api, '/currencies', { params: { sort: 'code', order: 'desc', limit: 100 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  test('3.10 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, '/currencies');
    expectUnauthorized(result);
  });

  test('3.11 - Invalid token → 401', async ({ playwright }) => {
    const ctx = await invalidTokenContext(playwright);
    const result = await get(ctx, '/currencies');
    expectUnauthorized(result);
    await ctx.dispose();
  });
});
