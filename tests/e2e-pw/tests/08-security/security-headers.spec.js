const { test, expect } = require('../../utils/fixtures');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

/**
 * Response-header hardening guard.
 *
 * Locks in the clickjacking / MIME-sniffing / referrer-leak protections the
 * app already ships, so a later middleware change cannot silently drop them.
 * Asserted on an unauthenticated page because these headers must apply to the
 * whole admin surface, login screen included.
 */
test.describe('Security headers on admin responses', () => {
  let headers;

  test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext({ storageState: undefined, baseURL: BASE_URL });
    const res = await context.request.get('/admin/login');
    headers = res.headers();
    await context.close();
  });

  test('X-Frame-Options prevents framing (clickjacking)', () => {
    expect((headers['x-frame-options'] || '').toUpperCase()).toMatch(/DENY|SAMEORIGIN/);
  });

  test('X-Content-Type-Options stops MIME sniffing', () => {
    expect(headers['x-content-type-options']).toBe('nosniff');
  });

  test('Referrer-Policy is set', () => {
    expect(headers['referrer-policy']).toBeTruthy();
  });

  test('Permissions-Policy locks down powerful features', () => {
    expect(headers['permissions-policy']).toBeTruthy();
  });
});
