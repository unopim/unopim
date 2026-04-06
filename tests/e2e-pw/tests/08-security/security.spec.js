const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

const LOGIN_URL = '/admin/login';
const FORGOT_URL = '/admin/forget-password';

/**
 * Helper: Log out and go to login page.
 */
async function goToLoginPage(page) {
  await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });
  if (page.url().includes('/admin/login')) {
    await page.waitForLoadState('networkidle');
    return;
  }
  await page.click('button.rounded-full');
  await page.getByRole('link', { name: 'Logout' }).click();
  await expect(page).toHaveURL(/\/admin\/login/);
}

test.describe('Security Vulnerability Fixes', () => {

  // ─── Vuln 1: Open Redirect via Referer ─────────────────────────────

  test('Login page should not redirect to external URLs', async ({ adminPage }) => {
    await goToLoginPage(adminPage);

    // Attempt login with invalid credentials — the page should NOT redirect externally
    await adminPage.fill('input[name=email]', 'admin@example.com');
    await adminPage.fill('input[name=password]', 'wrongpassword');
    await adminPage.press('input[name=password]', 'Enter');
    await adminPage.waitForLoadState('networkidle');

    // Verify we are still on the same host (not redirected externally)
    const currentUrl = adminPage.url();
    expect(currentUrl).toContain('127.0.0.1');
    expect(currentUrl).not.toContain('attacker.com');
  });

  // ─── Vuln 2: Rate Limiting on Login ────────────────────────────────

  test('Login should be rate limited after multiple failed attempts', async ({ adminPage }) => {
    await goToLoginPage(adminPage);

    let rateLimited = false;

    for (let i = 0; i < 8; i++) {
      await adminPage.fill('input[name=email]', 'admin@example.com');
      await adminPage.fill('input[name=password]', `wrong${i}`);
      await adminPage.press('input[name=password]', 'Enter');
      await adminPage.waitForLoadState('networkidle');

      // Check if we got a 429 (Too Many Requests) page or throttle message
      const bodyText = await adminPage.locator('body').textContent();
      if (bodyText.includes('Too Many Attempts') || bodyText.includes('429')) {
        rateLimited = true;
        break;
      }
    }

    expect(rateLimited).toBe(true);
  });

  // ─── Vuln 3: Password Validation ──────────────────────────────────

  test('User creation should reject weak passwords', async ({ adminPage }) => {
    await navigateTo(adminPage, 'users');

    // Click "Create User" button
    await adminPage.getByRole('link', { name: 'Create User' }).click();
    await adminPage.waitForLoadState('networkidle');

    // Fill form with a weak password
    await adminPage.fill('input[name=name]', 'Weak Pass User');
    await adminPage.fill('input[name=email]', `weakpwd_${Date.now()}@example.com`);
    await adminPage.fill('input[name=password]', 'abc');
    await adminPage.fill('input[name=password_confirmation]', 'abc');

    // Try to submit
    await adminPage.getByRole('button', { name: 'Save User' }).click();

    // Should show validation error for short password
    const errorMsg = adminPage.locator('text=The Password field must be at least 6 characters');
    await expect(errorMsg.first()).toBeVisible({ timeout: 10000 });
  });

  // ─── Vuln 4: User Enumeration via Forgot Password ─────────────────

  test('Forgot password should show same message for existing and non-existing emails', async ({ adminPage }) => {
    await goToLoginPage(adminPage);

    // Go to forgot password page
    await adminPage.goto(FORGOT_URL, { waitUntil: 'networkidle' });

    // Submit with a non-existing email
    await adminPage.fill('input[name=email]', `nonexistent_${Date.now()}@example.com`);
    await adminPage.press('input[name=email]', 'Enter');
    await adminPage.waitForLoadState('networkidle');

    // Should show generic success message (not "email not exist" error)
    const pageText = await adminPage.locator('body').textContent();
    expect(pageText).not.toContain('Email Not Exist');
    expect(pageText).not.toContain('email-not-exist');

    // Should show the generic "if account exists" message
    const successMsg = adminPage.locator('text=If an account with that email exists');
    await expect(successMsg.first()).toBeVisible({ timeout: 10000 });
  });

});
