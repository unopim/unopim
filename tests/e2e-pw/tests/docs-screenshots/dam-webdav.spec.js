const { test } = require('@playwright/test');
const { capture, ensureCredential, ensureProfile, ensureRemoteSource } = require('./_helpers');
const manifest = require('./dam-webdav.manifest');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';

test.use({
  viewport: { width: 1920, height: 1200 },
  deviceScaleFactor: 2,
  storageState: 'tests/e2e-pw/.state/admin-auth.json',
});

let credentialId;
let remoteId;

test.beforeAll(async ({ request }) => {
  credentialId = await ensureCredential(request);
  await ensureProfile(request, credentialId);
  await ensureRemoteSource(request);
  const list = await request.get(`${BASE}/admin/nextcloud/remote-sources`);
  const m = (await list.text()).match(/remote-sources\/(\d+)\/edit/);
  remoteId = m ? Number(m[1]) : null;
});

for (const shot of manifest) {
  if (shot.page.startsWith('__nc_flow')) continue;
  test(`shot ${shot.id}`, async ({ page }) => {
    const url = shot.page
      .replace('{credentialId}', credentialId)
      .replace('{remoteId}', remoteId);
    await page.goto(`${BASE}${url}`);
    await capture(page, shot);
  });
}

test('shot nc-flow-login + grant + success', async ({ page, request }) => {
  const init = await request.post(`${BASE}/index.php/login/v2`, { headers: { 'User-Agent': 'docs-capture' } });
  const { login: flowUrl } = await init.json();

  await page.goto(flowUrl);
  await capture(page, { id: 'nc-flow-login', target: { type: 'fullPage' } });

  await page.locator('input[name="email"], input[name="username"]').first().fill('admin@example.com');
  await page.locator('input[name="password"]').fill('admin123');
  await page.locator('button[type="submit"]').click();
  await page.waitForLoadState('networkidle');
  await capture(page, { id: 'nc-flow-grant', target: { type: 'fullPage' } });

  await page.locator('button[type="submit"], form button').last().click();
  await page.waitForLoadState('networkidle');
  await capture(page, { id: 'nc-flow-success', target: { type: 'fullPage' } });
});
