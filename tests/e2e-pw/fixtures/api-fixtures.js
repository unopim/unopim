/**
 * API-test fixtures.
 *
 * Extends Playwright's `test` with:
 *   - `apiToken`   (worker-scoped) — issues one OAuth2 token per worker and
 *                  reuses it across every test in that worker. Skips all tests
 *                  in the worker if credentials are not configured.
 *   - `api`        (test-scoped) — `APIRequestContext` pre-loaded with the
 *                  Bearer token and `baseURL` pointing at `/api/v1/rest`.
 *   - `unauthedApi`(test-scoped) — same baseURL, no auth header. Used by
 *                  invalid-token / missing-token negative paths.
 *   - `uid`        (test-scoped) — random unique slug for test-data isolation.
 *   - `payloads` / `schemas` — convenience accessors so spec files don't have
 *                  to import them separately.
 */
'use strict';

const base = require('@playwright/test');
const path = require('path');
const { authenticate } = require('../utils/api/auth-helper');
const { BASE_URL, REST_ROOT, RESPONSE_TIME, SEED } = require('../utils/api/config');
const payloads = require('../payloads');
const schemas = require('../schemas');
const { uniqueCode } = require('../utils/api');

exports.test = base.test.extend({
  /* Worker-scoped: one OAuth token reused by every test in the worker. */
  apiToken: [
    async ({ playwright }, use) => {
      const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
      const token = await authenticate(ctx);
      await ctx.dispose();
      if (!token) {
        base.test.skip(true, 'API credentials are not configured; skipping worker.');
      }
      await use(token);
    },
    { scope: 'worker' },
  ],

  /**
   * Authenticated REST context — baseURL is the rest root so paths are short.
   *
   * We deliberately do NOT include `Content-Type` in `extraHTTPHeaders`:
   * Playwright sets it per-request based on the body shape (`application/json`
   * for `data:`, `multipart/form-data; boundary=…` for `multipart:`). Pinning
   * it here breaks multipart uploads — UnoPim sees a JSON content-type, tries
   * to parse the body as JSON, and 422s with "file field is required".
   */
  api: async ({ playwright, apiToken }, use) => {
    const ctx = await playwright.request.newContext({
      baseURL: REST_ROOT,
      extraHTTPHeaders: {
        Authorization: `Bearer ${apiToken}`,
        Accept: 'application/json',
      },
    });
    await use(ctx);
    await ctx.dispose();
  },

  /* Unauthenticated REST context — for 401 / token-validation cases. */
  unauthedApi: async ({ playwright }, use) => {
    const ctx = await playwright.request.newContext({
      baseURL: REST_ROOT,
      extraHTTPHeaders: { Accept: 'application/json' },
    });
    await use(ctx);
    await ctx.dispose();
  },

  uid: async ({}, use) => {
    await use(uniqueCode('uid'));
  },

  payloads: async ({}, use) => { await use(payloads); },
  schemas: async ({}, use) => { await use(schemas); },
});

exports.expect = base.expect;
exports.BASE_URL = BASE_URL;
exports.REST_ROOT = REST_ROOT;
exports.RESPONSE_TIME = RESPONSE_TIME;
exports.SEED = SEED;
exports.MEDIA_DIR = path.resolve(__dirname, '..', 'utils');
