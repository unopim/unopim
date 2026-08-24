const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const ADMIN_EMAIL = process.env.ADMIN_USERNAME || process.env.ADMIN_EMAIL || 'admin@example.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'admin123';

test.describe('Product grid sort - SQL injection hardening', () => {
  let context;
  let page;

  // Issue the datagrid request from inside the page so it runs with the live
  // authenticated session and same-origin cookies — exactly like the admin
  // datagrid's own axios call.
  async function gridRequest(order) {
    await navigateTo(page, 'products');
    await page.waitForLoadState('networkidle');

    const result = await page.evaluate(async (sortOrder) => {
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

  function assertGridJson(res) {
    expect(res.status).toBe(200);
    expect(JSON.parse(res.body)).toHaveProperty('records');
  }

  // Re-establish auth on a DEDICATED guest context, never on the shared
  // adminPage session. Posting to /admin/login regenerates the session id, which
  // invalidates the admin-auth.json snapshot that every other suite shares —
  // logging in on the shared context silently de-authenticated every later test
  // in the shard (sidebar fly-out, association types, datagrid filters, ...).
  // An isolated context keeps the shared session untouched.
  test.beforeEach(async ({ browser }) => {
    context = await browser.newContext({ storageState: { cookies: [], origins: [] }, baseURL: BASE_URL });
    page = await context.newPage();

    const loginPage = await context.request.get(`${BASE_URL}/admin/login`);
    const token = (await loginPage.text()).match(/name="_token"\s+value="([^"]+)"/)?.[1];
    await context.request.post(`${BASE_URL}/admin/login`, {
      form: { _token: token || '', email: ADMIN_EMAIL, password: ADMIN_PASSWORD },
    });
  });

  test.afterEach(async () => {
    await page.close();
    await context.close();
  });

  test('malicious sort[order] is handled safely (no SQL error)', async () => {
    const payload = 'asc,(SELECT CASE WHEN (1=1) THEN name ELSE id END FROM admins LIMIT 1)';

    const res = await gridRequest(payload);
    assertGridJson(res);
  });

  test('a normal ascending sort still works', async () => {
    const res = await gridRequest('asc');
    assertGridJson(res);
  });
});
