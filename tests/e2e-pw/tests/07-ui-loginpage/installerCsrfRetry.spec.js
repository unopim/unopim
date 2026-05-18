const { test, expect } = require('@playwright/test');

/**
 * Regression: UI installer issued 419 (CSRF mismatch) on the second submit of
 * the DB-credentials step, because EnvironmentManager::generateEnv() called
 * DatabaseManager::generateKey() on every submit, rotating APP_KEY. On the
 * next request, Laravel could no longer decrypt the encrypted session cookie,
 * dropped the session, and VerifyCsrfToken returned 419 — bouncing the user
 * back to step 1.
 *
 * DESTRUCTIVE: this spec drives the real installer wizard end-to-end. It will
 * rewrite .env, run migrate:fresh, and wipe whatever database is configured.
 * Only run it against a throwaway environment — never your dev/staging DB.
 *
 * To run:
 *   BASE_URL=http://127.0.0.1:8000 npx playwright test \
 *     tests/07-ui-loginpage/installerCsrfRetry.spec.js
 */
test.describe('Installer @destructive — CSRF survives DB-credentials retry (issue #867)', () => {
  test('second env-file-setup submit does not return 419 after a first submit', async ({ page }) => {
    const responses = [];

    page.on('response', (response) => {
      if (response.url().includes('/install/api/env-file-setup')) {
        responses.push({ url: response.url(), status: response.status() });
      }
    });

    // Suppress the alert() shown on validation/CSRF failure so the test does
    // not hang on a modal dialog.
    page.on('dialog', (dialog) => dialog.dismiss().catch(() => {}));

    await page.goto('/install', { waitUntil: 'networkidle' });

    // Walk through earlier steps (Requirements → App Config → Database).
    // The exact button names are pulled from installer/index.blade.php.
    await page.getByRole('button', { name: /next|continue/i }).first().click();

    // Database step — submit invalid creds first.
    await page.locator('input[name="db_hostname"]').fill('127.0.0.1');
    await page.locator('input[name="db_port"]').fill('3306');
    await page.locator('input[name="db_name"]').fill('unopim_test');
    await page.locator('input[name="db_username"]').fill('root');
    await page.locator('input[name="db_password"]').fill('wrong-password-on-purpose');

    await page.getByRole('button', { name: /start.*installation|install/i }).click();

    // Wait for the first env-file-setup response.
    await expect.poll(() => responses.length, { timeout: 10_000 }).toBeGreaterThanOrEqual(1);
    expect(responses[0].status).toBe(200);

    // Fix the password and resubmit.
    await page.locator('input[name="db_password"]').fill('correct-password');
    await page.getByRole('button', { name: /start.*installation|install|retry/i }).click();

    // Wait for the second env-file-setup response.
    await expect.poll(() => responses.length, { timeout: 10_000 }).toBeGreaterThanOrEqual(2);

    // The actual regression: status must NOT be 419 on retry.
    expect(responses[1].status).not.toBe(419);
  });
});
