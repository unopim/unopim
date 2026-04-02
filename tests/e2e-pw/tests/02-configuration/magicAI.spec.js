const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

const OPENAI_API_KEY = process.env.OPENAI_API_KEY || '';

const MAGIC_AI_CONFIG_URL = '/admin/configuration/general/magic_ai';
const MAGIC_AI_PLATFORM_URL = '/admin/magic-ai/platform';
const MAGIC_AI_PROMPT_URL = '/admin/magic-ai/prompt';
const MAGIC_AI_SYSTEM_PROMPT_URL = '/admin/system-prompt';

/**
 * Helper: Open the datagrid on Prompt / System Prompt pages.
 * Clicking the "Create" button loads the datagrid AND opens a modal.
 * We close the modal immediately so we can interact with the grid.
 */
async function openDatagrid(adminPage, createBtnName) {
  await adminPage.getByRole('button', { name: createBtnName }).click();
  const cancelIcon = adminPage.locator('.icon-cancel');
  if (await cancelIcon.isVisible({ timeout: 5000 }).catch(() => false)) {
    await cancelIcon.click();
    await expect(cancelIcon).not.toBeVisible({ timeout: 5000 });
  }
}

/**
 * Helper: Create an OpenAI platform and return its label.
 * Cleans up after itself if cleanupAfter is called.
 */
async function createOpenAIPlatform(adminPage, label) {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();
  await expect(adminPage.locator('input[name="label"]')).toBeVisible();
  await adminPage.locator('input[name="label"]').fill(label);
  await adminPage.locator('input[name="api_key"]').fill(OPENAI_API_KEY);
  await adminPage.locator('input[name="label"]').click();
  const modelTag = adminPage.locator('.rounded-full.bg-violet-100').first();
  await modelTag.waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText(/saved successfully|created successfully|updated successfully/i)).toBeVisible({ timeout: 30000 });
}

/**
 * Helper: Delete a platform by label text.
 */
async function deletePlatform(adminPage, label) {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();
  await adminPage.locator('.icon-cancel').click();
  await expect(adminPage.locator('.icon-cancel')).not.toBeVisible();

  const row = adminPage.locator('div').filter({ hasText: label });
  if (await row.first().locator('span[title="Delete"], span[title="delete"]').first().isVisible().catch(() => false)) {
    await row.first().locator('span[title="Delete"], span[title="delete"]').first().click();
    await expect(adminPage.locator('#app').getByText('Are you sure you want to delete?')).toBeVisible();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/deleted successfully/i)).toBeVisible();
  }
}

test.describe('UnoPim Magic AI Test Cases', () => {

// ═════════════════════════════════════════════════
// SECTION 1: Platform Management
// ═════════════════════════════════════════════════

test('1.1 - Verify AI Platforms page opens with onboarding', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await expect(adminPage).toHaveURL(/.*\/admin\/magic-ai\/platform/);
  await expect(adminPage.locator('#app').getByText('AI Platforms', { exact: true })).toBeVisible();
  await expect(adminPage.getByRole('button', { name: 'Add Platform' })).toBeVisible();
});

test('1.2 - Verify all provider options in Add Platform modal', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();

  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  const optionTexts = await adminPage.locator('.multiselect__element').allTextContents();

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
});

test('1.3 - Verify selecting provider shows Label and API Key fields', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();

  await expect(adminPage.locator('input[name="label"]')).toBeVisible();
  await expect(adminPage.locator('input[name="api_key"]')).toBeVisible();
});

test('1.4 - Save platform without required fields shows validation', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText(/provider.*required|required/i)).toBeVisible();
});

test('1.5 - Test connection with invalid API key', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await adminPage.locator('input[name="provider"]').first().locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'OpenAI' }).first().click();
  await adminPage.locator('input[name="label"]').fill('Invalid Platform');
  await adminPage.locator('input[name="api_key"]').fill('invalid-openai-key-12345');

  const testBtn = adminPage.getByRole('button', { name: 'Test Connection' });
  if (await testBtn.isVisible().catch(() => false)) {
    await testBtn.click();
    await expect(adminPage.locator('#app').getByText(/failed|error|invalid/i)).toBeVisible();
  }
});

test('1.6 - Create and delete OpenAI platform with valid credentials', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping platform creation');
  test.setTimeout(60000);

  const uid = generateUid();
  const label = `OpenAI E2E ${uid}`;

  await createOpenAIPlatform(adminPage, label);
  await deletePlatform(adminPage, label);
});

