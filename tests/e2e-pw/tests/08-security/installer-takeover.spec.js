const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

/**
 * Installer must stay sealed on a fully installed instance.
 *
 * On an installed instance the `/install` routes and their state-changing API
 * endpoints must never run again. The `CanInstall` middleware redirects every
 * `/install` request once installation is complete (the `storage/installed`
 * marker is written at the end of both the web and CLI install flows), and the
 * controller adds a defence-in-depth guard. These checks run fully
 * unauthenticated and assert the seal via status code only, so they are
 * non-destructive: a sealed endpoint never executes the payload.
 */
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
});
