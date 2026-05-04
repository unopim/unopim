const { test, chromium } = require('@playwright/test');
const { execSync } = require('child_process');
const path = require('path');
const { capture } = require('./_helpers');
const manifest = require('./dam-webdav.manifest');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const REPO_ROOT = path.resolve(__dirname, '../../../..');
const ADMIN_EMAIL = process.env.DOCS_ADMIN_EMAIL || 'navneet@example.com';
const ADMIN_PASSWORD = process.env.DOCS_ADMIN_PASSWORD || 'admin123';

test.describe.configure({ mode: 'serial' });

let browser;
let ctx;
let sharedPage;
let ids = { credentialId: null, profileId: null, remoteId: null, trashId: null };

test.beforeAll(async () => {
  browser = await chromium.launch();
  ctx = await browser.newContext({
    viewport: { width: 1920, height: 1200 },
    deviceScaleFactor: 2,
    reducedMotion: 'reduce',
  });
  sharedPage = await ctx.newPage();
  await sharedPage.goto(`${BASE}/admin/login`);
  await sharedPage.getByRole('textbox', { name: 'Email Address' }).fill(ADMIN_EMAIL);
  await sharedPage.getByRole('textbox', { name: 'Password' }).fill(ADMIN_PASSWORD);
  await sharedPage.getByRole('button', { name: 'Sign In' }).click();
  await sharedPage.waitForURL(/\/admin\/(?!login)/, { timeout: 30_000 });

  const out = execSync('php tests/e2e-pw/tests/docs-screenshots/seed.php', { cwd: REPO_ROOT }).toString().trim();
  ids = JSON.parse(out.split('\n').pop());
  console.log('seeded ids:', ids);
});

test.afterAll(async () => {
  await browser?.close();
});

for (const shot of manifest) {
  if (shot.page.startsWith('__nc_flow')) continue;
  test(`shot ${shot.id}`, async () => {
    const url = shot.page
      .replace('{credentialId}', ids.credentialId)
      .replace('{remoteId}', ids.remoteId);
    await sharedPage.goto(`${BASE}${url}`, { waitUntil: 'networkidle' });
    if (sharedPage.url().includes('/admin/login')) {
      throw new Error(`Bounced to login for ${shot.id}`);
    }
    await sharedPage.waitForTimeout(800);
    await capture(sharedPage, shot);
  });
}

test('shot nc-flow-login + grant', async () => {
  const ncCtx = await browser.newContext({
    viewport: { width: 1920, height: 1200 },
    deviceScaleFactor: 2,
    reducedMotion: 'reduce',
  });
  const page = await ncCtx.newPage();
  const init = await ncCtx.request.post(`${BASE}/index.php/login/v2`, { headers: { 'User-Agent': 'docs-capture' } });
  if (!init.ok()) {
    await ncCtx.close();
    test.skip(true, `login/v2 init failed: ${init.status()}`);
    return;
  }
  const { login: flowUrl } = await init.json();

  await page.goto(flowUrl);
  await capture(page, { id: 'nc-flow-login', target: { type: 'fullPage' } });

  const email = page.locator('input[name="email"], input[name="username"]').first();
  if (await email.count()) {
    await email.fill(ADMIN_EMAIL);
    await page.locator('input[name="password"]').fill(ADMIN_PASSWORD);
    await page.locator('button[type="submit"]').first().click();
    await page.waitForLoadState('networkidle').catch(() => {});
    await capture(page, { id: 'nc-flow-grant', target: { type: 'fullPage' } });
  }
  await ncCtx.close();
});