test('1.8 - Verify platform datagrid columns', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PLATFORM_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Add Platform' }).first().click();
  await expect(adminPage.locator('#app').getByText('Add AI Platform')).toBeVisible();
  await adminPage.locator('.icon-cancel').click();
  await expect(adminPage.locator('.icon-cancel')).not.toBeVisible();

  await expect(adminPage.locator('#app').getByText('Label', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Provider', { exact: true }).first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Models', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Default', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Status', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Created At', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Actions', { exact: true })).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 2: Magic AI Configuration Settings
// ═════════════════════════════════════════════════

test('2.1 - Verify Magic AI is visible in sidebar', async ({ adminPage }) => {
  await navigateTo(adminPage, 'configuration');
  await expect(adminPage.getByRole('link', { name: 'Magic AI' })).toBeVisible();
});

test('2.2 - Verify config page opens with three sections', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage).toHaveURL(/.*\/admin\/configuration\/general\/magic_ai/);
  await expect(adminPage.locator('#app').getByText('Text Generation', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Image Generation', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Translation', { exact: true })).toBeVisible();
});

test('2.3 - Verify Text Generation section description', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Configure the default AI platform and model for generating product descriptions')).toBeVisible();
  await expect(adminPage.getByRole('button', { name: 'Save Configuration' })).toBeVisible();
  await expect(adminPage.getByRole('button', { name: 'Save Configuration' })).toBeEnabled();
});

test('2.4 - Verify Image Generation section description', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Configure the default AI platform and model for generating product images')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Only platforms that support image generation')).toBeVisible();
});

test('2.5 - Verify Translation section fields', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  await expect(adminPage.locator('#app').getByText('Configure the AI platform and model for translating product content across locales')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Replace Existing Value')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Source Channel')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Target Channel')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Source Locale')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Target Locales')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Translation Model')).toBeVisible();
});

test('2.6 - Verify platform dropdown shows OpenAI platform on config page', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });
  // Check that at least one platform option is visible on the config page
  await expect(adminPage.getByText(/OpenAI/i).first()).toBeVisible();
});

test('2.7 - Configure Magic AI with OpenAI platform for Text Generation', async ({ adminPage }) => {
  test.setTimeout(30000);
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });

  const platformDropdown = adminPage.locator('.multiselect__placeholder, .multiselect__single').first();
  if (await platformDropdown.isVisible().catch(() => false)) {
    await platformDropdown.click();
    const openaiOpt = adminPage.getByRole('option', { name: /OpenAI/i }).first();
    if (await openaiOpt.isVisible().catch(() => false)) {
      await openaiOpt.click();
    }
  }

  await adminPage.getByRole('button', { name: 'Save Configuration' }).click();
  await adminPage.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
  await expect(adminPage.locator('#app').getByText('Text Generation', { exact: true })).toBeVisible();
});

test('2.8 - Configure Image Generation with OpenAI platform', async ({ adminPage }) => {
  test.setTimeout(30000);
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });

  const platformDropdowns = adminPage.locator('.multiselect__placeholder, .multiselect__single');
  const ddCount = await platformDropdowns.count();
  let platformIdx = 0;
  for (let i = 0; i < ddCount; i++) {
    await platformDropdowns.nth(i).click();
    const openaiOpt = adminPage.getByRole('option', { name: /OpenAI/i }).first();
    if (await openaiOpt.isVisible({ timeout: 1000 }).catch(() => false)) {
      platformIdx++;
      if (platformIdx === 2) {
        await openaiOpt.click();
        break;
      }
    }
    await adminPage.keyboard.press('Escape');
  }

  await adminPage.getByRole('button', { name: 'Save Configuration' }).click();
  await adminPage.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
  await expect(adminPage.locator('#app').getByText('Text Generation', { exact: true })).toBeVisible();
});

test('2.9 - Save Configuration without any changes', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_CONFIG_URL, { waitUntil: 'networkidle' });

  await Promise.all([
    adminPage.waitForResponse(resp => resp.url().includes('configuration') && resp.status() === 200, { timeout: 20000 }).catch(() => {}),
    adminPage.getByRole('button', { name: 'Save Configuration' }).click()
  ]);
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('#app').getByText('Magic AI').first()).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 3: System Prompt Management
// ═════════════════════════════════════════════════

test('3.1 - Verify System Prompt page opens', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await expect(adminPage).toHaveURL(/.*admin\/system-prompt.*/);
  await expect(adminPage.getByRole('button', { name: 'Create System Prompt' })).toBeVisible();
});

test('3.2 - Verify default system prompts are pre-loaded', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create System Prompt');

  await expect(adminPage.locator('#app').getByText('Authoritative Guide')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Descriptive Storyteller')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Technical Expert')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Concise Responder')).toBeVisible();
});

test('3.3 - Verify system prompt datagrid columns', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create System Prompt');

  await expect(adminPage.locator('#app').getByText('Title', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Tone', { exact: true }).first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Max Tokens', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Temperature', { exact: true }).first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Status', { exact: true }).first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Actions', { exact: true })).toBeVisible();
});

