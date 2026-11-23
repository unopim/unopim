const { test, expect } = require('../../utils/fixtures');
const { fillSecret } = require('../../utils/secret-helpers');
const { generateUid } = require('../../utils/helpers');
const path = require('path');

const ADMIN_STATE_PATH = path.resolve(__dirname, '../../', process.env.PW_STATE_DIR || '.state', 'admin-auth.json');

/**
 * Session and login hardening: logout ends the session, a password change ends
 * the other sessions, the post-login redirect stays on this origin, and the
 * login form is usable from the keyboard and by screen readers.
 */
async function guestPage(browser, options = {}) {
  const context = await browser.newContext({ storageState: { cookies: [], origins: [] }, ...options });
  const page = await context.newPage();

  return { context, page };
}

async function signIn(page, email, password) {
  await page.goto('/admin/login', { waitUntil: 'networkidle', timeout: 30000 });
  await page.getByRole('textbox', { name: 'Email Address' }).fill(email);
  await fillSecret(page.getByRole('textbox', { name: 'Password' }), password);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.waitForURL((url) => !url.pathname.endsWith('/admin/login'), { timeout: 30000 });
}

async function adminRequest(adminPage, method, url, fields) {
  return adminPage.evaluate(async ({ method, url, fields }) => {
    const token = document.querySelector('input[name="_token"]')?.value ?? '';
    const body = new URLSearchParams({ _token: token, _method: method, ...fields });
    const response = await fetch(url, {
      method: 'POST',
      body,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });

    return { status: response.status, json: await response.json().catch(() => ({})) };
  }, { method, url, fields });
}

test.describe('Login page — keyboard and screen reader access', () => {
  test('links the email and password labels to their fields', async ({ browser }) => {
    const { context, page } = await guestPage(browser);
    await page.goto('/admin/login', { waitUntil: 'networkidle' });

    expect(await page.evaluate(() => document.getElementById('email').labels.length)).toBe(1);
    expect(await page.evaluate(() => document.getElementById('password').labels.length)).toBe(1);

    await page.locator('label[for="email"]').click();
    await expect(page.locator('#email')).toBeFocused();

    await context.close();
  });

  test('toggles password visibility with Enter and Space', async ({ browser }) => {
    const { context, page } = await guestPage(browser);
    await page.goto('/admin/login', { waitUntil: 'networkidle' });

    const toggle = page.getByRole('button', { name: 'Show or hide password' });
    const password = page.locator('#password');

    await password.focus();
    await page.keyboard.press('Tab');
    await expect(toggle).toBeFocused();
    await expect(toggle).toHaveAttribute('aria-pressed', 'false');

    await page.keyboard.press('Enter');
    await expect(password).toHaveAttribute('type', 'text');
    await expect(toggle).toHaveAttribute('aria-pressed', 'true');

    await page.keyboard.press('Space');
    await expect(password).toHaveAttribute('type', 'password');
    await expect(toggle).toHaveAttribute('aria-pressed', 'false');

    await context.close();
  });
});

test.describe('Login and logout — session hardening', () => {
  test.describe.configure({ mode: 'serial' });

  const uid = generateUid();
  const email = `session-${uid}@example.com`;
  const password = 'Session-pass-1';
  let userId;

  test.beforeAll(async ({ browser }) => {
    const adminContext = await browser.newContext({ storageState: ADMIN_STATE_PATH });
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/admin/settings/users', { waitUntil: 'networkidle' });

    const created = await adminRequest(adminPage, 'POST', '/admin/settings/users/create', {
      name: `Session User ${uid}`,
      email,
      password,
      password_confirmation: password,
      role_id: '1',
      timezone: 'UTC',
      status: '1',
    });

    expect(created.status).toBe(200);

    userId = (created.json?.redirect_url ?? '').match(/edit\/(\d+)/)?.[1];
    expect(userId).toBeTruthy();

    await adminContext.close();
  });

  test.afterAll(async ({ browser }) => {
    if (! userId) {
      return;
    }

    const adminContext = await browser.newContext({ storageState: ADMIN_STATE_PATH });
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/admin/settings/users', { waitUntil: 'networkidle' });
    await adminRequest(adminPage, 'DELETE', `/admin/settings/users/edit/${userId}`, {});
    await adminContext.close();
  });

  test('logout ends the session and issues a new CSRF token', async ({ browser }) => {
    const { context, page } = await guestPage(browser);
    await signIn(page, email, password);

    const tokenBefore = await page.locator('input[name="_token"]').first().inputValue();

    await page.evaluate(() => document.getElementById('adminLogout').submit());
    await page.waitForURL(/\/admin\/login/, { timeout: 30000 });

    const tokenAfter = await page.locator('input[name="_token"]').first().inputValue();
    expect(tokenAfter).not.toBe(tokenBefore);

    await page.goBack();
    await page.reload();
    await expect(page).toHaveURL(/\/admin\/login/);

    await page.goto('/admin/dashboard');
    await expect(page).toHaveURL(/\/admin\/login/);

    await context.close();
  });

  test('ignores a referring page on another port of the same host', async ({ browser, baseURL }) => {
    const origin = new URL(baseURL);
    const foreign = `${origin.protocol}//${origin.hostname}:6666/admin/phish`;

    const { context, page } = await guestPage(browser, { extraHTTPHeaders: { Referer: foreign } });
    await signIn(page, email, password);

    expect(new URL(page.url()).origin).toBe(origin.origin);

    await context.close();
  });

  test('a password change by another admin signs the user out everywhere', async ({ browser }) => {
    const { context, page } = await guestPage(browser);
    await signIn(page, email, password);
    await page.goto('/admin/dashboard');
    await expect(page).toHaveURL(/\/admin\/dashboard/);

    const adminContext = await browser.newContext({ storageState: ADMIN_STATE_PATH });
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/admin/settings/users', { waitUntil: 'networkidle' });

    const uiLocaleId = await adminPage.evaluate(async (id) => {
      const response = await fetch(`/admin/settings/users/edit/${id}`, { headers: { Accept: 'application/json' } });

      return (await response.json()).user.ui_locale_id;
    }, userId);

    const newPassword = 'Session-pass-2';
    const updated = await adminRequest(adminPage, 'PUT', '/admin/settings/users/edit', {
      id: String(userId),
      name: `Session User ${uid}`,
      email,
      role_id: '1',
      ui_locale_id: String(uiLocaleId),
      timezone: 'UTC',
      status: '1',
      password: newPassword,
      password_confirmation: newPassword,
      current_password: process.env.ADMIN_PASSWORD || 'admin123',
    });
    expect(updated.status).toBe(200);

    await adminPage.goto('/admin/dashboard');
    await expect(adminPage).toHaveURL(/\/admin\/dashboard/);
    await adminContext.close();

    await page.reload();
    await expect(page).toHaveURL(/\/admin\/login/);

    await context.close();
  });
});
