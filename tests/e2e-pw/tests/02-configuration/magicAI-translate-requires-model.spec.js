const { test, expect } = require('../../utils/fixtures');
const { xsrfToken } = require('../../utils/category-field-helpers');

const JSON_HEADERS = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

test.describe('UnoPim Magic AI — translation without an AI model', () => {
  test('asks for an AI model instead of failing with a server error', async ({ adminPage }) => {
    const gridResponse = adminPage.waitForResponse((response) => {
      const url = new URL(response.url());

      return url.pathname === '/admin/catalog/products'
        && response.request().resourceType() === 'xhr'
        && (response.headers()['content-type'] ?? '').includes('json');
    });

    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });

    const productId = (await (await gridResponse).json()).records?.[0]?.product_id;

    test.skip(! productId, 'The catalog has no product to translate.');

    for (const url of ['/admin/magic-ai/translate', '/admin/magic-ai/translate/all/attribute']) {
      const response = await adminPage.request.post(url, {
        headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': await xsrfToken(adminPage.context()) },
        form: {
          resource_id: String(productId),
          resource_type: 'product',
          field: 'name',
          attributes: 'name',
          model: '',
          targetLocale: 'fr_FR',
        },
      });

      expect(response.status()).toBe(422);
      expect((await response.json()).errors.model[0]).toMatch(/Select an AI model before translating/);
    }
  });
});