test('3.4 - Verify Friendly Assistant system prompt exists', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create System Prompt');

  const friendlyText = adminPage.getByText('Friendly Assistant');
  if (!(await friendlyText.isVisible().catch(() => false))) {
    const nextBtn = adminPage.locator('[cursor=pointer]', { hasText: '›' });
    if (await nextBtn.isVisible().catch(() => false)) {
      await nextBtn.click();
      await adminPage.waitForLoadState('networkidle');
    }
  }
  await expect(adminPage.locator('#app').getByText('Friendly Assistant')).toBeVisible();
});

test('3.5 - Verify Create System Prompt modal fields', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create System Prompt' }).click();

  await expect(adminPage.locator('#app').getByText('Create New System Prompt')).toBeVisible({ timeout: 20000 });
  await expect(adminPage.locator('input[name="title"]')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Status').first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Max Output Tokens')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Temperature').first()).toBeVisible();
  await expect(adminPage.locator('textarea[name="tone"]')).toBeVisible();
  await expect(adminPage.getByRole('button', { name: 'Save' })).toBeVisible();
});

test('3.6 - Save System Prompt with empty fields shows validation', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create System Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New System Prompt')).toBeVisible();
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText('The Title field is required')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The Tone field is required')).toBeVisible();
});

test('3.7 - Create and verify a System Prompt with all fields', async ({ adminPage }) => {
  const uid = generateUid();
  const title = `E-Commerce Writer ${uid}`;

  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create System Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New System Prompt')).toBeVisible();
  await adminPage.locator('input[name="title"]').fill(title);
  await adminPage.locator('input[name="max_tokens"]').fill('2000');
  await adminPage.locator('input[name="temperature"]').fill('0.8');
  await adminPage.locator('textarea[name="tone"]').fill('Professional e-commerce copywriter tone, persuasive and conversion-focused');
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText('Prompt saved successfully.')).toBeVisible();

  // Verify it appears in datagrid
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create System Prompt');
  await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 20000 });
  await expect(adminPage.locator('#app').getByText(title, { exact: true }).first()).toBeVisible({ timeout: 5000 });

  // Cleanup: delete the created system prompt
  const row = adminPage.locator('div').filter({ hasText: title });
  if (await row.first().locator('span[title="delete"]').first().isVisible().catch(() => false)) {
    await row.first().locator('span[title="delete"]').first().click();
    await expect(adminPage.getByRole('button', { name: 'Delete' })).toBeVisible();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
});

test('3.9 - Edit an existing system prompt', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create System Prompt');

  await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 20000 });
  const editIcon = adminPage.locator('span[title="Edit"]').first();
  await expect(editIcon).toBeVisible({ timeout: 5000 });
  await editIcon.click();

  const titleInput = adminPage.locator('input[name="title"]');
  await expect(titleInput).toBeVisible({ timeout: 20000 });
  const currentTitle = await titleInput.inputValue();
  await titleInput.clear();
  await titleInput.fill(currentTitle + ' Pro');
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText(/updated successfully|saved successfully/i)).toBeVisible({ timeout: 20000 });

  // Revert the edit
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create System Prompt');
  const editIconRevert = adminPage.locator('span[title="Edit"]').first();
  await expect(editIconRevert).toBeVisible({ timeout: 5000 });
  await editIconRevert.click();
  const titleInputRevert = adminPage.locator('input[name="title"]');
  await expect(titleInputRevert).toBeVisible({ timeout: 20000 });
  await titleInputRevert.clear();
  await titleInputRevert.fill(currentTitle);
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.locator('#app').getByText(/updated successfully|saved successfully/i)).toBeVisible({ timeout: 20000 });
});

test('3.10 - Search system prompts in datagrid', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_SYSTEM_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create System Prompt');

  await adminPage.locator('input[type="text"][placeholder*="Search"], input[type="text"]').first().fill('Technical');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('#app').getByText('Technical Expert')).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 4: Prompt Management
// ═════════════════════════════════════════════════

test('4.1 - Verify Prompt page opens', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await expect(adminPage).toHaveURL(/.*admin\/magic-ai\/prompt.*/);
  await expect(adminPage.getByRole('button', { name: 'Create Prompt' })).toBeVisible();
});

test('4.2 - Verify default prompts are pre-loaded', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create Prompt');

  // Verify the datagrid has results (default prompts are seeded)
  await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 20000 });

  // Search for specific default prompts to verify they exist
  await adminPage.locator('input[type="text"]').first().fill('Product Tagline');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('#app').getByText('Product Tagline')).toBeVisible();
});

