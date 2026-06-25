// utils/login.js

export async function login(page) {
  const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8000';
  const email = process.env.ADMIN_USERNAME || process.env.ADMIN_EMAIL || 'admin@example.com';
  const password = process.env.ADMIN_PASSWORD || 'admin123';

  const html = await (await page.request.get(`${baseURL}/admin/login`)).text();
  const match = html.match(/name="_token"\s+value="([^"]+)"/);
  const token = match ? match[1] : '';

  await page.request.post(`${baseURL}/admin/login`, {
    form: { _token: token, email, password },
  });

  await page.goto(`${baseURL}/admin/dashboard`);
  await page.waitForLoadState('networkidle');
}
