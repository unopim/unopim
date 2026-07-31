const { test, expect } = require('../../utils/fixtures');

/**
 * Security-focused coverage for the admin login endpoint.
 *
 * These tests deliberately use FRESH, UNAUTHENTICATED browser contexts (not the
 * shared admin-auth fixture) so they exercise the real /admin/login surface:
 * CSRF enforcement, SQL-injection resistance, reflected-XSS escaping, brute-force
 * throttling and user-enumeration parity.
 *
 * The throttle test keys off a throwaway email (the limiter key is `email|ip`,
 * see AdminServiceProvider::boot ‑ 'admin-login'), so it never locks the real
 * admin account used by the rest of the suite.
 */

const VALID_PASSWORD = process.env.ADMIN_PASSWORD || 'admin123';
const KNOWN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const CREDENTIAL_ERROR = 'Please check your credentials and try again.';

/**
 * Open a clean, logged-out page on the login screen.
 */
async function freshLoginPage(browser) {
  // Force a logged-out context: the project applies a global `storageState`
  // (an authenticated admin session), so an unqualified newContext() would be
  // logged in and /admin/login would redirect to the dashboard.
  const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
  const page = await context.newPage();
  await page.goto('/admin/login', { waitUntil: 'networkidle', timeout: 30000 });

  return { context, page };
}

test.describe('Login Page — Security', () => {
  test('rejects a cross-site POST with no CSRF token (419)', async ({ browser }) => {
    const { context, page } = await freshLoginPage(browser);

    // Laravel 13's Fetch-Metadata CSRF defense trusts a browser-supplied
    // `Sec-Fetch-Site: same-origin` header as proof of same-origin intent and
    // skips the token check for it (PreventRequestForgery::hasValidOrigin) —
    // a real forged cross-site request can never carry that header, since
    // browsers set it and JS cannot override it. `page.evaluate(fetch(...))`
    // runs inside the browser and would get that same free pass, so it can't
    // exercise the token fallback. `context.request` issues the POST from
    // Playwright's own HTTP client (no Sec-Fetch-Site header attached),
    // which is what actually forces token verification and reproduces what
    // an off-origin attacker's request looks like.
    const res = await context.request.post('/admin/login', {
      form: { email: KNOWN_EMAIL, password: VALID_PASSWORD },
      maxRedirects: 0,
      failOnStatusCode: false,
    });

    expect(res.status()).toBe(419);

    await context.close();
  });

  test('is not vulnerable to SQL injection in the email field', async ({ browser }) => {
    const { context, page } = await freshLoginPage(browser);

    // Post injection payloads straight at the endpoint (bypassing the client-side
    // email-format guard) to prove the query is parameterised: every payload must
    // fail authentication (401) — never authenticate (200/302 to dashboard) and
    // never raise a SQL error (500).
    const outcomes = await page.evaluate(async () => {
      const token =
        document.querySelector('meta[name=csrf-token]')?.content ||
        document.querySelector('input[name=_token]')?.value;
      const payloads = [
        "realadmin@example.com' OR '1'='1",
        "' OR 1=1 -- ",
        "admin@example.com'; DROP TABLE admins; -- ",
        "\" OR \"\"=\"",
      ];
      const results = [];

      for (const email of payloads) {
        const res = await fetch('/admin/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
          },
          body: `email=${encodeURIComponent(email)}&password=x&_token=${encodeURIComponent(token || '')}`,
          credentials: 'same-origin',
          redirect: 'manual',
        });
        results.push(res.status);
      }

      return results;
    });

    for (const status of outcomes) {
      // 401 = rejected as bad credentials, 422 = validation. Never authenticated, never a DB error.
      expect([401, 422]).toContain(status);
      expect(status).not.toBe(200);
      expect(status).not.toBe(302);
      expect(status).not.toBe(500);
    }

    await context.close();
  });

  test('does not reflect the email parameter as raw HTML (no reflected XSS)', async ({ browser }) => {
    const { context, page } = await freshLoginPage(browser);

    const reflection = await page.evaluate(async () => {
      const marker = '"><b>xssmarker</b>';
      const res = await fetch('/admin/login?email=' + encodeURIComponent(marker), {
        credentials: 'same-origin',
      });
      const html = await res.text();

      return {
        raw: html.includes('<b>xssmarker</b>'),
        escaped: html.includes('&lt;b&gt;xssmarker'),
      };
    });

    // The payload must never appear unescaped. (Neither reflected at all is also fine.)
    expect(reflection.raw).toBe(false);

    await context.close();
  });

  test('throttles brute-force attempts after the configured limit (429)', async ({ browser }) => {
    const { context, page } = await freshLoginPage(browser);

    const token =
      (await page.locator('meta[name=csrf-token]').getAttribute('content').catch(() => null)) ||
      (await page.locator('input[name=_token]').inputValue().catch(() => null));

    // The limiter key is `email|ip` (AdminServiceProvider::configureRateLimiting),
    // so a fresh email per run keeps a re-run (or a previous failed attempt)
    // from starting the test already throttled.
    const probeEmail = `throttle-probe-${Date.now()}@example.com`;
    // The `admin-login` limiter is env-driven (ADMIN_LOGIN_RATE_LIMIT, default
    // 5); CI and this environment both set it to 20, so send enough attempts
    // to clear whatever limit is configured rather than hardcoding a count
    // tuned to the framework default.
    const maxAttempts = Number(process.env.ADMIN_LOGIN_RATE_LIMIT) || 20;
    const statuses = [];

    for (let i = 0; i < maxAttempts + 3; i++) {
      const res = await context.request.post('/admin/login', {
        headers: { 'X-CSRF-TOKEN': token || '', Accept: 'application/json' },
        form: { email: probeEmail, password: `wrong${i}`, _token: token || '' },
        maxRedirects: 0,
        failOnStatusCode: false,
      });
      statuses.push(res.status());
    }

    // First attempts fail on credentials (401); once the limit is hit the
    // throttle middleware returns 429 for the remainder.
    expect(statuses).toContain(401);
    expect(statuses).toContain(429);
    // Lockout must be sticky — the final attempt is throttled, not re-evaluated.
    expect(statuses[statuses.length - 1]).toBe(429);

    await context.close();
  });

  test('returns an identical message for unknown and known emails (no user enumeration)', async ({ browser }) => {
    // Unknown email + valid password
    const first = await freshLoginPage(browser);
    await first.page.fill('input[name=email]', 'definitely-not-a-user@example.com');
    await first.page.fill('input[name=password]', VALID_PASSWORD);
    await first.page.press('input[name=password]', 'Enter');
    await expect(first.page.getByRole('alert')).toContainText(CREDENTIAL_ERROR);
    await first.context.close();

    // Known email + wrong password
    const second = await freshLoginPage(browser);
    await second.page.fill('input[name=email]', KNOWN_EMAIL);
    await second.page.fill('input[name=password]', 'wrong-password-xyz');
    await second.page.press('input[name=password]', 'Enter');
    await expect(second.page.getByRole('alert')).toContainText(CREDENTIAL_ERROR);
    await second.context.close();
  });

  test('does not repopulate the password field after a failed login', async ({ browser }) => {
    const { context, page } = await freshLoginPage(browser);

    await page.fill('input[name=email]', KNOWN_EMAIL);
    await page.fill('input[name=password]', 'wrong-password-xyz');
    await page.press('input[name=password]', 'Enter');

    await expect(page.getByRole('alert')).toContainText(CREDENTIAL_ERROR);
    expect(await page.inputValue('input[name=password]')).toBe('');

    await context.close();
  });
});
