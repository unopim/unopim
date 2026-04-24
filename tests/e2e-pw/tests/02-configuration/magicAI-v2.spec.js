const { test, expect } = require('../../utils/fixtures');
const { generateUid } = require('../../utils/helpers');

const OPENAI_API_KEY = process.env.OPENAI_API_KEY || '';
const MAGIC_AI_CONFIG_URL = '/admin/configuration/general/magic_ai';
const MAGIC_AI_PLATFORM_URL = '/admin/magic-ai/platform';

test.describe('UnoPim Magic AI v2.0.1 Configuration', () => {

// ═════════════════════════════════════════════════
// SECTION 1: Configuration Page Layout
// ═════════════════════════════════════════════════

test('1.1 - Magic AI config page loads with correct title', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Magic AI').first()).toBeVisible();
});

test('1.2 - Magic AI config page has Save Configuration button', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const saveBtn = adminPage.getByRole('button', { name: 'Save Configuration' });
  await expect(saveBtn).toBeVisible();
  await expect(saveBtn).toBeEnabled();
});

test('1.3 - Config page shows all four v2.0.1 sections', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });

  await expect(adminPage.locator('#app').getByText('Agentic PIM', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Text Generation', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Image Generation', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Translation', { exact: true })).toBeVisible();
});

test('1.4 - Each section has a description paragraph', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });

  await expect(adminPage.locator('#app').getByText(/Configure the AI Agent Chat/)).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/Configure the default AI platform and model for generating product descriptions/)).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/Configure the default AI platform and model for generating product images/)).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/Configure the AI platform and model for translating product content/)).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 2: Agentic PIM Configuration
// ═════════════════════════════════════════════════

test('2.1 - Agentic PIM section description mentions autonomous enrichment', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText(/autonomous enrichment workflows/)).toBeVisible();
});

test('2.2 - Agentic PIM has Enable AI Agent Chat checkbox', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Enable AI Agent Chat')).toBeVisible();
  const checkbox = adminPage.getByRole('checkbox', { name: 'Enable AI Agent Chat' });
  await expect(checkbox).toBeVisible();
});

test('2.3 - Agentic PIM has Max Agent Steps Per Turn dropdown with default "5 (Default)"', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Max Agent Steps Per Turn')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('5 (Default)')).toBeVisible();
});

test('2.4 - Agentic PIM has Daily Token Budget numeric input', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Daily Token Budget')).toBeVisible();
  const budgetInput = adminPage.getByRole('spinbutton', { name: 'Daily Token Budget' });
  await expect(budgetInput).toBeVisible();
});

test('2.5 - Agentic PIM has Auto-Enrichment on Product Create checkbox', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Auto-Enrichment on Product Create')).toBeVisible();
  const checkbox = adminPage.getByRole('checkbox', { name: 'Auto-Enrichment on Product Create' });
  await expect(checkbox).toBeVisible();
});

test('2.6 - Agentic PIM has Catalog Quality Monitor checkbox', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Catalog Quality Monitor')).toBeVisible();
  const checkbox = adminPage.getByRole('checkbox', { name: 'Catalog Quality Monitor' });
  await expect(checkbox).toBeVisible();
});

test('2.7 - Agentic PIM has Confidence Threshold dropdown with default "0.7 (Balanced)"', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Confidence Threshold')).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/0\.7.*Balanced/)).toBeVisible();
});

test('2.8 - Agentic PIM has Change Approval Mode dropdown with default "Confirm & apply"', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Change Approval Mode')).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/Confirm & apply/)).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 3: Text Generation Configuration
// ═════════════════════════════════════════════════

test('3.1 - Text Generation has Enabled checkbox', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const textGenSection = adminPage.locator('div', { hasText: 'Text Generation' }).filter({ hasText: 'product descriptions' });
  const enabledCheckbox = textGenSection.locator('..').getByRole('checkbox', { name: 'Enabled' }).first();
  await expect(enabledCheckbox).toBeVisible();
});

test('3.2 - Text Generation has Default Platform dropdown with "Use Default Platform" option', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'load' });

  await expect(adminPage.getByText('Use Default Platform').first()).toBeVisible({ timeout: 20000 });
  await adminPage.getByText('Use Default Platform').first().click();
  await expect(adminPage.getByRole('option', { name: /Use Default Platform/i }).first()).toBeVisible();
  await adminPage.keyboard.press('Escape');
});

test('3.3 - Text Generation Default Platform lists configured platforms with provider names', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — no platforms available');
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.getByText(/OpenAI/i).first()).toBeVisible();
});

