const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

test.describe('Security: installer sealed once installed', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('admin-config-setup is sealed even with the X-Requested-With (AJAX) header', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/install/api/admin-config-setup`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      data: {
        admin: 'Probe',
        email: 'probe@example.test',
        password: 'probe-password',
        timezone: 'UTC',
        locale: 'en_US',
      },
      maxRedirects: 0,
    });

    expect(
      [302, 403].includes(response.status()),
      `admin-config-setup should be sealed (got ${response.status()})`
    ).toBe(true);
  });

  test('every state-changing installer endpoint is sealed', async ({ request }) => {
    const endpoints = [
      'install/api/env-file-setup',
      'install/api/run-migration',
      'install/api/run-seeder',
      'install/api/admin-config-setup',
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
});
