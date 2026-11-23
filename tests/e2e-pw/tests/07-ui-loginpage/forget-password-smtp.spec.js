const { test, expect } = require('../../utils/fixtures');
const { clickSaveAndExpect } = require('../../utils/helpers');
const path = require('path');

const ADMIN_STATE_PATH = path.resolve(__dirname, '../../', process.env.PW_STATE_DIR || '.state', 'admin-auth.json');

/**
 * Broken-SMTP coverage for the admin forget-password flow.
 * The reset mail is sent after the response, so an unreachable host is only logged.
 * The visitor always gets the same generic answer, whether or not the account exists
 * and whether or not the mail could be sent, so the page never reveals either.
 *
 * SMTP host/port are stored as core config (System Settings > Email; see
 * CoreServiceProvider, which layers `mail_host`/`mail_port` over config('mail...')
 * at boot), so the transport is broken here by pointing that setting at an
 * unreachable host through the admin UI — not by stopping a mail container,
 * which this environment doesn't run the app's actual SMTP transport through.
 */
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const GENERIC = 'If an account with that email exists, a password reset link has been sent.';
const UNKNOWN_EMAIL = 'nobody-forget-password@example.com';
const EMAIL_SETTINGS_URL = '/admin/configuration/system/system.email';
const UNREACHABLE_HOST = '127.0.0.1';
const UNREACHABLE_PORT = '1';

async function freshForgetPasswordPage(browser) {
  // Logged-out context: global storageState is an admin, whom the controller redirects away.
  const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
  const page = await context.newPage();
  await page.goto('/admin/forget-password', { waitUntil: 'networkidle', timeout: 30000 });

  return { context, page };
}

/**
 * Read the current SMTP host/port from the admin Email settings form.
 */
async function readMailSettings(adminPage) {
  await adminPage.goto(EMAIL_SETTINGS_URL, { waitUntil: 'networkidle', timeout: 30000 });

  return {
    host: await adminPage.locator('input[name="emails[configure][email_settings][mail_host]"]').inputValue(),
    port: await adminPage.locator('input[name="emails[configure][email_settings][mail_port]"]').inputValue(),
  };
}

/**
 * Set the SMTP host/port via the admin UI and save through the unsaved-changes bar.
 */
async function writeMailSettings(adminPage, { host, port }) {
  await adminPage.goto(EMAIL_SETTINGS_URL, { waitUntil: 'networkidle', timeout: 30000 });
  await adminPage.locator('input[name="emails[configure][email_settings][mail_host]"]').fill(host);
  await adminPage.locator('input[name="emails[configure][email_settings][mail_port]"]').fill(port);
  await clickSaveAndExpect(adminPage, 'Save changes', /Settings saved successfully/i);
}

test.describe('Forget Password — broken SMTP', { tag: '@global-state' }, () => {
  test.describe.configure({ mode: 'serial' });

  let originalSettings;

  test.beforeAll(async ({ browser }) => {
    const adminContext = await browser.newContext({
      storageState: ADMIN_STATE_PATH,
    });
    const adminPage = await adminContext.newPage();

    originalSettings = await readMailSettings(adminPage);
    await writeMailSettings(adminPage, { host: UNREACHABLE_HOST, port: UNREACHABLE_PORT });

    await adminContext.close();
  });

  test.afterAll(async ({ browser }) => {
    if (!originalSettings) {
      return;
    }

    const adminContext = await browser.newContext({
      storageState: ADMIN_STATE_PATH,
    });
    const adminPage = await adminContext.newPage();

    await writeMailSettings(adminPage, originalSettings);

    await adminContext.close();
  });

  for (const email of [ADMIN_EMAIL, UNKNOWN_EMAIL]) {
    test(`gives the generic answer for ${email === ADMIN_EMAIL ? 'an existing' : 'an unknown'} account when the mail host is unreachable`, async ({ browser }) => {
      const { context, page } = await freshForgetPasswordPage(browser);

      await page.locator('input[name="email"]').fill(email);

      const [response] = await Promise.all([
        page.waitForResponse(
          (res) => res.url().includes('/admin/forget-password') && res.request().method() === 'POST',
          { timeout: 30000 },
        ),
        page.locator('button[type="submit"]').click(),
      ]);

      expect(response.status()).toBe(200);
      expect(await response.json()).toEqual({ message: GENERIC });

      await expect(page.getByText(GENERIC)).toBeVisible();

      expect(page.url()).toContain('/admin/forget-password');

      await context.close();
    });
  }
});
