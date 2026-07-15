const { test, expect } = require('../../utils/fixtures');
const { clickSave } = require('../../utils/helpers');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

/**
 * Create a fresh unauthenticated browser page (no saved session).
 */
async function createGuestPage(browser) {
  const context = await browser.newContext({ storageState: undefined, baseURL: BASE_URL });
  const page = await context.newPage();
  return { page, context };
}

test.describe('Security Vulnerability Fixes', () => {

  // ─── Vuln 1: Open Redirect via Referer ─────────────────────────────

  test('Login page should not redirect to external URLs', async ({ browser }) => {
    const { page, context } = await createGuestPage(browser);

    await page.goto('/admin/login', { waitUntil: 'networkidle' });

    // Use a throwaway email so this failed-login probe does not consume the
    // real admin account's per-email login rate-limit budget (which a later
    // test relies on to sign in).
    await page.fill('input[name=email]', `redirect_probe_${Date.now()}@example.com`);
    await page.fill('input[name=password]', 'wrongpassword');
    await page.press('input[name=password]', 'Enter');
    await page.waitForLoadState('networkidle');

    const currentUrl = page.url();
    const expectedOrigin = new URL(BASE_URL).origin;
    expect(currentUrl).toContain(expectedOrigin);
    expect(currentUrl).toContain('/admin/login');

    await page.close();
    await context.close();
  });

  // ─── Vuln 3: Password Validation (before rate limit test) ─────────

  test('User creation should reject weak passwords', async ({ browser }) => {
    // Use a fresh authenticated context to avoid stale session issues
    const context = await browser.newContext({ storageState: undefined, baseURL: BASE_URL });
    const page = await context.newPage();

    // Login fresh
    await page.goto('/admin/login', { waitUntil: 'networkidle' });
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
    // The login form validates asynchronously then POSTs via AJAX, so
    // networkidle can resolve before the request fires. Wait for the
    // post-login navigation away from the login screen instead.
    await page.waitForURL((url) => !url.pathname.includes('/admin/login'), { timeout: 15000 });
    await page.waitForLoadState('networkidle');

    // Navigate to users
    await page.goto('/admin/settings/users', { waitUntil: 'networkidle' });

    // "Create User" is a <button> that opens a modal
    await page.getByRole('button', { name: 'Create User' }).click();

    // Modal form uses role-based textbox selectors
    await page.getByRole('textbox', { name: 'Name' }).fill('Weak Pass User');
    await page.getByRole('textbox', { name: 'email@example.com' }).fill(`weakpwd_${Date.now()}@example.com`);
    await page.getByRole('textbox', { name: 'Password', exact: true }).fill('abc');
    await page.getByRole('textbox', { name: 'Confirm Password' }).fill('abc');

    await clickSave(page, 'Save User');

    const errorMsg = page.locator('#app').getByText(/at least \d+ characters/i);
    await expect(errorMsg.first()).toBeVisible({ timeout: 10000 });

    await page.close();
    await context.close();
  });

  // ─── Vuln 4: User Enumeration via Forgot Password ─────────────────

  test('Forgot password should show same message for existing and non-existing emails', async ({ browser }) => {
    const { page, context } = await createGuestPage(browser);

    await page.goto('/admin/forget-password', { waitUntil: 'networkidle' });

    await page.fill('input[name=email]', `nonexistent_${Date.now()}@example.com`);
    await page.press('input[name=email]', 'Enter');
    await page.waitForLoadState('networkidle');

    const pageText = await page.locator('body').textContent();
    expect(pageText).not.toContain('Email Not Exist');
    expect(pageText).not.toContain('email-not-exist');

    const successMsg = page.locator('text=If an account with that email exists');
    await expect(successMsg.first()).toBeVisible({ timeout: 10000 });

    await page.close();
    await context.close();
  });

  // ─── Vuln 2: Rate Limiting (last — exhausts the rate limiter) ─────

  test('Login should be rate limited after multiple failed attempts', async ({ browser }) => {
    const { page, context } = await createGuestPage(browser);

    // The limit is env-configurable (config admin.auth.login_rate_limit); test
    // environments raise it via ADMIN_LOGIN_RATE_LIMIT so back-to-back logins by
    // the rest of the suite are not throttled. Probe with a generous fixed count
    // that exceeds the test-env limit — the loop stops as soon as a 429 arrives,
    // so this asserts throttling at any configured threshold up to ~30/min.
    const maxAttempts = 30;
    test.setTimeout((maxAttempts + 5) * 1500);

    await page.goto('/admin/login', { waitUntil: 'networkidle' });

    // The login form submits via AJAX, so a 429 does not navigate the page —
    // watch the network response instead of the page body. Use a throwaway
    // email (the limiter is keyed per email) so the real admin account is not
    // throttled for other tests.
    let rateLimited = false;
    page.on('response', (response) => {
      if (response.url().includes('/admin/login') && response.status() === 429) {
        rateLimited = true;
      }
    });

    const email = `ratelimit_probe_${Date.now()}@example.com`;

    for (let i = 0; i < maxAttempts && !rateLimited; i++) {
      const emailInput = page.locator('input[name=email]');
      if (!(await emailInput.isVisible({ timeout: 3000 }).catch(() => false))) {
        break;
      }

      await emailInput.fill(email);
      await page.fill('input[name=password]', `wrong${i}`);
      await page.press('input[name=password]', 'Enter');
      await page.waitForTimeout(600);
    }

    expect(rateLimited).toBe(true);

    await page.close();
    await context.close();
  });

});
