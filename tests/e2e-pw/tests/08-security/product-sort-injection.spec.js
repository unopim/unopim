const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('Product grid sort - SQL injection hardening', () => {
  // Issue the datagrid request from inside the page so it runs with the live
  // authenticated session and same-origin cookies — exactly like the admin
  // datagrid's own axios call. `adminPage.request` uses the storageState
  // cookie snapshot, which can be stale and get bounced to the login HTML.
  async function gridRequest(adminPage, order) {
    await navigateTo(adminPage, 'products');
    await adminPage.waitForLoadState('networkidle');

    const result = await adminPage.evaluate(async (sortOrder) => {
      const url = `/admin/catalog/products?sort[column]=name&sort[order]=${encodeURIComponent(sortOrder)}`;
      const response = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        credentials: 'same-origin',
      });
      return { status: response.status, url: response.url, body: await response.text() };
    }, order);

    if (result.status !== 200 || !result.body.trim().startsWith('{')) {
      console.log('[product-sort] unexpected grid response:', result.status, result.url, result.body.slice(0, 300));
    }

    return result;
  }

  test('malicious sort[order] is handled safely (no SQL error)', async ({ adminPage }) => {
    const payload = 'asc,(SELECT CASE WHEN (1=1) THEN name ELSE id END FROM admins LIMIT 1)';

    const res = await gridRequest(adminPage, payload);

    expect(res.status, 'grid must not 500 on a malicious sort order').toBe(200);
    expect(JSON.parse(res.body)).toHaveProperty('records');
  });

  test('a normal ascending sort still works', async ({ adminPage }) => {
    const res = await gridRequest(adminPage, 'asc');

    expect(res.status).toBe(200);
    expect(JSON.parse(res.body)).toHaveProperty('records');
  });
});
