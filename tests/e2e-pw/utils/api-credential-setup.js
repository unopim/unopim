/**
 * First-run bootstrap of UnoPim API integration credentials.
 *
 * Flow:
 *   1. Server-to-server admin login via `request.newContext` (works around
 *      UnoPim 2.x's Vue "Sign In" button which doesn't reliably fire when
 *      clicked in headless chromium).
 *   2. Pin session-cookie expiry so chromium doesn't drop "session" cookies
 *      (expires=-1) as already-expired on reload.
 *   3. Open chromium with that storage state, navigate directly to
 *      `/admin/configuration/integrations/create`, create a new integration.
 *   4. If the admin user already owns an integration (UnoPim enforces
 *      one-integration-per-admin), fall through to reading the existing
 *      integration's credentials at `/admin/configuration/integrations/edit/{id}`
 *      and re-generating its secret if missing.
 *
 * The chromium leg is needed only for the integration form, which posts
 * via Vue/axios; there's no equivalent plain-HTML form endpoint to hit.
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { chromium, request } = require('@playwright/test');

const CONFIG_PATH = path.resolve(__dirname, '../.api-config.json');

function writeConfig(config) {
  fs.writeFileSync(CONFIG_PATH, JSON.stringify(config, null, 2), 'utf8');
}

/**
 * Login server-to-server and return a Playwright storage-state object the
 * caller can persist or pass directly to `browser.newContext({ storageState })`.
 */
async function adminLoginStorageState({ baseUrl, adminEmail, adminPassword }) {
  const ctx = await request.newContext({ baseURL: baseUrl });
  try {
    const loginPage = await ctx.get('/admin/login');
    if (!loginPage.ok()) {
      throw new Error(`GET /admin/login → ${loginPage.status()}`);
    }
    const m = (await loginPage.text()).match(/name="_token"\s+value="([^"]+)"/);
    if (!m) throw new Error('Could not find CSRF _token on /admin/login');

    const resp = await ctx.post('/admin/login', {
      form: { _token: m[1], email: adminEmail, password: adminPassword },
    });
    if (resp.url().includes('/login')) {
      throw new Error(`Login POST landed back on /login (status ${resp.status()}) — bad credentials?`);
    }
    const dashboard = await ctx.get('/admin/dashboard');
    if (!dashboard.ok() || dashboard.url().includes('/login')) {
      throw new Error(`Dashboard probe after login → ${dashboard.status()} ${dashboard.url()}`);
    }
    const state = await ctx.storageState();

    // Pin session-cookie expiry — chromium drops -1 cookies as expired on reload.
    const oneDay = Math.floor(Date.now() / 1000) + 24 * 60 * 60;
    state.cookies = (state.cookies || []).map((c) =>
      (c.expires === -1 || c.expires === undefined) ? { ...c, expires: oneDay } : c,
    );
    return state;
  } finally {
    await ctx.dispose();
  }
}

/**
 * Read the credentials displayed on an integration's edit page. If the secret
 * has been hidden / never generated, click "Re-Generate Secret Key" first.
 *
 * @returns {Promise<{ client_id: string, client_secret: string } | null>}
 */
async function readCredentialsOnEditPage(page) {
  // Re-Generate Secret Key is the only reliable button — clicking it mints
  // a fresh client_id+secret pair and reveals both inputs populated.
  const regen = page.locator('button').filter({ hasText: /re-?generate/i }).first();
  if (await regen.isVisible({ timeout: 3000 }).catch(() => false)) {
    const respPromise = page.waitForResponse(
      (r) => r.request().method() === 'POST',
      { timeout: 15000 },
    ).catch(() => null);
    await regen.click();
    // If a confirm dialog appears, accept it.
    const confirm = page.locator('button:has-text("Yes"), button:has-text("Confirm"), button:has-text("OK")').first();
    if (await confirm.isVisible({ timeout: 2000 }).catch(() => false)) await confirm.click();
    await respPromise;
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  }

  const cid = await page.locator('#client_id').inputValue().catch(() => '');
  const sec = await page.locator('#secret_key').inputValue().catch(() => '');
  if (cid && sec) return { client_id: cid, client_secret: sec };
  return null;
}

/**
 * Open the integrations list and navigate into the first existing integration.
 * Returns its credentials, regenerating the secret if necessary.
 */
