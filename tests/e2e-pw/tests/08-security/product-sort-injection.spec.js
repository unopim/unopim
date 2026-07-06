const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('Product grid sort - SQL injection hardening', () => {
  // Mirror the working datagrid request in productGridFilter.spec.js: navigate
  // to the grid first so the request resolves against the authenticated app
  // origin, then build an absolute URL. A bare relative URL with no prior
  // navigation resolves against about:blank and returns the login HTML.
  async function gridRequest(adminPage, order) {
    await navigateTo(adminPage, 'products');
    await adminPage.waitForLoadState('networkidle');

    const url = new URL('/admin/catalog/products', adminPage.url());
    url.searchParams.set('sort[column]', 'name');
    url.searchParams.set('sort[order]', order);

    return adminPage.request.get(url.toString(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest', accept: 'application/json' },
    });
  }

  test('malicious sort[order] is handled safely (no SQL error)', async ({ adminPage }) => {
    const payload = 'asc,(SELECT CASE WHEN (1=1) THEN name ELSE id END FROM admins LIMIT 1)';

    const res = await gridRequest(adminPage, payload);

    expect(res.status(), 'grid must not 500 on a malicious sort order').toBe(200);
    expect(await res.json()).toHaveProperty('records');
  });

  test('a normal ascending sort still works', async ({ adminPage }) => {
    const res = await gridRequest(adminPage, 'asc');

    expect(res.status()).toBe(200);
    expect(await res.json()).toHaveProperty('records');
  });
});
