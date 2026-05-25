const { test, expect } = require('../../utils/fixtures');
const { spawnSync } = require('child_process');
const path = require('path');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const APP_ROOT = path.resolve(__dirname, '../../../..');

/**
 * Seed/cleanup a low-priv admin via artisan tinker (DB direct).
 * spawnSync (no shell) avoids escape-hell with multi-line PHP.
 */
function tinker(php) {
  const r = spawnSync('php', ['artisan', 'tinker', '--execute', php], {
    cwd: APP_ROOT,
    encoding: 'utf8',
  });
  if (r.status !== 0) {
    throw new Error(`tinker failed: ${r.stdout || ''}${r.stderr || ''}`);
  }
  return (r.stdout || '').trim();
}

function seedLowPrivAdmin(email, password) {
  const phpSeed = [
    `$role = \\Webkul\\User\\Models\\RoleProxy::create(['name' => 'acl-bypass-${Date.now()}', 'description' => 'ACL bypass regression role', 'permission_type' => 'custom', 'permissions' => ['dashboard']]);`,
    `$admin = \\Webkul\\User\\Models\\AdminProxy::create(['name' => 'ACL Bypass User', 'email' => ${JSON.stringify(email)}, 'password' => \\Illuminate\\Support\\Facades\\Hash::make(${JSON.stringify(password)}), 'status' => 1, 'role_id' => $role->id, 'timezone' => 'UTC', 'ui_locale_id' => \\Webkul\\Core\\Models\\LocaleProxy::where('code', 'en_US')->value('id')]);`,
    `echo $admin->id . ':' . $role->id;`,
  ].join(' ');

  const out = tinker(phpSeed);
  const m = out.match(/(\d+):(\d+)/);
  if (!m) throw new Error('Seed failed: ' + out);
  return { adminId: Number(m[1]), roleId: Number(m[2]) };
}

function cleanupLowPrivAdmin(adminId, roleId) {
  const php = [
    `\\Webkul\\User\\Models\\AdminProxy::where('id', ${adminId})->delete();`,
    `\\Webkul\\User\\Models\\RoleProxy::where('id', ${roleId})->delete();`,
    `echo 'ok';`,
  ].join(' ');
  tinker(php);
}

async function loginAs(page, email, password) {
  await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle' });
  await page.getByRole('textbox', { name: 'Email Address' }).fill(email);
  await page.getByRole('textbox', { name: 'Password' }).fill(password);
  await Promise.all([
    page.waitForResponse(r => r.url().includes('/admin/login') && r.request().method() === 'POST', { timeout: 15000 }).catch(() => null),
    page.getByRole('button', { name: 'Sign In' }).click(),
  ]);
  await page.waitForLoadState('networkidle').catch(() => {});
}

async function attemptWrite(page, urlPath, method) {
  return page.evaluate(async ({ baseURL, urlPath, method }) => {
    const token = document.querySelector('input[name="_token"]')?.value ?? '';
    const body = new URLSearchParams({ _token: token, _method: method });
    const res = await fetch(`${baseURL}${urlPath}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      redirect: 'manual',
      body: body.toString(),
    });
    return { status: res.status };
  }, { baseURL: BASE_URL, urlPath, method });
}

test.describe('ACL write-route bypass regression (CWE-862)', () => {
  test('Low-priv user gets 403 on all write-verb admin routes', async ({ browser }) => {
    test.setTimeout(180_000);

    const email = `acl-bypass-${Date.now()}@example.com`;
    const password = 'Test@12345';

    const seeded = seedLowPrivAdmin(email, password);

    const ctx = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await ctx.newPage();

    try {
      await loginAs(page, email, password);
      expect(page.url(), 'Low-priv user should be authenticated after login').not.toMatch(/\/admin\/login/);

      await page.goto(`${BASE_URL}/admin/dashboard`, { waitUntil: 'networkidle' });

      const cases = [
        { url: '/admin/catalog/products/edit/1',                    method: 'PUT' },
        { url: '/admin/catalog/categories/create',                  method: 'POST' },
        { url: '/admin/catalog/categories/edit/1',                  method: 'PUT' },
        { url: '/admin/catalog/category-fields/create',             method: 'POST' },
        { url: '/admin/catalog/category-fields/edit/1',             method: 'PUT' },
        { url: '/admin/catalog/attributes/create',                  method: 'POST' },
        { url: '/admin/catalog/attributes/edit/1',                  method: 'PUT' },
        { url: '/admin/catalog/attributegroups/create',             method: 'POST' },
        { url: '/admin/catalog/attributegroups/edit/1',             method: 'PUT' },
        { url: '/admin/catalog/families/create',                    method: 'POST' },
        { url: '/admin/catalog/families/edit/1',                    method: 'PUT' },
        { url: '/admin/settings/channels/create',                   method: 'POST' },
        { url: '/admin/settings/channels/edit/1',                   method: 'PUT' },
        { url: '/admin/settings/currencies/create',                 method: 'POST' },
        { url: '/admin/settings/currencies/edit',                   method: 'PUT' },
        { url: '/admin/settings/locales/edit',                      method: 'PUT' },
        { url: '/admin/settings/data-transfer/imports/create',      method: 'POST' },
        { url: '/admin/settings/data-transfer/imports/edit/1',      method: 'PUT' },
        { url: '/admin/settings/data-transfer/exports/create',      method: 'POST' },
        { url: '/admin/settings/data-transfer/exports/edit/1',      method: 'PUT' },
      ];

      const leaks = [];
      for (const c of cases) {
        const { status } = await attemptWrite(page, c.url, c.method);
        if (status !== 403) leaks.push(`${c.method} ${c.url} → ${status}`);
      }

      expect(
        leaks,
        `Routes leaked past ACL (expected 403):\n  ${leaks.join('\n  ')}`
      ).toEqual([]);
    } finally {
      await page.close();
      await ctx.close();
      if (seeded) {
        try { cleanupLowPrivAdmin(seeded.adminId, seeded.roleId); } catch (_) {}
      }
    }
  });
});
