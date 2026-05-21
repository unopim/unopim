/**
 * Centralized OAuth2 authentication for the UnoPim REST API.
 *
 * - Caches the access token in-process so a single worker reuses one token
 *   across many tests (the token has a ~3600s lifetime — far longer than any
 *   single spec needs).
 * - Falls back to UI-driven credential generation via `api-credential-setup`
 *   when no client_id/client_secret are present yet.
 * - Returns `null` (never throws) on auth failure so individual specs can
 *   skip gracefully via `test.skip(!token, '...')`.
 */
'use strict';

const { OAUTH_URL, getCredentials } = require('./config');
const { ensureApiCredentials } = require('../api-credential-setup');

/** in-memory cache keyed by client_id so different credential sets coexist. */
const tokenCache = new Map();

function basicAuth(clientId, clientSecret) {
  return `Basic ${Buffer.from(`${clientId}:${clientSecret}`).toString('base64')}`;
}

/**
 * Issue a fresh OAuth2 password-grant token. Does not consult the cache.
 * Exported for negative-path tests that need to call the token endpoint
 * directly.
 */
async function requestToken(request, { clientId, clientSecret, username, password, grantType = 'password', refreshToken } = {}) {
  const body = grantType === 'refresh_token'
    ? { grant_type: 'refresh_token', refresh_token: refreshToken }
    : { grant_type: grantType, username, password };

  return request.post(OAUTH_URL, {
    headers: {
      Authorization: basicAuth(clientId, clientSecret),
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    data: body,
  });
}

/**
 * Returns a cached or freshly minted access_token. Returns `null` (and logs
 * to stderr) on failure so tests can skip rather than blow up the run.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {object} [opts]
 * @param {boolean} [opts.forceRefresh=false] — bypass the cache.
 */
async function authenticate(request, { forceRefresh = false } = {}) {
  let creds = getCredentials();

  // First-run bootstrap: spin up a chromium session, create an integration,
  // and persist client_id/client_secret to .api-config.json.
  if (!creds.client_id || !creds.client_secret) {
    try {
      await ensureApiCredentials({
        baseUrl: (process.env.API_BASE_URL || process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, ''),
        // CI sets ADMIN_USERNAME (matches existing UI workflow); local devs may set ADMIN_EMAIL.
        adminEmail: process.env.ADMIN_EMAIL || process.env.ADMIN_USERNAME,
        adminPassword: process.env.ADMIN_PASSWORD,
        integrationName: process.env.API_INTEGRATION_NAME,
      });
      creds = getCredentials();
    } catch (err) {
      console.warn('[api/auth] credential bootstrap failed:', err.message);
      return null;
    }
  }

  if (!creds.client_id || !creds.client_secret) return null;

  const cacheKey = creds.client_id;
  if (!forceRefresh && tokenCache.has(cacheKey)) return tokenCache.get(cacheKey);

  const response = await requestToken(request, {
    clientId: creds.client_id,
    clientSecret: creds.client_secret,
    username: creds.username || process.env.ADMIN_EMAIL || 'admin@example.com',
    password: creds.password || process.env.ADMIN_PASSWORD || 'admin123',
  });

  if (!response.ok()) {
    console.error('[api/auth] token request failed:', response.status(), await response.text());
    return null;
  }

  const body = await response.json();
  if (!body.access_token) return null;
  tokenCache.set(cacheKey, body.access_token);
  return body.access_token;
}

/** Standard JSON Bearer headers for protected endpoints. */
function authHeaders(token) {
  return {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };
}

/** Multipart-friendly headers — Playwright sets Content-Type automatically when `multipart:` is used. */
function authHeadersMultipart(token) {
  return {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
  };
}

/** Drop the cached token (use after an integration is rotated/revoked). */
function clearTokenCache() {
  tokenCache.clear();
}

/**
 * Build a one-shot `APIRequestContext` pre-pointed at the REST root with a
 * caller-supplied (deliberately invalid) Bearer token. Used by the "invalid
 * token → 401" assertion in every resource spec — keeps the pattern to one
 * line at the call site.
 *
 * @returns {Promise<import('@playwright/test').APIRequestContext>}
 */
async function invalidTokenContext(playwright, token = 'invalid_token_xyz') {
  const { REST_ROOT } = require('./config');
  return playwright.request.newContext({
    baseURL: REST_ROOT,
    extraHTTPHeaders: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
}

module.exports = {
  authenticate,
  requestToken,
  authHeaders,
  authHeadersMultipart,
  basicAuth,
  clearTokenCache,
  invalidTokenContext,
};
