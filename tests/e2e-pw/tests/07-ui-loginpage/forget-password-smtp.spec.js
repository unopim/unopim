const { execSync } = require('child_process');
const { test, expect } = require('../../utils/fixtures');

/**
 * Broken-SMTP coverage for the admin forget-password flow.
 * The reset mail sends synchronously, so an unreachable host makes the transport throw.
 * The endpoint must degrade to a warning flash (200), never a 500 or silent green success.
 * SMTP is broken by stopping the mailpit container for this file (restarted in afterAll);
 * requires docker on the Playwright host.
 */
const MAILPIT = 'unopim-unopim-mailpit-1';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const WARNING = 'Email could not be sent';

function docker(command) {
  execSync(`docker ${command}`, { stdio: 'ignore' });
}

async function freshForgetPasswordPage(browser) {
  // Logged-out context: global storageState is an admin, whom the controller redirects away.
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