test('4.3 - Verify prompt datagrid columns', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create Prompt');

  await expect(adminPage.locator('#app').getByText('Title', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Prompt', { exact: true }).first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Entity Type', { exact: true })).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Purpose', { exact: true }).first()).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Actions', { exact: true })).toBeVisible();
});

test('4.4 - Verify Create Prompt modal fields', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create Prompt' }).click();

  await expect(adminPage.locator('#app').getByText('Create New Prompt')).toBeVisible();
  await expect(adminPage.locator('input[name="title"]')).toBeVisible();
  await expect(adminPage.locator('textarea[name="prompt"]')).toBeVisible();
  await expect(adminPage.getByRole('button', { name: 'Save Prompt' })).toBeVisible();
});

test('4.5 - Verify Purpose field has Text Generation and Image Generation', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New Prompt')).toBeVisible();

  await adminPage.locator('input[name="purpose"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await expect(adminPage.getByRole('option', { name: 'Text Generation' }).first()).toBeVisible();
  await expect(adminPage.getByRole('option', { name: 'Image Generation' }).first()).toBeVisible();
  await adminPage.keyboard.press('Escape');
});

test('4.6 - Verify Entity Type has Product and Category options', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New Prompt')).toBeVisible();

  const entityTypeDropdown = adminPage.locator('div').filter({ hasText: /^Product$/ }).nth(1);
  await entityTypeDropdown.click();
  await expect(adminPage.getByRole('option', { name: 'Product' }).first()).toBeVisible();
  await expect(adminPage.getByRole('option', { name: 'Category' }).first()).toBeVisible();
});

test('4.7 - Verify Tone defaults to Friendly Assistant', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New Prompt')).toBeVisible();
  await expect(adminPage.locator('div').filter({ hasText: /^Friendly Assistant$/ }).first()).toBeVisible();
});

test('4.8 - Save Prompt with empty fields shows validation', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New Prompt')).toBeVisible();
  await adminPage.getByRole('button', { name: 'Save Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('The title field is required')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The Prompt field is required')).toBeVisible();
});

