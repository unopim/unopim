const { test, expect } = require('../../utils/fixtures');

/**
 * SSRF guard on the webhook test-probe endpoint.
 *
 * POST configuration/webhook/test fetches an admin-supplied URL server-side.
 * SafeWebhookUrl must reject loopback / private / link-local / reserved
 * targets and bad schemes with a 422 before any request leaves the box. The
 * cases below stay blocked even when WEBHOOK_ALLOW_LOOPBACK is on in the test
 * env (that flag re-opens only 127/8 + ::1, never metadata or RFC1918), so the
 * spec does not depend on the loopback setting.
 */
test.describe('SSRF - webhook test endpoint rejects internal targets', () => {
  const BLOCKED = [
    { label: 'cloud metadata (link-local)', url: 'http://169.254.169.254/latest/meta-data/' },
    { label: 'RFC1918 10/8', url: 'http://10.0.0.1/' },
    { label: 'RFC1918 192.168/16', url: 'http://192.168.0.1/' },
    { label: 'RFC1918 172.16/12', url: 'http://172.16.0.1/' },
    { label: 'non-http scheme (file)', url: 'file:///etc/passwd' },
    { label: 'gopher scheme', url: 'gopher://127.0.0.1:6379/' },
  ];

  async function probe(adminPage, url) {
    return adminPage.evaluate(async ({ url }) => {
      const token = document.querySelector('meta[name="csrf-token"]')?.content
        ?? document.querySelector('input[name="_token"]')?.value
        ?? '';
      const res = await fetch('/admin/configuration/webhook/test', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ url }),
        redirect: 'manual',
      });
      let json = null;
      try {
        json = await res.json();
      } catch {
        json = null;
      }
      return { status: res.status, json };
    }, { url });
  }

  // The exact message SafeWebhookUrl::validate() failure returns. Asserting it
  // distinguishes a URL rejected *before* any request from the connection-failed
  // / unreachable messages test() returns after it actually contacts a host — so
  // removing the guard (and letting the fetch time out) would fail this test.
  const UNSAFE_URL_MESSAGE = 'The URL points at a private, loopback or internal address and is not allowed.';

  for (const { label, url } of BLOCKED) {
    test(`blocks ${label}`, async ({ adminPage }) => {
      await adminPage.goto('/admin/configuration/webhook', { waitUntil: 'domcontentloaded' });

      const { status, json } = await probe(adminPage, url);

      expect(status, `${url} must be rejected (422), not fetched`).toBe(422);
      expect(json?.success, `${url} must report failure`).toBe(false);
      expect(json?.message, `${url} must be rejected by the URL guard, not a failed fetch`).toBe(UNSAFE_URL_MESSAGE);
    });
  }
});
