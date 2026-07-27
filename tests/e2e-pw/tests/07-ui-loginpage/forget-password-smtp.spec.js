const { execSync } = require('child_process');
const { test, expect } = require('../../utils/fixtures');

/**
 * Broken-SMTP coverage for the admin forget-password flow.
 *
 * The reset mail is sent synchronously inside the request, so an unreachable
 * mail host makes the transport throw. The endpoint must degrade to a visible
 * WARNING (200 + yellow flash) — never a 500 and never a silent green success
 * that would hide the broken email setup from the operator.
 *
 * SMTP is broken for real by stopping the mailpit container for the duration of
 * this file, then restarting it in afterAll. Requires docker access on the host
 * running Playwright (the same stack that serves BASE_URL).
 */
const MAILPIT = 'unopim-unopim-mailpit-1';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const WARNING = 'Email could not be sent';

function docker(command) {
  execSync(`docker ${command}`, { stdio: 'ignore' });
}

async function freshForgetPasswordPage(browser) {
  // Logged-out context: the project's global storageState is an authenticated
  // admin, and the controller redirects a logged-in admin away from this page.
  const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
  const page = await context.newPage();
  await page.goto('/admin/forget-password', { waitUntil: 'networkidle', timeout: 30000 });

  return { context, page };
}

test.describe('Forget Password — broken SMTP', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(() => {
    docker(`stop ${MAILPIT}`);
  });

  test.afterAll(() => {
    docker(`start ${MAILPIT}`);
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
