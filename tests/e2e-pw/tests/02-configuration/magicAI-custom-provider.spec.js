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

  test('Test Connection surfaces the upstream Cerebras 402 body in the UI', async ({ adminPage }) => {
    // Intercept the POST to the test endpoint and return a Cerebras-style
    // 402 (the same shape that triggered the original "Unknown error" bug).
    // The backend already has a Pest test for this; here we verify the UI
    // actually displays the cleaned-up message to the user.
    await adminPage.route('**/admin/magic-ai/platform/test-connection', async (route) => {
      await route.fulfill({
        status: 400,
        contentType: 'application/json',
        body: JSON.stringify({
          success: false,
          message: 'Connection test failed: HTTP 402: Payment required to access this resource. Visit your billing tab.',
        }),
      });
    });

    await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
    await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();

    // Pick Custom and fill the minimum fields the modal needs to enable Save.
    await adminPage.locator('input[name="provider"]').first().locator('..')
      .locator('.multiselect__placeholder, .multiselect__single').first().click();
    await adminPage.getByRole('option', { name: /Custom \(OpenAI-compatible\)/ }).first().click();

    await adminPage.locator('input[name="api_key"]').fill('csk-test-key-1234567890');
    await adminPage.locator('input[name="api_url"]').fill('https://api.cerebras.ai/v1');
    await adminPage.getByPlaceholder('Type custom model ID...').fill('llama3.1-8b');
    await adminPage.getByRole('button', { name: '+ Add' }).click();

    // Trigger the save flow which calls /test-connection first.
    await adminPage.getByRole('button', { name: 'Save' }).click();

    // The flash message must surface the Cerebras text and must NOT leak
    // either the misleading "Groq Error" prefix or the "Unknown error"
    // placeholder Prism would otherwise produce.
    await expect(
      adminPage.locator('#app').getByText(/Payment required to access this resource/i)
    ).toBeVisible();
    await expect(adminPage.locator('#app').getByText(/Groq Error/i)).toHaveCount(0);
    await expect(adminPage.locator('#app').getByText(/Unknown error/i)).toHaveCount(0);
  });

});
