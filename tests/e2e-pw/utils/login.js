// utils/login.js

export async function login(page) {
  const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8000';
  const email = process.env.ADMIN_USERNAME || process.env.ADMIN_EMAIL || 'admin@example.com';
  const password = process.env.ADMIN_PASSWORD || 'admin123';
  await page.goto(`${baseURL}/admin/login`);
  await page.getByRole('textbox', { name: 'Email Address' }).fill(email);
  await page.getByRole('textbox', { name: 'Password' }).fill(password);
  await page.getByRole('button', { name: 'Sign In' }).click();

  // Wait for the post-login redirect off /admin/login. `networkidle` is unreliable
  // on a dev server where Debugbar/Livewire keep polling, so key off the URL and
  // fall back to DOM-ready rather than an idle network.
  await page
    .waitForURL((url) => !url.pathname.endsWith('/admin/login'), { timeout: 30000 })
    .catch(() => {});
  await page.waitForLoadState('domcontentloaded').catch(() => {});
}