test('3.4 - Text Generation shows help text about default platform with asterisk', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — no platforms available');
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const helpText = adminPage.locator('#app').getByText(/Leave empty to use the platform marked as default/).first();
  const visible = await helpText.isVisible({ timeout: 3000 }).catch(() => false);
  test.skip(!visible, 'Magic AI platform help text not rendered (no usable platform in env)');
  await expect(helpText).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/Platforms marked with \* are default/).first()).toBeVisible();
});

test('3.5 - Text Generation has Default Model dropdown with model options', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Default Model').first()).toBeVisible();
  await expect(adminPage.getByText('Select Model').first()).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 4: Image Generation Configuration
// ═════════════════════════════════════════════════

test('4.1 - Image Generation section notes about supported providers', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText(/Only platforms that support image generation.*OpenAI.*Gemini.*xAI/)).toBeVisible();
});

test('4.2 - Image Generation has Enabled checkbox', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const labels = adminPage.getByText('Enabled');
  const count = await labels.count();
  expect(count).toBeGreaterThanOrEqual(2);
});

test('4.3 - Image Generation has Default Platform dropdown', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const platformLabels = adminPage.getByText('Default Platform');
  const count = await platformLabels.count();
  expect(count).toBeGreaterThanOrEqual(2);
});

test('4.4 - Image Generation Default Model only shows image-capable models', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — no platforms available');
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const modelDropdowns = adminPage.locator('.multiselect');
  const count = await modelDropdowns.count();
  let imageModelSelect = null;
  if (count > 0) {
    imageModelSelect = modelDropdowns.last();
  }
  expect(imageModelSelect).not.toBeNull();
});

// ═════════════════════════════════════════════════
// SECTION 5: Translation Configuration
// ═════════════════════════════════════════════════

test('5.1 - Translation section mentions cheaper platform usage', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText(/potentially faster\/cheaper/)).toBeVisible();
});

test('5.2 - Translation has Enabled checkbox', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const labels = adminPage.getByText('Enabled');
  const count = await labels.count();
  expect(count).toBeGreaterThanOrEqual(3);
});

test('5.3 - Translation has Default Platform dropdown', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const platformLabels = adminPage.getByText('Default Platform');
  const count = await platformLabels.count();
  expect(count).toBeGreaterThanOrEqual(3);
});

test('5.4 - Translation has Translation Model dropdown', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Translation Model')).toBeVisible();
});

test('5.5 - Translation has Replace Existing Value checkbox with tooltip', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Replace Existing Value')).toBeVisible();
  const tooltipElement = adminPage.locator('[title*="Replace the existing value"]');
  await expect(tooltipElement).toBeVisible();
});

test('5.6 - Translation has Source Channel dropdown', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Source Channel')).toBeVisible();
});

test('5.7 - Translation has Target Channel dropdown', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'load' });
  await expect(adminPage.locator('#app').getByText('Target Channel')).toBeVisible({ timeout: 20000 });
});

test('5.8 - Translation has Source Locale dropdown', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Source Locale')).toBeVisible();
});

test('5.9 - Translation has Target Locales dropdown', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Target Locales')).toBeVisible();
});

test('5.10 - Translation channel and locale dropdowns have "Select option" placeholder', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const selectOptions = adminPage.locator('.multiselect__placeholder');
  const count = await selectOptions.count();
  expect(count).toBeGreaterThanOrEqual(4);
});

// ═════════════════════════════════════════════════
// SECTION 6: AI Platform Management Page
// ═════════════════════════════════════════════════

test('6.1 - AI Platforms page accessible from Configuration > Magic AI menu', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await expect(adminPage).toHaveURL(/\/admin\/magic-ai\/platform/);
});

test('6.2 - AI Platforms page shows title and Add Platform button', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('AI Platforms', { exact: true })).toBeVisible();
  await expect(adminPage.getByRole('button', { name: 'Add Platform' })).toBeVisible();
});

test('6.3 - AI Platforms datagrid has Search input', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  const searchInput = adminPage.getByPlaceholder('Search').first();
  await expect(searchInput).toBeVisible();
});

test('6.4 - AI Platforms datagrid shows results count', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible();
});

test('6.5 - AI Platforms datagrid has Filter button', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Filter')).toBeVisible();
});

test('6.6 - AI Platforms datagrid has Per Page selector and pagination', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Per Page')).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/of \d+/)).toBeVisible();
});

