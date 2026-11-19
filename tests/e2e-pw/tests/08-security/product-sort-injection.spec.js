const { test, expect } = require('../../utils/fixtures');

test.describe('Product grid sort - SQL injection hardening', () => {
  const gridRequest = (adminPage, order) =>
    adminPage.request.get(
      `/admin/catalog/products?sort[column]=name&sort[order]=${encodeURIComponent(order)}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } },
    );

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