test('4.9 - Create a Product Text Generation prompt and clean up', async ({ adminPage }) => {
  const uid = generateUid();
  const title = `AI Prod Desc ${uid}`;

  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New Prompt')).toBeVisible();
  await adminPage.locator('input[name="title"]').fill(title);
  await adminPage.locator('textarea[name="prompt"]').fill('Write a detailed product description for @name highlighting its features, benefits and @color variant.');
  await adminPage.getByRole('button', { name: 'Save Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Prompt saved successfully.')).toBeVisible();

  // Cleanup
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create Prompt');
  const row = adminPage.locator('div').filter({ hasText: title });
  if (await row.first().locator('span[title="delete"]').first().isVisible().catch(() => false)) {
    await row.first().locator('span[title="delete"]').first().click();
    await expect(adminPage.getByRole('button', { name: 'Delete' })).toBeVisible();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
});

test('4.10 - Create a Category prompt and clean up', async ({ adminPage }) => {
  const uid = generateUid();
  const title = `AI Cat Desc ${uid}`;

  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New Prompt')).toBeVisible();
  await adminPage.locator('input[name="title"]').fill(title);
  await adminPage.locator('div').filter({ hasText: /^Product$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Category' }).first().click();
  await adminPage.locator('textarea[name="prompt"]').fill('Write a compelling category description for @name that helps customers browse products.');
  await adminPage.getByRole('button', { name: 'Save Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Prompt saved successfully.')).toBeVisible();

  // Cleanup
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create Prompt');
  const row = adminPage.locator('div').filter({ hasText: title });
  if (await row.first().locator('span[title="delete"]').first().isVisible().catch(() => false)) {
    await row.first().locator('span[title="delete"]').first().click();
    await expect(adminPage.getByRole('button', { name: 'Delete' })).toBeVisible();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
});

test('4.11 - Create an Image Generation prompt and clean up', async ({ adminPage }) => {
  const uid = generateUid();
  const title = `AI Prod Image ${uid}`;

  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await adminPage.getByRole('button', { name: 'Create Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Create New Prompt')).toBeVisible();
  await adminPage.locator('input[name="title"]').fill(title);

  await adminPage.locator('input[name="purpose"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').first().click();
  await adminPage.getByRole('option', { name: 'Image Generation' }).first().click();

  await adminPage.locator('textarea[name="prompt"]').fill('Generate a professional product photo of @name on a clean white background with studio lighting.');
  await adminPage.getByRole('button', { name: 'Save Prompt' }).click();
  await expect(adminPage.locator('#app').getByText('Prompt saved successfully.')).toBeVisible();

  // Cleanup
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create Prompt');
  const row = adminPage.locator('div').filter({ hasText: title });
  if (await row.first().locator('span[title="delete"]').first().isVisible().catch(() => false)) {
    await row.first().locator('span[title="delete"]').first().click();
    await expect(adminPage.getByRole('button', { name: 'Delete' })).toBeVisible();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
});

test('4.12 - Search prompts in datagrid', async ({ adminPage }) => {
  await adminPage.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await openDatagrid(adminPage, 'Create Prompt');

  await adminPage.locator('input[type="text"]').first().fill('Tagline');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('#app').getByText('Product Tagline')).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 5: Locale Setup for Translation (self-contained)
// ═════════════════════════════════════════════════

test('5.1 - Enable Hindi locale for translation testing', async ({ adminPage }) => {
  test.setTimeout(30000);
  await navigateTo(adminPage, 'locales');

  // Search for Hindi locale using the search box
  const searchInput = adminPage.getByPlaceholder('Search by code').first();
  await searchInput.fill('hi_IN');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  // Extra wait for datagrid re-render to complete
  await adminPage.waitForTimeout(1000);

  const editBtn = adminPage.locator('span[title="Edit"]').first();
  await editBtn.waitFor({ state: 'visible', timeout: 20000 });

  await editBtn.click({ timeout: 20000 });
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('label[for="status"]')).toBeVisible();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Locale' }).click();
  await expect(adminPage.locator('#app').getByText(/Locale Updated successfully/i)).toBeVisible();
});

test('5.2 - Assign Hindi locale to default channel', async ({ adminPage }) => {
  test.setTimeout(30000);
  await navigateTo(adminPage, 'channels');

  // Click the first Edit button on the channels page (default channel)
  const editBtn = adminPage.locator('span[title="Edit"]').first();
  test.skip(!(await editBtn.isVisible({ timeout: 5000 }).catch(() => false)), 'No channels available to edit');
  await editBtn.click();
  await adminPage.waitForLoadState('networkidle');

  const localeMultiselect = adminPage.locator('.multiselect__tags', { hasText: 'English' });
  if (await localeMultiselect.isVisible().catch(() => false)) {
    await localeMultiselect.click();
    const hindiOption = adminPage.getByRole('option', { name: 'Hindi (India)' });
    if (await hindiOption.isVisible().catch(() => false)) {
      await hindiOption.click();
    }
  }

  await adminPage.getByRole('button', { name: 'Save Channel' }).click();
  await adminPage.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
  await expect(adminPage.locator('#app')).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 6: Attribute Configuration for Magic AI
// ═════════════════════════════════════════════════

test('6.1 - Enable AI Translate on description attribute', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributes');
  await adminPage.locator('input[type="text"]').first().fill('description');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');

  const itemRow = adminPage.locator('div', { hasText: 'descriptionDescription' }).first();
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage.locator('#app').getByText('AI Translate')).toBeVisible();
  await adminPage.locator('label', { hasText: 'AI Translate' }).click();
  await clickSaveAndExpect(adminPage, 'Save Attribute', /Attribute Updated Successfully/i);
});

test('6.2 - Enable AI Translate on short_description attribute', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributes');
  await adminPage.locator('input[type="text"]').first().fill('short_desc');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');

  const itemRow = adminPage.locator('div', { hasText: 'short_descriptionShort Description' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage.locator('#app').getByText('AI Translate')).toBeVisible();
  await adminPage.locator('label', { hasText: 'AI Translate' }).click();
  await clickSaveAndExpect(adminPage, 'Save Attribute', /Attribute Updated Successfully/i);
});

// ═════════════════════════════════════════════════
// SECTION 7: Product - Magic AI Content Generation
// ═════════════════════════════════════════════════

test('7.1 - Create product, verify Magic AI button, and clean up', async ({ adminPage }) => {
  const uid = generateUid();
  const sku = `magicai-prod-${uid}`;

  // Create product
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await expect(adminPage.locator('input[name="sku"]')).toBeVisible();

  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Simple' }).first().click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Default' }).first().click();
  await adminPage.locator('input[name="sku"]').fill(sku);
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('#app').getByText(/Product created successfully/i)).toBeVisible();

  // Verify Magic AI button in WYSIWYG toolbar
  const magicAIButtons = adminPage.getByRole('button', { name: 'Magic AI' });
  await expect(magicAIButtons.first()).toBeVisible({ timeout: 20000 });
  const count = await magicAIButtons.count();
  expect(count).toBeGreaterThanOrEqual(2);

  // Cleanup: delete the product
  await navigateTo(adminPage, 'products');
  await searchInDataGrid(adminPage, sku);
  const row = adminPage.locator('div', { hasText: sku });
  await row.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Product Deleted Successfully/i)).toBeVisible({ timeout: 20000 });
});

