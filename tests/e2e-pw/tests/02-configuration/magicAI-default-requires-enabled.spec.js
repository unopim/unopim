const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Magic AI — Default platform must be enabled (Issue #755)', () => {
  test('API rejects a disabled platform being marked as default with 422', async ({ adminPage }) => {
    await adminPage.goto('/admin/magic-ai/platform', { waitUntil: 'networkidle' });

    const label = 'Disabled Default ' + Date.now();

    const result = await adminPage.evaluate(async (label) => {
      const xsrf = (document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1] || '';
      const token = decodeURIComponent(xsrf);

      const form = new URLSearchParams({
        label,
        provider:   'openai',
        api_url:    'https://example.test',
        api_key:    'sk-test-key',
        models:     'gpt-test',
        is_default: '1',
        status:     '0',
      });

      const res = await fetch('/admin/magic-ai/platform', {
        method:      'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type':     'application/x-www-form-urlencoded',
          'X-XSRF-TOKEN':     token,
          'X-Requested-With': 'XMLHttpRequest',
          Accept:             'application/json',
        },
        body: form.toString(),
      });

      let body = null;
      try { body = await res.json(); } catch (_) {}

      return { status: res.status, body };
    }, label);

    expect(result.status).toBe(422);
    expect(result.body?.errors).toHaveProperty('is_default');
  });
});
