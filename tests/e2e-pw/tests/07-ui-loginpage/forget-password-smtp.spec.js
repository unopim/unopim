const { test, expect } = require('../../utils/fixtures');

/**
 * Broken-SMTP coverage for the admin forget-password flow.
 * The reset mail sends synchronously, so an unreachable host makes the transport throw.
 * The endpoint must degrade to a warning flash (200), never a 500 or silent green success.
 *
 * SMTP host/port are stored as core config (System Settings > Email; see
 * CoreServiceProvider, which layers `mail_host`/`mail_port` over config('mail...')
 * at boot), so the transport is broken here by pointing that setting at an
 * unreachable host through the admin UI — not by stopping a mail container,
 * which this environment doesn't run the app's actual SMTP transport through.
 */
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const WARNING = 'Email could not be sent';
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
  await adminPage.getByRole('button', { name: 'Save changes' }).click();
  await adminPage.waitForLoadState('networkidle').catch(() => {});
}

test.describe('Forget Password — broken SMTP', () => {
  test.describe.configure({ mode: 'serial' });

  let originalSettings;

  test.beforeAll(async ({ browser }) => {
    const adminContext = await browser.newContext({
      storageState: require('path').resolve(__dirname, '../../.state/admin-auth.json'),
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
      storageState: require('path').resolve(__dirname, '../../.state/admin-auth.json'),
    });
    const adminPage = await adminContext.newPage();

    await writeMailSettings(adminPage, originalSettings);

    await adminContext.close();
  });

  test('degrades to a warning flash, never a 500, when the mail host is unreachable', async ({ browser }) => {
    const { context, page } = await freshForgetPasswordPage(browser);

    await page.locator('input[name="email"]').fill(ADMIN_EMAIL);

    const [response] = await Promise.all([
      page.waitForResponse(
        (res) => res.url().includes('/admin/forget-password') && res.request().method() === 'POST',
        { timeout: 30000 },
      ),
      page.locator('button[type="submit"]').click(),
    ]);

    expect(response.status()).toBe(200);
    expect(await response.json()).toMatchObject({ type: 'warning' });

    await expect(page.getByText(WARNING, { exact: false })).toBeVisible();

    expect(page.url()).toContain('/admin/forget-password');

    await page.screenshot({ path: 'test-results/forget-password-smtp-warning.png' });

    await context.close();
  });
});
