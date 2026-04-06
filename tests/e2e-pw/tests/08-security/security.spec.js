const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

/**
 * Create a fresh unauthenticated browser page (no saved session).
 */
async function createGuestPage(browser) {
  const context = await browser.newContext({ storageState: undefined });
  const page = await context.newPage();
  return { page, context };
}

test.describe('Security Vulnerability Fixes', () => {

  // ─── Vuln 1: Open Redirect via Referer ─────────────────────────────

  test('Login page should not redirect to external URLs', async ({ browser }) => {
    const { page, context } = await createGuestPage(browser);

    await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'networkidle' });

    await page.fill('input[name=email]', 'admin@example.com');
    await page.fill('input[name=password]', 'wrongpassword');
    await page.press('input[name=password]', 'Enter');
    await page.waitForLoadState('networkidle');

    const currentUrl = page.url();
    expect(currentUrl).toContain('127.0.0.1');
    expect(currentUrl).toContain('/admin/login');

    await page.close();
    await context.close();
  });

  // ─── Vuln 3: Password Validation (before rate limit test) ─────────

  test('User creation should reject weak passwords', async ({ adminPage }) => {
    await navigateTo(adminPage, 'users');

    const createBtn = adminPage.locator('#app').getByText('Create User').first();
    await createBtn.click();
    await adminPage.waitForTimeout(500);

    await adminPage.fill('input[name=name]', 'Weak Pass User');
    await adminPage.fill('input[name=email]', `weakpwd_${Date.now()}@example.com`);
    await adminPage.fill('input[name=password]', 'abc');
    await adminPage.fill('input[name=password_confirmation]', 'abc');

    const saveBtn = adminPage.getByRole('button', { name: 'Save User' });
    await saveBtn.click();

    const errorMsg = adminPage.locator('text=at least 6 characters');
    await expect(errorMsg.first()).toBeVisible({ timeout: 10000 });
  });

  // ─── Vuln 4: User Enumeration via Forgot Password ─────────────────

  test('Forgot password should show same message for existing and non-existing emails', async ({ browser }) => {
    const { page, context } = await createGuestPage(browser);

    await page.goto('http://127.0.0.1:8000/admin/forget-password', { waitUntil: 'networkidle' });

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

    await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'networkidle' });

    let rateLimited = false;

    for (let i = 0; i < 8; i++) {
      await page.fill('input[name=email]', 'admin@example.com');
      await page.fill('input[name=password]', `wrong${i}`);
      await page.press('input[name=password]', 'Enter');
      await page.waitForLoadState('networkidle');

      const bodyText = await page.locator('body').textContent();
      if (bodyText.includes('Too Many Attempts') || bodyText.includes('429')) {
        rateLimited = true;
        break;
      }
    }

    expect(rateLimited).toBe(true);

    await page.close();
    await context.close();
  });

});