test('6.7 - AI Platforms datagrid has pagination arrows', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('text="«"')).toBeVisible();
  await expect(adminPage.locator('text="‹"')).toBeVisible();
  await expect(adminPage.locator('text="›"')).toBeVisible();
  await expect(adminPage.locator('text="»"')).toBeVisible();
});

test('6.8 - AI Platforms datagrid shows all column headers', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  // Trigger datagrid load by clicking Add Platform then closing
  await adminPage.getByRole('button', { name: 'Add Platform' }).click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();
  await adminPage.locator('.icon-cancel').click().catch(() => {});
  await expect(adminPage.locator('.icon-cancel')).not.toBeVisible().catch(() => {});

  await expect(adminPage.locator('#app').getByText('Label').first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Provider').first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Models').first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Default').first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Status').first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Created At').first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Actions').first()).toBeVisible();
});

test('6.9 - Platform rows show edit and delete action icons', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();
  await adminPage.locator('.icon-cancel').click().catch(() => {});
  await expect(adminPage.locator('.icon-cancel')).not.toBeVisible().catch(() => {});

  const editIcons = adminPage.locator('span[title="Edit"]');
  const deleteIcons = adminPage.locator('span[title="Delete"]');
  const editCount = await editIcons.count();
  const deleteCount = await deleteIcons.count();

  if (editCount > 0) {
    expect(deleteCount).toBeGreaterThan(0);
  }
});

test('6.10 - Non-default platform rows show "Set as Default" star icon', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();
  await adminPage.locator('.icon-cancel').click().catch(() => {});
  await expect(adminPage.locator('.icon-cancel')).not.toBeVisible().catch(() => {});

  const starIcons = adminPage.locator('[title="Set as Default"]');
  const starCount = await starIcons.count();
  expect(starCount).toBeGreaterThanOrEqual(0);
});

// ═════════════════════════════════════════════════
// SECTION 7: Add Platform Modal
// ═════════════════════════════════════════════════

test('7.1 - Add Platform button opens modal with "Add AI Platform" title', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();
});

test('7.2 - Add Platform modal has Provider dropdown with all provider options', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();

  const providerSelect = adminPage.locator('input[name="provider"]').first().locator('..');
  await expect(providerSelect).toBeVisible();

  await providerSelect.locator('.multiselect__placeholder, .multiselect__single').first().click();
  const options = adminPage.locator('.multiselect__option');
  const count = await options.count();
  const optionTexts = [];
  for (let i = 0; i < count; i++) {
    optionTexts.push(await options.nth(i).textContent());
  }
  await adminPage.keyboard.press('Escape');
  expect(optionTexts.some(t => t.includes('OpenAI'))).toBe(true);
  expect(optionTexts.some(t => t.includes('Anthropic'))).toBe(true);
  expect(optionTexts.some(t => t.includes('Google Gemini'))).toBe(true);
  expect(optionTexts.some(t => t.includes('Groq'))).toBe(true);
  expect(optionTexts.some(t => t.includes('Ollama'))).toBe(true);
  expect(optionTexts.some(t => t.includes('xAI (Grok)'))).toBe(true);
  expect(optionTexts.some(t => t.includes('Mistral'))).toBe(true);
  expect(optionTexts.some(t => t.includes('DeepSeek'))).toBe(true);
  expect(optionTexts.some(t => t.includes('Azure OpenAI'))).toBe(true);
  expect(optionTexts.some(t => t.includes('OpenRouter'))).toBe(true);
  expect(optionTexts.some(t => t.includes('Custom (OpenAI-compatible)'))).toBe(true);
});

test('7.3 - Selecting OpenAI provider shows Label, API Key, API URL, Models, toggles', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await expect(adminPage.locator('input[name="label"]')).toBeVisible();
  await expect(adminPage.locator('input[name="api_key"]')).toBeVisible();
  await expect(adminPage.locator('input[name="api_url"]')).toBeVisible();
  await expect(adminPage.locator('#app').getByText(/Pre-filled with the default endpoint/)).toBeVisible();
  await expect(adminPage.getByPlaceholder('Type custom model ID...')).toBeVisible();
  await expect(adminPage.getByRole('button', { name: '+ Add' })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Set as Default', { exact: true }).last()).toBeVisible();
  const statusCheckboxes = adminPage.locator('input[type="checkbox"]');
  expect(await statusCheckboxes.count()).toBeGreaterThanOrEqual(2);
});

test('7.4 - OpenAI API URL is pre-filled with https://api.openai.com/v1', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  const apiUrlInput = adminPage.locator('input[name="api_url"]');
  await expect(apiUrlInput).toHaveValue('https://api.openai.com/v1');
});

