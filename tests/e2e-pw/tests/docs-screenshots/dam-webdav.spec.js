const { test } = require('@playwright/test');
const { execSync } = require('child_process');
const path = require('path');
const { capture } = require('./_helpers');
const manifest = require('./dam-webdav.manifest');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const REPO_ROOT = path.resolve(__dirname, '../../../..');

test.use({
  viewport: { width: 1920, height: 1200 },
  deviceScaleFactor: 2,
  storageState: '.state/admin-auth.json',
});

let ids = { credentialId: null, profileId: null, remoteId: null, trashId: null };

test.beforeAll(async () => {
  const out = execSync('php tests/e2e-pw/tests/docs-screenshots/seed.php', { cwd: REPO_ROOT }).toString().trim();
  ids = JSON.parse(out.split('\n').pop());
  console.log('seeded ids:', ids);
});

for (const shot of manifest) {
  if (shot.page.startsWith('__nc_flow')) continue;
  test(`shot ${shot.id}`, async ({ page }) => {
    const url = shot.page
      .replace('{credentialId}', ids.credentialId)
      .replace('{remoteId}', ids.remoteId);
    await page.goto(`${BASE}${url}`, { waitUntil: 'networkidle' });
    await capture(page, shot);
  });
}

test('shot nc-flow-login + grant + success', async ({ page, request }) => {
  const init = await request.post(`${BASE}/index.php/login/v2`, { headers: { 'User-Agent': 'docs-capture' } });
  if (!init.ok()) {
    test.skip(true, `login/v2 init failed: ${init.status()}`);
    return;
  }
  const { login: flowUrl } = await init.json();

  await page.goto(flowUrl);
  await capture(page, { id: 'nc-flow-login', target: { type: 'fullPage' } });

  const email = page.locator('input[name="email"], input[name="username"]').first();
  if (await email.count()) {
    await email.fill('admin@example.com');
    await page.locator('input[name="password"]').fill('admin123');
    await page.locator('button[type="submit"]').first().click();
    await page.waitForLoadState('networkidle');
    await capture(page, { id: 'nc-flow-grant', target: { type: 'fullPage' } });
  }
});
