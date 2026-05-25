/**
 * OAuth Authentication — `POST /oauth/token`
 *
 * The OAuth endpoint sits OUTSIDE `/api/v1/rest`, so this file uses raw
 * `playwright.request` contexts rather than the `api` fixture (which is
 * pre-pointed at the REST root). Every other module file does the opposite.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { OAUTH_URL, REST_ROOT, BASE_URL, getCredentials, RESPONSE_TIME } = require('../../../utils/api/config');
const { basicAuth, requestToken } = require('../../../utils/api/auth-helper');
const { post, get } = require('../../../utils/api/request-wrapper');
const {
  expectOk,
  expectStatus,
  expectResponseTimeUnder,
  expectJsonContentType,
  expectMatchesSchema,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');

let creds = getCredentials();

test.describe('OAuth Authentication API', () => {
  test.beforeEach(async ({ apiToken }) => {
    if (!apiToken) test.skip(true, 'API credentials not configured — bootstrap failed.');
    creds = getCredentials();
  });

  // ── Positive ─────────────────────────────────────────────────────────────

  test('1.1 - POST /oauth/token returns 200 with valid credentials', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const response = await ctx.post(OAUTH_URL, {
      headers: {
        Authorization: basicAuth(creds.client_id, creds.client_secret),
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      data: { grant_type: 'password', username: creds.username, password: creds.password },
    });
    expect(response.status()).toBe(200);
    await ctx.dispose();
  });

  test('1.2 - Token response matches OAuth2 schema', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const startedAt = Date.now();
    const response = await requestToken(ctx, {
      clientId: creds.client_id,
      clientSecret: creds.client_secret,
      username: creds.username,
      password: creds.password,
    });
    const durationMs = Date.now() - startedAt;
    expect(response.ok()).toBe(true);
    const body = await response.json();
    expectMatchesSchema(schemas.oauthToken, body, 'oauthToken');
    expect(durationMs).toBeLessThanOrEqual(RESPONSE_TIME.upload);
    await ctx.dispose();
  });

  test('1.3 - Content-Type of token response is JSON', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const response = await requestToken(ctx, {
      clientId: creds.client_id, clientSecret: creds.client_secret,
      username: creds.username, password: creds.password,
    });
    expectJsonContentType(response.headers());
    await ctx.dispose();
  });

  test('1.4 - Token is reusable on a protected endpoint', async ({ apiToken, playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const result = await get(ctx, `${REST_ROOT}/locales`, { token: apiToken });
    expectOk(result);
    expectResponseTimeUnder(result, RESPONSE_TIME.list);
    await ctx.dispose();
  });

  test('1.5 - Two concurrent token requests both succeed', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const [r1, r2] = await Promise.all([
      requestToken(ctx, { clientId: creds.client_id, clientSecret: creds.client_secret, username: creds.username, password: creds.password }),
      requestToken(ctx, { clientId: creds.client_id, clientSecret: creds.client_secret, username: creds.username, password: creds.password }),
    ]);
    expect(r1.ok()).toBe(true);
    expect(r2.ok()).toBe(true);
    await ctx.dispose();
  });

  // ── Negative ─────────────────────────────────────────────────────────────

  test('1.6 - Wrong password returns 4xx', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const response = await requestToken(ctx, {
      clientId: creds.client_id, clientSecret: creds.client_secret,
      username: creds.username, password: 'wrong_password_xyz',
    });
    expect(response.status()).toBeGreaterThanOrEqual(400);
    await ctx.dispose();
  });

  test('1.7 - Invalid client_id/secret returns 4xx', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const response = await requestToken(ctx, {
      clientId: 'invalid_client', clientSecret: 'invalid_secret',
      username: creds.username, password: creds.password,
    });
    expect(response.status()).toBeGreaterThanOrEqual(400);
    await ctx.dispose();
  });

  test('1.8 - Wrong username returns 4xx', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const response = await requestToken(ctx, {
      clientId: creds.client_id, clientSecret: creds.client_secret,
      username: 'nonexistent@invalid.com', password: creds.password,
    });
    expect(response.status()).toBeGreaterThanOrEqual(400);
    await ctx.dispose();
  });

  test('1.9 - Unsupported grant_type returns 4xx', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const response = await requestToken(ctx, {
      clientId: creds.client_id, clientSecret: creds.client_secret,
      username: creds.username, password: creds.password, grantType: 'implicit',
    });
    expect(response.status()).toBeGreaterThanOrEqual(400);
    await ctx.dispose();
  });

  test('1.10 - Missing grant_type returns 4xx', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const response = await ctx.post(OAUTH_URL, {
      headers: {
        Authorization: basicAuth(creds.client_id, creds.client_secret),
        'Content-Type': 'application/json', Accept: 'application/json',
      },
      data: { username: creds.username, password: creds.password },
    });
    expect(response.status()).toBeGreaterThanOrEqual(400);
    await ctx.dispose();
  });

  test('1.11 - Empty body returns 4xx', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const response = await ctx.post(OAUTH_URL, {
      headers: {
        Authorization: basicAuth(creds.client_id, creds.client_secret),
        'Content-Type': 'application/json', Accept: 'application/json',
      },
      data: {},
    });
    expect(response.status()).toBeGreaterThanOrEqual(400);
    await ctx.dispose();
  });

  // ── Authorization checks on protected endpoint ───────────────────────────

  test('1.12 - Bearer token missing → 401', async ({ unauthedApi }) => {
    const result = await get(unauthedApi, '/locales');
    expectStatus(result, 401);
  });

  test('1.13 - Invalid Bearer token → 401', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    const result = await get(ctx, '/locales', { token: 'totally_invalid_xyz' });
    expectStatus(result, 401);
    await ctx.dispose();
  });

  test('1.14 - Empty Bearer value → 4xx', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    const response = await ctx.get('/locales', {
      headers: { Authorization: 'Bearer ', Accept: 'application/json' },
    });
    expect(response.status()).toBeGreaterThanOrEqual(400);
    expect(response.status()).toBeLessThan(500);
    await ctx.dispose();
  });

  test('1.15 - Wrong Authorization scheme → 4xx', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    const response = await ctx.get('/locales', {
      headers: { Authorization: 'Token irrelevant', Accept: 'application/json' },
    });
    expect(response.status()).toBeGreaterThanOrEqual(400);
    await ctx.dispose();
  });

  // ── Response-time SLA ────────────────────────────────────────────────────

  test('1.16 - Token endpoint responds within budget', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: BASE_URL });
    const startedAt = Date.now();
    await requestToken(ctx, {
      clientId: creds.client_id, clientSecret: creds.client_secret,
      username: creds.username, password: creds.password,
    });
    expect(Date.now() - startedAt).toBeLessThanOrEqual(RESPONSE_TIME.upload);
    await ctx.dispose();
  });
});