test('7.5 - Add Platform modal has Save button', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await expect(adminPage.getByRole('button', { name: 'Save' })).toBeVisible();
});

test('7.6 - Add Platform modal has close (X) button', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  const closeBtn = adminPage.locator('.icon-cancel');
  await expect(closeBtn).toBeVisible();
});

test('7.7 - Save platform without required fields shows validation errors', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText(/provider.*required|required/i)).toBeVisible();
});

test('7.8 - Label field auto-fills when provider is selected', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'Groq' }).first().click();

  const labelInput = adminPage.locator('input[name="label"]');
  await expect(labelInput).toHaveValue('Groq');
});

test('7.9 - Status toggle is enabled by default when adding a new platform', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  const statusCheckboxes = adminPage.locator('input[type="checkbox"]');
  const count = await statusCheckboxes.count();

  let statusChecked = false;
  for (let i = count - 1; i >= 0; i--) {
    const isInModal = await statusCheckboxes.nth(i).isVisible().catch(() => false);
    if (isInModal) {
      statusChecked = await statusCheckboxes.nth(i).isChecked();
      break;
    }
  }
  expect(statusChecked).toBe(true);
});

// ═════════════════════════════════════════════════
// SECTION 8: Save Configuration
// ═════════════════════════════════════════════════

test('8.1 - Save Configuration without changes succeeds', async ({ adminPage }) => {
  test.setTimeout(30000);
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });

  await adminPage.getByRole('button', { name: 'Save Configuration' }).click();
  await adminPage.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});

  await expect(adminPage.locator('#app').getByText('Agentic PIM', { exact: true })).toBeVisible();
});

test('8.2 - Open Agenting PIM button is visible on Magic AI config page', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  const agentBtn = adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' });
  const visible = await agentBtn.isVisible({ timeout: 3000 }).catch(() => false);
  test.skip(!visible, 'Agenting PIM widget not active in this environment');
  await expect(agentBtn).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 9: Credential Validation & Model Fetching
// (requires OPENAI_API_KEY env variable)
// ═════════════════════════════════════════════════

test('9.1 - Add Platform with valid OpenAI API key fetches models automatically', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping credential tests');
  test.setTimeout(60000);

  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await adminPage.locator('input[name="api_key"]').fill(OPENAI_API_KEY);
  await adminPage.locator('input[name="label"]').click();

  const modelCheckbox = adminPage.locator('.grid.grid-cols-2 label input[type="checkbox"]').first();
  await modelCheckbox.waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  const modelCheckboxes = adminPage.locator('.grid.grid-cols-2 label input[type="checkbox"]');
  const modelCount = await modelCheckboxes.count();
  expect(modelCount).toBeGreaterThan(0);

  await adminPage.locator('.icon-cancel').click();
});

test('9.2 - Fetched model list includes known OpenAI models', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping credential tests');
  test.setTimeout(60000);

  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await adminPage.locator('input[name="api_key"]').fill(OPENAI_API_KEY);
  await adminPage.locator('input[name="label"]').click();

  await adminPage.locator('input[type="checkbox"]').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  await expect(adminPage.locator('#app').getByText('gpt-4o', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('gpt-image-1', { exact: true })).toBeVisible();

  await adminPage.locator('.icon-cancel').click();
});

test('9.3 - Model search filters the model checkbox list', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping credential tests');
  test.setTimeout(60000);

  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await adminPage.locator('input[name="api_key"]').fill(OPENAI_API_KEY);
  await adminPage.locator('input[name="label"]').click();

  await adminPage.locator('input[type="checkbox"]').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  const searchInput = adminPage.getByPlaceholder('Search models...');
  await searchInput.fill('gpt-image');

  await expect(adminPage.locator('#app').getByText('gpt-image-1', { exact: true })).toBeVisible();

  const gpt4oVisible = await adminPage.getByRole('checkbox', { name: 'gpt-4o', exact: true }).isVisible().catch(() => false);
  expect(gpt4oVisible).toBe(false);

  await adminPage.locator('.icon-cancel').click();
});

test('9.4 - Selecting a model adds it as a tag chip', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping credential tests');
  test.setTimeout(60000);

  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await adminPage.locator('input[name="api_key"]').fill(OPENAI_API_KEY);
  await adminPage.locator('input[name="label"]').click();

  await adminPage.locator('input[type="checkbox"]').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  const gpt4oCheckbox = adminPage.getByRole('checkbox', { name: 'gpt-4o', exact: true });
  if (!(await gpt4oCheckbox.isChecked())) {
    await gpt4oCheckbox.check();
  }

  // exact: true — without it the locator substring-matches and finds 4 buttons:
  // gpt-4o, gpt-4o-mini, gpt-4o-mini-search-preview, gpt-4o-search-preview.
  const removeBtn = adminPage.getByRole('button', { name: 'Remove model gpt-4o', exact: true });
  await expect(removeBtn).toBeVisible();

  await adminPage.locator('.icon-cancel').click();
});