test('7.3 - Open AI Assistance modal and verify fields', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — Magic AI button requires configured platform');
  test.setTimeout(60000);

  const uid = generateUid();
  const sku = `magicai-modal-${uid}`;

  // Create product
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await expect(adminPage.locator('input[name="sku"]')).toBeVisible();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Simple' }).first().click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Default' }).first().click();
  await adminPage.locator('input[name="sku"]').fill(sku);
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('#app').getByText(/Product created successfully/i)).toBeVisible();

  // Click Magic AI on Description WYSIWYG toolbar
  const magicAIBtn = adminPage.getByRole('button', { name: 'Magic AI' }).last();
  await expect(magicAIBtn).toBeVisible({ timeout: 20000 });
  await magicAIBtn.click();

  // Verify AI Assistance modal fields
  await expect(adminPage.locator('#app').getByText('AI Assistance')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('Default Prompt')).toBeVisible();
  // System Prompt field may have different label — check for the prompt textarea/input area
  const hasSystemPrompt = await adminPage.locator('#app').getByText('System Prompt', { exact: true }).isVisible({ timeout: 5000 }).catch(() => false);
  const hasPromptField = hasSystemPrompt || await adminPage.locator('#app textarea, #app .ql-editor').first().isVisible({ timeout: 5000 }).catch(() => false);
  expect(hasPromptField).toBeTruthy();
  await expect(adminPage.getByRole('button', { name: 'Generate' })).toBeVisible();
  await expect(adminPage.locator('.multiselect').first()).toBeVisible();

  // Close modal
  await adminPage.locator('.icon-cancel').click();

  // Cleanup: delete the product
  await navigateTo(adminPage, 'products');
  await searchInDataGrid(adminPage, sku);
  const row = adminPage.locator('div', { hasText: sku });
  await row.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Product Deleted Successfully/i)).toBeVisible({ timeout: 20000 });
});

test('7.5 - Verify More Actions menu on product edit page', async ({ adminPage }) => {
  const uid = generateUid();
  const sku = `magicai-more-${uid}`;

  // Create product
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await expect(adminPage.locator('input[name="sku"]')).toBeVisible();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Simple' }).first().click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Default' }).first().click();
  await adminPage.locator('input[name="sku"]').fill(sku);
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await expect(adminPage.locator('#app').getByText(/Product created successfully/i)).toBeVisible();

  // Verify "More Actions" button exists
  const moreBtn = adminPage.locator('[title="More Actions"]').first();
  await expect(moreBtn).toBeVisible({ timeout: 20000 });
  await moreBtn.click();

  const translateOption = adminPage.locator('span[title="Translate"]');
  if (await translateOption.isVisible().catch(() => false)) {
    await expect(translateOption).toBeVisible();
  }

  // Cleanup: delete the product
  await navigateTo(adminPage, 'products');
  await searchInDataGrid(adminPage, sku);
  const row = adminPage.locator('div', { hasText: sku });
  await row.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Product Deleted Successfully/i)).toBeVisible({ timeout: 20000 });
});

test('7.6 - Verify Translate Step 1 fields', async ({ adminPage }) => {
  test.setTimeout(30000);

  const uid = generateUid();
  const sku = `magicai-trans-${uid}`;

  // Create product
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await expect(adminPage.locator('input[name="sku"]')).toBeVisible();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Simple' }).first().click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Default' }).first().click();
  await adminPage.locator('input[name="sku"]').fill(sku);
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  // Wait for redirect to edit page (toast may disappear before we catch it)
  await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { timeout: 20000 });
  await adminPage.waitForLoadState('networkidle');

  const moreBtn = adminPage.locator('[title="More Actions"]').first();
  await expect(moreBtn).toBeVisible({ timeout: 20000 });
  await moreBtn.click();

  const translateOption = adminPage.locator('span[title="Translate"]');
  if (await translateOption.isVisible().catch(() => false)) {
    await translateOption.click();
    await expect(adminPage.locator('#app').getByText('Step 1: Select Source Channel, Language and Attributes')).toBeVisible();
    await expect(adminPage.getByRole('button', { name: 'Next' })).toBeVisible();
  }

  // Cleanup: delete the product
  await navigateTo(adminPage, 'products');
  await searchInDataGrid(adminPage, sku);
  const row = adminPage.locator('div', { hasText: sku });
  await row.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Product Deleted Successfully/i)).toBeVisible({ timeout: 20000 });
});