async function reuseExistingIntegration(page, baseUrl) {
  await page.goto(`${baseUrl}/admin/configuration/integrations`, { waitUntil: 'networkidle', timeout: 30000 });
  const editIcon = page.locator('[title="Edit"], .icon-edit').first();
  if (!await editIcon.isVisible({ timeout: 5000 }).catch(() => false)) return null;
  await editIcon.click();
  await page.waitForURL(/\/admin\/configuration\/integrations\/edit\//, { timeout: 15000 });
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  return readCredentialsOnEditPage(page);
}

/**
 * Try to create a new integration. Returns its credentials on success, or
 * `null` if creation failed (typically because the admin user already owns
 * one — UnoPim enforces unique admin_id on integrations).
 */
async function tryCreateNewIntegration(page, baseUrl, integrationName) {
  await page.goto(`${baseUrl}/admin/configuration/integrations/create`, { waitUntil: 'networkidle', timeout: 30000 });
  await page.locator('input[name="name"]').first().waitFor({ state: 'visible', timeout: 10000 });
  await page.locator('input[name="name"]').first().fill(integrationName);

  // Vue multiselect — click container, wait for options to render, pick first.
  await page.locator('#admin_id .multiselect').click();
  await page.locator('#admin_id .multiselect__element').first().waitFor({ state: 'visible', timeout: 5000 });
  await page.locator('#admin_id .multiselect__element').first().click();

  const postPromise = page.waitForResponse(
    (r) => r.request().method() === 'POST' && r.url().includes('configuration/integrations'),
    { timeout: 15000 },
  ).catch(() => null);
  await page.locator('button:has-text("Save")').first().click();
  const post = await postPromise;
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

  // 302 + still on /create means validation failure (almost always
  // "admin id has already been taken" on UnoPim).
  if (!post || !/\/edit\//.test(page.url())) return null;
  return readCredentialsOnEditPage(page);
}

async function createApiIntegration({ baseUrl, adminEmail, adminPassword, integrationName }) {
  // 1) Server-to-server login → storage state
  const state = await adminLoginStorageState({ baseUrl, adminEmail, adminPassword });
  const stateFile = path.resolve(__dirname, '../.state/bootstrap-auth.json');
  fs.mkdirSync(path.dirname(stateFile), { recursive: true });
  fs.writeFileSync(stateFile, JSON.stringify(state, null, 2));

  // 2) chromium with the storage state — only used for the Vue integration form
  const browser = await chromium.launch({
    headless: true,
    args: ['--disable-gpu', '--disable-dev-shm-usage', '--no-sandbox'],
  });
  try {
    const context = await browser.newContext({ storageState: stateFile });
    const page = await context.newPage();

    // Try to create a fresh integration first
    let creds = await tryCreateNewIntegration(page, baseUrl, integrationName);

    // Fall back to reusing the existing one
    if (!creds) {
      creds = await reuseExistingIntegration(page, baseUrl);
    }

    if (!creds) {
      throw new Error(
        'Could not obtain API credentials. Try creating an integration manually at ' +
        `${baseUrl}/admin/configuration/integrations then paste client_id + client_secret into .api-config.json.`,
      );
    }

    return {
      client_id: creds.client_id,
      client_secret: creds.client_secret,
      username: adminEmail,
      password: adminPassword,
      savedAt: new Date().toISOString(),
      integrationName,
    };
  } finally {
    await browser.close();
  }
}

async function ensureApiCredentials({ baseUrl, adminEmail, adminPassword, integrationName }) {
  const existing = require('./api-config').getCredentials();
  if (existing.client_id && existing.client_secret) return existing;

  const missing = [];
  if (!adminEmail && !existing.username) missing.push('admin email');
  if (!adminPassword && !existing.password) missing.push('admin password');
  if (missing.length > 0) {
    throw new Error(
      `Unable to generate API credentials automatically. Missing: ${missing.join(', ')}. ` +
      'Set ADMIN_EMAIL / ADMIN_PASSWORD env vars or seed .api-config.json.',
    );
  }

  const credentials = await createApiIntegration({
    baseUrl,
    adminEmail,
    adminPassword,
    integrationName: integrationName || 'UnoPim API Test Integration',
  });
  writeConfig(credentials);
  return credentials;
}

module.exports = { ensureApiCredentials, adminLoginStorageState };
