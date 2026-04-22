const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Magic AI — Default platform must be enabled (Issue #755)', () => {
  test('API rejects a disabled platform being marked as default with 422', async ({ adminPage }) => {
    await adminPage.goto('/admin/magic-ai/platform', { waitUntil: 'networkidle' });

    const csrfToken = await adminPage.evaluate(() =>
      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    );

    const response = await adminPage.request.post('/admin/magic-ai/platform', {
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      form: {
        label: 'Disabled Default ' + Date.now(),
        provider: 'openai',
        api_url: 'https://example.test',
        api_key: 'sk-test-key',
        models: 'gpt-test',
        is_default: '1',
        status: '0',
      },
    });

    expect(response.status()).toBe(422);
    const body = await response.json();
    expect(body.errors).toHaveProperty('is_default');
  });
});
