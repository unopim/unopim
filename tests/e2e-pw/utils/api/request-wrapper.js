/**
 * Thin wrapper around `APIRequestContext` that:
 *   - adds Bearer auth automatically
 *   - measures wall-clock response time and exposes it on the returned object
 *   - normalizes return shape to `{ response, body, durationMs, ok, status }`
 *
 * Every helper is `null`-safe on `body` parsing so a negative test asserting a
 * 401 still gets back a usable object instead of throwing on `.json()`.
 */
'use strict';

const { REST_ROOT } = require('./config');
const { authHeaders, authHeadersMultipart } = require('./auth-helper');

/** Read the body once, trying JSON first, then text — never throws. */
async function safeBody(response) {
  const text = await response.text().catch(() => '');
  if (!text) return null;
  try { return JSON.parse(text); } catch { return text; }
}

function buildUrl(pathOrUrl) {
  if (/^https?:\/\//i.test(pathOrUrl)) return pathOrUrl;
  return `${REST_ROOT}${pathOrUrl.startsWith('/') ? '' : '/'}${pathOrUrl}`;
}

/**
 * Execute a verb against the REST root.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {'get'|'post'|'put'|'patch'|'delete'} method
 * @param {string} pathOrUrl   — path under /api/v1/rest, or absolute URL
 * @param {object} [opts]
 * @param {string|null} [opts.token] — Bearer token (null = no auth)
 * @param {object} [opts.headers] — extra headers (merged last)
 * @param {object} [opts.params] — query string params
 * @param {object} [opts.data]   — JSON body
 * @param {object} [opts.multipart] — multipart form-data
 * @param {number} [opts.timeout=30_000]
 */
async function callApi(request, method, pathOrUrl, opts = {}) {
  const {
    token,
    headers = {},
    params,
    data,
    multipart,
    timeout = 30_000,
  } = opts;

  const baseHeaders = token
    ? (multipart ? authHeadersMultipart(token) : authHeaders(token))
    : { Accept: 'application/json' };

  const url = buildUrl(pathOrUrl);
  const init = {
    headers: { ...baseHeaders, ...headers },
    timeout,
  };
  if (params) init.params = params;
  if (data !== undefined && !multipart) init.data = data;
  if (multipart) init.multipart = multipart;

  const startedAt = Date.now();
  const response = await request[method](url, init);
  const durationMs = Date.now() - startedAt;
  const body = await safeBody(response);

  return {
    response,
    body,
    durationMs,
    ok: response.ok(),
    status: response.status(),
    headers: response.headers(),
    url,
    method,
  };
}

/** Convenience verb wrappers. */
const get = (request, path, opts) => callApi(request, 'get', path, opts);
const post = (request, path, opts) => callApi(request, 'post', path, opts);
const put = (request, path, opts) => callApi(request, 'put', path, opts);
const patch = (request, path, opts) => callApi(request, 'patch', path, opts);
const del = (request, path, opts) => callApi(request, 'delete', path, opts);

/**
 * Best-effort DELETE that silently absorbs 4xx/5xx. Use in `afterAll`
 * cleanup hooks so a missing/changed resource doesn't fail the run.
 */
async function deleteIfExists(request, token, path) {
  try {
    await del(request, path, { token });
  } catch (_) {
    /* ignore */
  }
}

module.exports = {
  callApi,
  get,
  post,
  put,
  patch,
  delete: del,
  deleteIfExists,
  buildUrl,
  safeBody,
};
