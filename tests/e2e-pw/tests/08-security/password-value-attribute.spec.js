const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

/**
 * A typed password must never be readable from the serialized DOM.
 *
 * Vue 3.5 mirrors a bound input's live `value` into the DOM attribute on
 * every patch, so without a guard the plaintext password appears in
 * outerHTML — readable by extensions, "save page", and any DOM-capturing
 * error reporter, whichever state the visibility toggle is in. The
 * `v-no-value-attr` directive strips that reflection for password controls.
 */
test.describe('Security: password value stays out of the DOM attribute', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('login password never reaches the value attribute, toggle included', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/login`);

    const password = page.locator('input[name="password"]');

    await password.fill('Secret@123');
    await expect(password).toHaveValue('Secret@123');
    await expect(password).not.toHaveAttribute('value', /./);

    const toggle = page.locator('#visibilityIcon');

    await toggle.click();
    await expect(password).toHaveAttribute('type', 'text');
    await expect(password).not.toHaveAttribute('value', /./);

    await toggle.click();
    await expect(password).toHaveAttribute('type', 'password');
    await expect(password).toHaveValue('Secret@123');
    await expect(password).not.toHaveAttribute('value', /./);
  });
});
