const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

/**
 * Stored passwords can never begin with whitespace (creation, reset, and the
 * installer all reject it), so a leading space typed or pasted into the login
 * password field is always an accident — the field strips it as it appears,
 * while interior spaces (passphrases) are preserved.
 */
test.describe('Login password field refuses leading whitespace', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('typed and pasted leading spaces are stripped, interior spaces kept', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/login`);

    const password = page.locator('input[name="password"]');

    await password.pressSequentially('  Secret@123');
    await expect(password).toHaveValue('Secret@123');

    await password.fill('');
    await password.fill('   pasted secret');
    await expect(password).toHaveValue('pasted secret');

    await password.fill('');
    await password.pressSequentially('pass phrase');
    await expect(password).toHaveValue('pass phrase');
  });
});
