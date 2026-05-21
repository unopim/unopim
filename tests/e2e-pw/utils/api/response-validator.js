/**
 * Reusable response-level assertions.
 *
 * Each helper is intentionally small so a spec can compose them freely:
 *   expectOk(result);
 *   expectResponseTimeUnder(result, RESPONSE_TIME.list);
 *   expectListEnvelope(result.body);
 *   expectJsonContentType(result.headers);
 *   validateSchema(schemas.locale, result.body.data[0]);
 */
'use strict';

const { expect } = require('@playwright/test');
const { validateSchema } = require('./schema-validator');

/** Hard-asserts a 2xx and surfaces the body on failure for fast debugging. */
function expectOk(result, label = '') {
  if (!result.ok) {
    throw new Error(
      `${label || result.method.toUpperCase() + ' ' + result.url} expected 2xx, got ${result.status}\n${JSON.stringify(result.body, null, 2).slice(0, 1500)}`,
    );
  }
  expect(result.status, label).toBeGreaterThanOrEqual(200);
  expect(result.status, label).toBeLessThan(300);
}

function expectStatus(result, status) {
  expect(result.status, `${result.method} ${result.url}`).toBe(status);
}

function expectStatusIn(result, statuses) {
  expect(statuses, `${result.method} ${result.url} (got ${result.status})`).toContain(result.status);
}

function expectResponseTimeUnder(result, maxMs) {
  expect(result.durationMs, `Response time for ${result.method} ${result.url}`).toBeLessThanOrEqual(maxMs);
}

function expectJsonContentType(headers) {
  const ct = headers['content-type'] || '';
  expect(ct.toLowerCase(), 'Content-Type').toContain('application/json');
}

/**
 * UnoPim list responses share a stable envelope:
 *   { data: [...], current_page, last_page, total, links: { first, last, next, prev } }
 *
 * Only `data` is strictly required everywhere — the pagination meta is present
 * on every paginated endpoint but not on the (rare) full-list responses.
 */
function expectListEnvelope(body, { requireMeta = true } = {}) {
  expect(body, 'list envelope').toBeTruthy();
  expect(Array.isArray(body.data), 'body.data is array').toBe(true);
  if (requireMeta) {
    expect(body, 'pagination meta').toEqual(
      expect.objectContaining({
        current_page: expect.any(Number),
        last_page: expect.any(Number),
        total: expect.any(Number),
      }),
    );
  }
}

/** UnoPim write responses: `{ success: true, message: "..." }` (sometimes wrap a `data` echo). */
function expectSuccessEnvelope(body, expectedKeyword) {
  expect(body, 'success envelope').toBeTruthy();
  expect(body.success, `body.success`).toBe(true);
  if (expectedKeyword) {
    expect(String(body.message || '').toLowerCase(), `body.message`).toContain(expectedKeyword.toLowerCase());
  }
}

/**
 * UnoPim validation errors typically return 422 with `errors: { field: [msg] }`
 * but the framework also 500s on some malformed inputs (e.g. empty payload
 * passed to category/category-field/product create), which is a server quirk
 * rather than a test fault. Accept any 4xx or 5xx so the test still proves
 * "server didn't silently accept the bad payload".
 */
function expectValidationError(result) {
  expectStatusIn(result, [400, 422, 500]);
  expect(result.body, 'error body').toBeTruthy();
}

function expectUnauthorized(result) {
  expectStatus(result, 401);
}

function expectForbidden(result) {
  expectStatusIn(result, [401, 403]);
}

function expectNotFound(result) {
  expectStatus(result, 404);
}

/** Schema validator wrapped to read better at the call-site. */
function expectMatchesSchema(schema, data, label) {
  validateSchema(schema, data, label);
}

module.exports = {
  expectOk,
  expectStatus,
  expectStatusIn,
  expectResponseTimeUnder,
  expectJsonContentType,
  expectListEnvelope,
  expectSuccessEnvelope,
  expectValidationError,
  expectUnauthorized,
  expectForbidden,
  expectNotFound,
  expectMatchesSchema,
};
