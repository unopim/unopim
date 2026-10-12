const { test, expect } = require('../../utils/fixtures');

test.describe('Product grid sort - SQL injection hardening', () => {
  const gridRequest = (adminPage, order) =>
    adminPage.request.get(
      `/admin/catalog/products?sort[column]=name&sort[order]=${encodeURIComponent(order)}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } },
    );

  // Earlier logout specs in the same shard can invalidate the shared admin
  // session, which makes the raw datagrid request silently redirect to the HTML
  // login page (breaking res.json()). Re-establish auth deterministically with a
  // server-side form login on this context's request jar — the same jar the grid
  // request below uses — avoiding flaky UI/AJAX-redirect re-login.
  test.beforeEach(async ({ adminPage }) => {
    const loginPage = await adminPage.request.get('/admin/login');
    const token = (await loginPage.text()).match(/name="_token"\s+value="([^"]+)"/)?.[1];
    await adminPage.request.post('/admin/login', {
      form: { _token: token || '', email: 'admin@example.com', password: 'admin123' },
    });
  });

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