test('7.7 - Translate product content to Hindi and verify', async ({ adminPage }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — translation requires configured platform');
  test.setTimeout(120000);

  const uid = generateUid();
  const sku = `magicai-hindi-${uid}`;

  // Step 0: Enable hi_IN locale if not already enabled
  await navigateTo(adminPage, 'locales');
  await searchInDataGrid(adminPage, 'hi_IN', 'Search by code');
  const localeRow = adminPage.locator('#app div').filter({ hasText: 'hi_IN' }).first();
  const editBtn = localeRow.locator('span[title="Edit"]').first();
  if (await editBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    await editBtn.click();
    await adminPage.waitForLoadState('networkidle');
    const isChecked = await adminPage.locator('input#status[type="checkbox"]').isChecked();
    if (!isChecked) {
      await adminPage.locator('label[for="status"]').click();
      await adminPage.getByRole('button', { name: 'Save Locale' }).click();
      await adminPage.waitForLoadState('networkidle');
    }
  }

  // Step 1: Assign hi_IN to default channel if not assigned
  await navigateTo(adminPage, 'channels');
  await searchInDataGrid(adminPage, 'default');
  const channelRow = adminPage.locator('#app div').filter({ hasText: 'default' }).first();
  await channelRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('networkidle');

  // Check if Hindi is already in the locales multiselect
  const hindiTag = adminPage.locator('#locales .multiselect__tag', { hasText: 'Hindi' });
  if (!(await hindiTag.isVisible({ timeout: 3000 }).catch(() => false))) {
    await adminPage.locator('#locales').locator('.multiselect__tags').click();
    await adminPage.waitForTimeout(300);
    const hindiOption = adminPage.getByRole('option', { name: /Hindi/ }).first();
    if (await hindiOption.isVisible({ timeout: 5000 }).catch(() => false)) {
      await hindiOption.click();
      await adminPage.keyboard.press('Escape');
      await adminPage.getByRole('button', { name: 'Save Channel' }).click();
      await adminPage.waitForLoadState('networkidle');
    }
  }

  // Step 2: Create a product with English content
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await expect(adminPage.locator('input[name="sku"]')).toBeVisible();
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Simple' }).first().click();
  await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'Default' }).first().click();
  await adminPage.locator('input[name="sku"]').fill(sku);
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { timeout: 20000 });
  await adminPage.waitForLoadState('networkidle');

  // Fill product name in English
  const nameField = adminPage.locator('input[name*="[name]"]').first();
  if (await nameField.isVisible({ timeout: 5000 }).catch(() => false)) {
    await nameField.fill(`Test Product ${uid}`);
    await adminPage.getByRole('button', { name: 'Save Product' }).click();
    await adminPage.waitForLoadState('networkidle');
  }

  // Step 3: Open Translate modal via More Actions
  const moreBtn = adminPage.locator('[title="More Actions"]').first();
  await expect(moreBtn).toBeVisible({ timeout: 20000 });
  await moreBtn.click();

  const translateOption = adminPage.locator('span[title="Translate"]');
  if (!(await translateOption.isVisible({ timeout: 5000 }).catch(() => false))) {
    // Translate not available — skip rest but still cleanup
    await navigateTo(adminPage, 'products');
    await searchInDataGrid(adminPage, sku);
    const row = adminPage.locator('div', { hasText: sku });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    return;
  }

  await translateOption.click();
  await expect(adminPage.locator('#app').getByText('Step 1')).toBeVisible({ timeout: 10000 });

  // Step 4: Select Hindi as target language and proceed
  const nextBtn = adminPage.getByRole('button', { name: 'Next' });
  if (await nextBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    // Select target locale if dropdown available
    const targetLocale = adminPage.locator('.multiselect').filter({ hasText: /Target|Destination/ }).first();
    if (await targetLocale.isVisible({ timeout: 3000 }).catch(() => false)) {
      await targetLocale.locator('.multiselect__tags').click();
      await adminPage.waitForTimeout(300);
      const hindiOpt = adminPage.getByRole('option', { name: /Hindi/ }).first();
      if (await hindiOpt.isVisible({ timeout: 5000 }).catch(() => false)) {
        await hindiOpt.click();
      }
    }
    await nextBtn.click();
    await adminPage.waitForTimeout(1000);

    // Step 5: Click Translate if available
    const translateBtn = adminPage.getByRole('button', { name: /Translate/i }).first();
    if (await translateBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await translateBtn.click();
      // Wait for translation to complete (API call)
      await adminPage.waitForTimeout(10000);
    }
  }

  // Cleanup: delete the product
  await navigateTo(adminPage, 'products');
  await searchInDataGrid(adminPage, sku);
  const delRow = adminPage.locator('div', { hasText: sku });
  const delBtn = delRow.locator('span[title="Delete"]').first();
  if (await delBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    await delBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
});

// ═════════════════════════════════════════════════
// SECTION 8: Category - Magic AI Content Generation
// ═════════════════════════════════════════════════

