const { test, expect } = require('../../utils/fixtures');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

/**
 * Broken-access-control regression guard.
 *
 * An unauthenticated client must never reach an admin surface — neither the
 * HTML pages nor the datagrid JSON endpoints that back them. Both must bounce
 * to /admin/login (302) and must not return a 200 body carrying records.
 *
 * These routes span every core module so a future middleware regression on any
 * one of them fails here instead of leaking data in production.
 */
test.describe('Broken access control - guest is denied every admin surface', () => {
  const PROTECTED_PAGES = [
    '/admin/dashboard',
    '/admin/catalog/products',
    '/admin/catalog/categories',
    '/admin/catalog/attributes',
    '/admin/catalog/attribute-families',
    '/admin/settings/users',
    '/admin/settings/roles',
    '/admin/settings/channels',
    '/admin/settings/locales',
    '/admin/settings/currencies',
    '/admin/data-transfer/imports',
    '/admin/data-transfer/exports',
  ];

  for (const path of PROTECTED_PAGES) {
    test(`guest GET ${path} redirects to login`, async ({ browser }) => {
      const context = await browser.newContext({ storageState: undefined, baseURL: BASE_URL });

      // A plain HTML navigation (no AJAX header) exercises the guest's real
      // browser path, not the datagrid JSON branch the dedicated test below
      // covers.
      const res = await context.request.get(path, { maxRedirects: 0 });

      expect(res.status(), `${path} must redirect the guest`).toBe(302);
      expect(res.headers()['location'] || '').toContain('/admin/login');

      await context.close();
    });
  }

  test('guest datagrid JSON request returns no records', async ({ browser }) => {
    const context = await browser.newContext({ storageState: undefined, baseURL: BASE_URL });

    const res = await context.request.get('/admin/catalog/products', {
      maxRedirects: 0,
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    });

    expect(res.status(), 'datagrid must not serve data to a guest').not.toBe(200);

    await context.close();
  });
});
