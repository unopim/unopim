const { test, expect } = require('../../utils/fixtures');

const MAGIC_AI_PLATFORM_URL = '/admin/magic-ai/platform';

test.describe('UnoPim Magic AI — Custom (OpenAI-compatible) provider', () => {

  test('Custom provider is selectable in the Add Platform modal', async ({ adminPage }) => {
    await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
    await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();

    await adminPage.locator('input[name="provider"]').first().locator('..')
      .locator('.multiselect__placeholder, .multiselect__single').first().click();

    await expect(adminPage.getByRole('option', { name: /Custom \(OpenAI-compatible\)/ })).toBeVisible();
    await adminPage.getByRole('option', { name: /Custom \(OpenAI-compatible\)/ }).first().click();

    await expect(adminPage.locator('input[name="provider"]').first().locator('..')
      .locator('.multiselect__single')).toContainText('Custom (OpenAI-compatible)');
  });

  test('Selecting Custom auto-fills the label and leaves api_url empty', async ({ adminPage }) => {
    await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
    await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();

    await adminPage.locator('input[name="provider"]').first().locator('..')
      .locator('.multiselect__placeholder, .multiselect__single').first().click();
    await adminPage.getByRole('option', { name: /Custom \(OpenAI-compatible\)/ }).first().click();

    await expect(adminPage.locator('input[name="label"]')).toHaveValue('Custom (OpenAI-compatible)');
    await expect(adminPage.locator('input[name="api_url"]')).toHaveValue('');
  });

  test('Custom provider exposes API Key, API URL, and the manual model add input', async ({ adminPage }) => {
    await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
    await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();

    await adminPage.locator('input[name="provider"]').first().locator('..')
      .locator('.multiselect__placeholder, .multiselect__single').first().click();
    await adminPage.getByRole('option', { name: /Custom \(OpenAI-compatible\)/ }).first().click();

    await expect(adminPage.locator('input[name="api_key"]')).toBeVisible();
    await expect(adminPage.locator('input[name="api_url"]')).toBeVisible();
    await expect(adminPage.getByPlaceholder('Type custom model ID...')).toBeVisible();
    await expect(adminPage.getByRole('button', { name: '+ Add' })).toBeVisible();
  });

  test('User can manually add a model ID for a Custom provider', async ({ adminPage }) => {
    await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
    await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();

    await adminPage.locator('input[name="provider"]').first().locator('..')
      .locator('.multiselect__placeholder, .multiselect__single').first().click();
    await adminPage.getByRole('option', { name: /Custom \(OpenAI-compatible\)/ }).first().click();

    await adminPage.locator('input[name="api_url"]').fill('https://api.cerebras.ai/v1');
    await adminPage.getByPlaceholder('Type custom model ID...').fill('cerebras-llama-4-scout-17b');
    await adminPage.getByRole('button', { name: '+ Add' }).click();

    await expect(adminPage.locator('#app').getByText('cerebras-llama-4-scout-17b')).toBeVisible();
  });

  // NOTE: The earlier "Test Connection surfaces the upstream Cerebras 402 body"
  // e2e test was removed after master's saveWithTest() method was refactored to
  // POST directly to /store (no pre-save /test-connection hop). The backend
  // contract — resolver extracting the upstream body, Groq->Custom prefix
  // rewrite — is still covered by AiProviderCustomTest.php (two Http::fake
  // feature tests). That's the right layer for this behaviour.

});