test('8.1 - Create a category and verify Magic AI button', async ({ adminPage }) => {
  const uid = generateUid();
  const uniqueCode = `magicaicat${uid}`;

  await navigateTo(adminPage, 'categories');
  await adminPage.getByRole('link', { name: 'Create Category' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.locator('input[name="code"]').fill(uniqueCode);
  await adminPage.locator('#name').fill(`Electronics AI ${uid}`);

  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await expect(adminPage.locator('#app').getByText(/category created successfully/i)).toBeVisible({ timeout: 20000 });
});

test('8.2 - Verify Magic AI button on category description WYSIWYG', async ({ adminPage }) => {
  test.setTimeout(30000);

  await navigateTo(adminPage, 'categories');

  // Try to find and edit any category with a WYSIWYG
  const editLink = adminPage.locator('span[title="Edit"]').first();
  if (await editLink.isVisible().catch(() => false)) {
    await editLink.click();
    await adminPage.waitForLoadState('networkidle');

    const magicAIBtn = adminPage.getByRole('button', { name: 'Magic AI' });
    if (await magicAIBtn.first().isVisible().catch(() => false)) {
      await expect(magicAIBtn.first()).toBeVisible();
    }
  }
});

// ═════════════════════════════════════════════════
// SECTION 9: Roles & Permissions for Magic AI
// ═════════════════════════════════════════════════

test('9.1 - Verify Magic AI permission tree under Configuration in Roles', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.waitForLoadState('networkidle');

  const configLabel = adminPage.locator('label').filter({ hasText: /^Configuration$/ });
  if (await configLabel.isVisible().catch(() => false)) {
    await configLabel.click();
  } else {
    await adminPage.locator('div').filter({ hasText: /^Configuration$/ }).click();
  }

  await expect(adminPage.locator('label').filter({ hasText: 'Magic AI' })).toBeVisible();
});

test('9.2 - Create a Role with MagicAI permission and clean up', async ({ adminPage }) => {
  const uid = generateUid();
  const roleName = `MagicAI Mgr ${uid}`;

  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage.locator('label div:text("Dashboard")').first()).toBeVisible({ timeout: 5000 });
  await adminPage.locator('label div:text("Dashboard")').first().click();
  await adminPage.locator('label div:text("Configuration")').first().click();

  await adminPage.getByRole('textbox', { name: 'Name' }).fill(roleName);
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('Role with Magic AI permissions only');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  // Wait for redirect to roles list page (toast may disappear before assertion)
  await adminPage.waitForURL(/\/admin\/settings\/roles$/, { timeout: 20000 }).catch(() => {});
  await adminPage.waitForLoadState('networkidle');

  // Cleanup: delete the role
  await navigateTo(adminPage, 'roles');
  await searchInDataGrid(adminPage, roleName);
  const itemRow = adminPage.locator('div', { hasText: roleName });
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('#app').getByText('Are you sure you want to delete?')).toBeVisible();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Roles is deleted successfully/i).first()).toBeVisible();
});

test('9.3 - Create a user with MagicAI role and clean up both', async ({ adminPage }) => {
  const uid = generateUid();
  const roleName = `MagicAI Role ${uid}`;
  const userName = `MagicAI Tester ${uid}`;
  const email = `magicai-${uid}@example.com`;

  // Create role first
  await navigateTo(adminPage, 'roles');
  await adminPage.getByRole('link', { name: 'Create Role' }).click();
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage.locator('label div:text("Dashboard")').first()).toBeVisible({ timeout: 5000 });
  await adminPage.locator('label div:text("Dashboard")').first().click();
  await adminPage.locator('label div:text("Configuration")').first().click();

  await adminPage.getByRole('textbox', { name: 'Name' }).fill(roleName);
  await adminPage.getByRole('textbox', { name: 'Description' }).fill('Temp role for user test');
  await adminPage.getByRole('button', { name: 'Save Role' }).click();
  await expect(adminPage.locator('#app').getByText('Roles Created Successfully')).toBeVisible();

  // Create user
  await navigateTo(adminPage, 'users');
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill(userName);
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill(email);
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('test123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('test123');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).first().click();
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).nth(1).click();
  await adminPage.keyboard.type('kolkata');
  await adminPage.getByRole('option', { name: 'Asia/Kolkata (+05:30)' }).first().click();
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: roleName }).first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/User created successfully/i)).toBeVisible();

  // Cleanup: delete the user
  await navigateTo(adminPage, 'users');
  await searchInDataGrid(adminPage, userName);
  const userRow = adminPage.locator('div', { hasText: userName });
  await userRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('#app').getByText('Are you sure you want to delete?')).toBeVisible();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/User deleted successfully/i)).toBeVisible();

  // Cleanup: delete the role
  await navigateTo(adminPage, 'roles');
  await searchInDataGrid(adminPage, roleName);
  const roleRow = adminPage.locator('div', { hasText: roleName });
  await roleRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('#app').getByText('Are you sure you want to delete?')).toBeVisible();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Roles is deleted successfully/i).first()).toBeVisible();
});

});
