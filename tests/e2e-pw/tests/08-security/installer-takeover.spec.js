const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

test.describe('Security: installer pre-auth admin takeover', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('AJAX-header bypass cannot reach admin-config-setup on an installed instance', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/install/api/admin-config-setup`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      data: {
        admin: 'Hacker',
        email: 'attacker@evil.com',
        password: 'pwned123',
        timezone: 'UTC',
        locale: 'en_US',
      },
      maxRedirects: 0,
    });

    expect([302, 403]).toContain(response.status());
    expect(response.status()).not.toBe(200);
  });

  test('every state-changing installer endpoint is sealed once installed', async ({ request }) => {
    const endpoints = [
      'install/api/env-file-setup',
      'install/api/run-migration',
      'install/api/run-seeder',
      'install/api/admin-config-setup',
      'install/api/seed-sample-data',
    ];

    for (const path of endpoints) {
      const response = await request.post(`${BASE_URL}/${path}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        data: {},
        maxRedirects: 0,
      });

      expect(
        [302, 403].includes(response.status()),
        `${path} should be sealed (got ${response.status()})`
      ).toBe(true);
    }
  });

  test('the legitimate admin account is unchanged after a takeover attempt', async ({ browser, request }) => {
    await request.post(`${BASE_URL}/install/api/admin-config-setup`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      data: {
        admin: 'Hacker',
        email: 'attacker@evil.com',
        password: 'pwned123',
        timezone: 'UTC',
        locale: 'en_US',
      },
      maxRedirects: 0,
    });

    const context = await browser.newContext({ storageState: undefined, baseURL: BASE_URL });
    const page = await context.newPage();

    await page.goto('/admin/login', { waitUntil: 'networkidle' });
    await page.fill('input[name=email]', 'attacker@evil.com');
    await page.fill('input[name=password]', 'pwned123');
    await page.press('input[name=password]', 'Enter');
    await page.waitForLoadState('networkidle');

    expect(page.url()).toContain('/admin/login');
    expect(page.url()).not.toContain('/admin/dashboard');

    await page.close();
    await context.close();
  });
});
