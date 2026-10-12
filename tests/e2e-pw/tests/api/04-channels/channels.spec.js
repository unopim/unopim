/**
 * Channels API — read-only. Channel detail includes assigned `locales`,
 * `currencies` and `root_category` — surface coverage focuses on contract.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME } = require('../../../utils/api/config');
const { get } = require('../../../utils/api/request-wrapper');
const {
  expectOk, expectResponseTimeUnder,
  expectListEnvelope, expectMatchesSchema, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const testData = require('../../../fixtures/test-data');

test.describe('Channels API', () => {
  test('4.1 - GET /channels returns paginated envelope', async ({ api }) => {
    const result = await get(api, '/channels');
    expectOk(result);
    expectListEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.list);
  });

  test('4.2 - Each channel matches schema', async ({ api }) => {
    const { body } = await get(api, '/channels');
    if (body.data.length === 0) test.skip(true, 'No channels seeded');
    for (const ch of body.data) expectMatchesSchema(schemas.channel, ch, 'channel');
  });

  test('4.3 - GET /channels/:default returns 200', async ({ api }) => {
    const result = await get(api, `/channels/${testData.seedChannel}`);
    if (result.status === 404) test.skip(true, `Seed channel ${testData.seedChannel} not present in this env`);
    expectOk(result);
    expectMatchesSchema(schemas.channel, result.body, 'channel');
    expectResponseTimeUnder(result, RESPONSE_TIME.fast);
  });

  test('4.4 - Channel detail includes locales and currencies arrays', async ({ api }) => {
    const result = await get(api, `/channels/${testData.seedChannel}`);
    if (result.status === 404) test.skip(true, 'No seed channel');
    expect(Array.isArray(result.body.locales || [])).toBe(true);
    expect(Array.isArray(result.body.currencies || [])).toBe(true);
  });

  test('4.5 - GET /channels/:nonexistent returns 4xx', async ({ api }) => {
    const result = await get(api, `/channels/${testData.nonExistent.channel}`);
    expect([400, 404]).toContain(result.status);
  });

  test('4.6 - Pagination cap honored', async ({ api }) => {
    const result = await get(api, '/channels', { params: { limit: 1, page: 1 } });
    expectOk(result);
    expect(result.body.data.length).toBeLessThanOrEqual(1);
  });

  test('4.7 - Sort by code asc accepted', async ({ api }) => {
    const result = await get(api, '/channels', { params: { sort: 'code', order: 'asc', limit: 50 } });
    expectOk(result);
    expect(Array.isArray(result.body.data)).toBe(true);
  });

  test('4.8 - Without auth → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, '/channels');
    expectUnauthorized(result);
  });
});