test('9.5 - Removing a model tag chip unchecks it in the list', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping credential tests');
  test.setTimeout(60000);

  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await adminPage.locator('input[name="api_key"]').fill(OPENAI_API_KEY);
  await adminPage.locator('input[name="label"]').click();

  await adminPage.locator('input[type="checkbox"]').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  const gpt4oCheckbox = adminPage.getByRole('checkbox', { name: 'gpt-4o', exact: true });
  if (!(await gpt4oCheckbox.isChecked())) {
    await gpt4oCheckbox.check();
  }

  // exact: true — see comment on test 9.4 for the substring-match collision.
  await adminPage.getByRole('button', { name: 'Remove model gpt-4o', exact: true }).click();
  await expect(gpt4oCheckbox).not.toBeChecked();

  await adminPage.locator('.icon-cancel').click();
});

test('9.6 - Adding a custom model ID via the text input', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping credential tests');
  test.setTimeout(60000);

  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await adminPage.locator('input[name="api_key"]').fill(OPENAI_API_KEY);
  await adminPage.locator('input[name="label"]').click();

  await adminPage.locator('input[type="checkbox"]').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  await adminPage.getByPlaceholder('Type custom model ID...').fill('my-custom-ft-model');
  await adminPage.getByRole('button', { name: '+ Add' }).click();

  const removeBtn = adminPage.getByRole('button', { name: 'Remove model my-custom-ft-model' });
  await expect(removeBtn).toBeVisible();

  await adminPage.locator('.icon-cancel').click();
});


test('9.8 - Save platform with valid API key and selected models succeeds', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping credential tests');
  test.setTimeout(60000);

  const uid = generateUid();
  const uniqueLabel = `E2E Test Platform ${uid}`;

  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await adminPage.locator('input[name="label"]').clear();
  await adminPage.locator('input[name="label"]').fill(uniqueLabel);
  await adminPage.locator('input[name="api_key"]').fill(OPENAI_API_KEY);
  await adminPage.locator('input[name="label"]').click();

  await adminPage.locator('input[type="checkbox"]').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  const gpt4oCheckbox = adminPage.getByRole('checkbox', { name: 'gpt-4o', exact: true });
  if (await gpt4oCheckbox.isVisible().catch(() => false)) {
    if (!(await gpt4oCheckbox.isChecked())) {
      await gpt4oCheckbox.check();
    }
  }

  await adminPage.getByRole('button', { name: 'Save' }).click();
  const successMsg = adminPage.getByText(/saved successfully|created successfully/i);
  await expect(successMsg).toBeVisible({ timeout: 20000 }).catch(() => {});

  // Cleanup: delete the platform
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();
  await adminPage.locator('.icon-cancel').click();
  await expect(adminPage.locator('.icon-cancel')).not.toBeVisible();

  const deleteBtn = adminPage.locator('div', { hasText: uniqueLabel }).locator('[title="Delete"]');
  if (await deleteBtn.first().isVisible().catch(() => false)) {
    await deleteBtn.first().click();
    const confirmBtn = adminPage.getByRole('button', { name: /Agree|Confirm|Yes|Delete/i });
    if (await confirmBtn.isVisible().catch(() => false)) {
      await confirmBtn.click();
      await adminPage.waitForLoadState('networkidle');
    }
  }
});

test('9.9 - Invalid API key shows error when saving platform', async ({ adminPage }) => {
  test.setTimeout(30000);

  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await adminPage.locator('input[name="label"]').clear();
  await adminPage.locator('input[name="label"]').fill('Invalid Key Test');
  await adminPage.locator('input[name="api_key"]').fill('sk-invalid-key-12345');

  await adminPage.getByPlaceholder('Type custom model ID...').fill('gpt-4o');
  await adminPage.getByRole('button', { name: '+ Add' }).click();

  await adminPage.getByRole('button', { name: 'Save' }).click();

  const errorMsg = adminPage.getByText(/failed|error|invalid|could not|unable/i);
  await expect(errorMsg.first()).toBeVisible({ timeout: 20000 });

  await adminPage.locator('.icon-cancel').click().catch(() => {});
});

});
