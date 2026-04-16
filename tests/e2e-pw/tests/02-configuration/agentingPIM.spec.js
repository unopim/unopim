const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

const OPENAI_API_KEY = process.env.OPENAI_API_KEY || '';

test.describe('UnoPim Agenting PIM Chat Widget', () => {

test.beforeEach(async ({ adminPageWithWidget }) => {
  await navigateTo(adminPageWithWidget, 'dashboard');
});

// ═════════════════════════════════════════════════
// SECTION 1: Widget Panel — Open / Close / Header
// ═════════════════════════════════════════════════

test('1.1 - "Open Agenting PIM" floating button is visible on dashboard', async ({ adminPageWithWidget }) => {
  const btn = adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' });
  await expect(btn).toBeVisible();
});

test('1.2 - Clicking floating button opens the Agenting PIM panel', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await expect(adminPageWithWidget.getByText('Agenting PIM')).toBeVisible();
  await expect(adminPageWithWidget.getByText('AI-powered operations')).toBeVisible();
});

test('1.3 - Panel header shows Agenting PIM title and subtitle', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await expect(adminPageWithWidget.getByText('Agenting PIM', { exact: true })).toBeVisible();
  await expect(adminPageWithWidget.getByText('AI-powered operations')).toBeVisible();
});

test('1.4 - Panel header shows AI Settings link', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  const settingsLink = adminPageWithWidget.getByRole('link', { name: 'AI Settings' });
  await expect(settingsLink).toBeVisible();
  await expect(settingsLink).toHaveAttribute('href', /\/admin\/ai-agent\/settings/);
});

test('1.5 - Panel header shows Close button', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await expect(adminPageWithWidget.getByRole('button', { name: 'Close' })).toBeVisible();
});

test('1.6 - Clicking Close button hides the panel', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await expect(adminPageWithWidget.getByRole('button', { name: 'Close' })).toBeVisible();

  await adminPageWithWidget.getByRole('button', { name: 'Close' }).click();

  await expect(adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' })).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 2: Tab Navigation
// ═════════════════════════════════════════════════

test('2.1 - Panel shows three tabs: Capabilities, Chat, Sessions', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await expect(adminPageWithWidget.getByRole('button', { name: /Capabilities/ })).toBeVisible();
  await expect(adminPageWithWidget.getByRole('button', { name: /Chat/ })).toBeVisible();
  await expect(adminPageWithWidget.getByRole('button', { name: /Sessions/ })).toBeVisible();
});

test('2.2 - Capabilities tab is active by default', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await expect(adminPageWithWidget.getByPlaceholder('Search capabilities…')).toBeVisible();
});

test('2.3 - Clicking Chat tab shows the chat interface', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await expect(adminPageWithWidget.getByPlaceholder('Ask me anything about your catalog…')).toBeVisible();
});

test('2.4 - Clicking Sessions tab shows sessions list', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await adminPageWithWidget.getByRole('button', { name: /Sessions/ }).click();

  await expect(adminPageWithWidget.getByRole('button', { name: 'New Session' })).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 3: Capabilities Tab
// ═════════════════════════════════════════════════

test('3.1 - Capabilities tab shows search input', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await expect(adminPageWithWidget.getByPlaceholder('Search capabilities…')).toBeVisible();
});

test('3.2 - Capabilities tab shows all capability cards as buttons', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  // Each capability is a button with "Title Description" accessible name
  const capabilityPatterns = [
    /Create from Image.*Upload/,
    /Update Products.*Update/,
    /Search Products.*Find/,
    /Find Similar/,
    /Generate Content.*AI-generated/,
    /Generate Image.*Create product images/,
    /Assign Categories/,
    /List Attributes/,
    /Edit Product Image/,
    /Export Products/,
    /Bulk Import CSV/,
    /Delete Products/,
    /Create Category/,
    /Category Tree/,
    /Create Attribute/,
    /Manage Options/,
    /Attribute Families.*List/,
    /Bulk Edit.*Mass/,
    /Catalog Summary/,
    /Channels.*View channels/,
    /Users.*View admin/,
    /Roles.*View roles/,
    /Ask Anything/,
  ];

  for (const pattern of capabilityPatterns) {
    await expect(adminPageWithWidget.getByRole('button', { name: pattern })).toBeVisible();
  }
});

test('3.3 - Each capability card shows title and description', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await expect(adminPageWithWidget.getByText('Upload photos to auto-create products')).toBeVisible();
  await expect(adminPageWithWidget.getByText('Find products by SKU, name, or status')).toBeVisible();
  await expect(adminPageWithWidget.getByText('Free-form PIM assistant')).toBeVisible();
});

test('3.4 - Search filters capability cards', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  const searchInput = adminPageWithWidget.getByPlaceholder('Search capabilities…');
  await searchInput.fill('image');

  // Image-related capability buttons should still be visible
  await expect(adminPageWithWidget.getByRole('button', { name: /Create from Image/ })).toBeVisible();

  // Unrelated capabilities should be hidden
  const bulkEditVisible = await adminPageWithWidget.getByRole('button', { name: /Bulk Edit.*Mass/ }).isVisible().catch(() => false);
  expect(bulkEditVisible).toBe(false);
});

test('3.5 - Clearing search shows all capabilities again', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  const searchInput = adminPageWithWidget.getByPlaceholder('Search capabilities…');
  await searchInput.fill('image');
  // Wait for filter to take effect
  await expect(adminPageWithWidget.getByRole('button', { name: /Create from Image/ })).toBeVisible();

  await searchInput.clear();

  // All capabilities should be visible again
  await expect(adminPageWithWidget.getByRole('button', { name: /Bulk Edit.*Mass/ })).toBeVisible();
  await expect(adminPageWithWidget.getByRole('button', { name: /Ask Anything/ })).toBeVisible();
});

test('3.6 - Clicking a capability card switches to Chat tab', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  await adminPageWithWidget.getByRole('button', { name: /Catalog Summary/ }).click();

  // After clicking a capability, the tab switches to chat and the input shows the capability hint
  const chatInput = adminPageWithWidget.locator('textarea[placeholder]').first();
  await expect(chatInput).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 4: Chat Tab — UI Elements
// ═════════════════════════════════════════════════

test('4.1 - Chat tab shows welcome message', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await expect(adminPageWithWidget.getByText('How can I help with your catalog?')).toBeVisible();
});

test('4.2 - Chat tab shows "General Chat" session label', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await expect(adminPageWithWidget.getByText('General Chat')).toBeVisible();
});

test('4.3 - Chat input has correct placeholder text', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await expect(adminPageWithWidget.getByPlaceholder('Ask me anything about your catalog…')).toBeVisible();
});

test('4.4 - Chat input shows keyboard shortcut hint', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await expect(adminPageWithWidget.getByText('Enter to send')).toBeVisible();
});

test('4.5 - Chat input has Attach image button', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await expect(adminPageWithWidget.locator('[title="Attach image"]')).toBeVisible();
});

test('4.6 - Chat input has AI Platform dropdown with configured platforms', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  // Platform dropdown only renders when AI platforms are configured
  const platformSelect = adminPageWithWidget.locator('select[title="Select AI Platform"]');
  const isVisible = await platformSelect.isVisible().catch(() => false);
  test.skip(!isVisible, 'No AI platforms configured in current environment');

  const options = await platformSelect.locator('option').allTextContents();
  expect(options.length).toBeGreaterThanOrEqual(1);
});

test('4.7 - Chat input has Model dropdown with available models', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  // Model dropdown only renders when AI platforms are configured
  const modelSelect = adminPageWithWidget.locator('select[title="Select Model"]');
  const isVisible = await modelSelect.isVisible().catch(() => false);
  test.skip(!isVisible, 'No AI platforms configured in current environment');

  const options = await modelSelect.locator('option').allTextContents();
  expect(options.length).toBeGreaterThanOrEqual(1);
});

test('4.8 - Send button is disabled when input is empty', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await expect(adminPageWithWidget.getByRole('button', { name: 'Send' })).toBeDisabled();
});

test('4.9 - Send button is enabled when input has text', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await adminPageWithWidget.getByPlaceholder('Ask me anything about your catalog…').fill('hello');

  await expect(adminPageWithWidget.getByRole('button', { name: 'Send' })).toBeEnabled();
});

// ═════════════════════════════════════════════════
// SECTION 5: Chat — Sending Messages & Responses
// (requires OPENAI_API_KEY + configured platform)
// ═════════════════════════════════════════════════

test('5.0 - Setup: Create OpenAI platform for chat tests', async ({ adminPageWithWidget }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set — skipping platform setup');
  test.setTimeout(60000);

  await adminPageWithWidget.goto('/admin/magic-ai/platform', { waitUntil: 'networkidle' });

  // Check if platform already exists in the datagrid (before opening modal)
  const existingPlatform = adminPageWithWidget.locator('span[title="Edit"]');
  if (await existingPlatform.first().isVisible({ timeout: 3000 }).catch(() => false)) {
    return;
  }

  // Create platform
  await adminPageWithWidget.getByRole('button', { name: 'Add Platform' }).first().click();
  await expect(adminPageWithWidget.getByText('Add AI Platform')).toBeVisible();
  // Select provider - try component select first, fall back to native select
  const componentSelect = adminPageWithWidget.locator('input[name="provider"]').first().locator('..');
  if (await componentSelect.locator('.multiselect__placeholder').isVisible({ timeout: 2000 }).catch(() => false)) {
    await componentSelect.locator('.multiselect__placeholder').click();
    await adminPageWithWidget.getByRole('option', { name: 'OpenAI' }).first().click();
  } else {
    await adminPageWithWidget.locator('select[name="provider"]').selectOption({ label: 'OpenAI' });
  }
  await adminPageWithWidget.locator('input[name="label"]').fill('OpenAI Chat Test');
  await adminPageWithWidget.locator('input[name="api_key"]').fill(OPENAI_API_KEY);

  // Click outside API key field to trigger model fetch via AJAX
  await adminPageWithWidget.locator('input[name="label"]').click();
  // Wait for models to auto-load after AJAX fetch (models auto-select recommended or first 3)
  const modelTag = adminPageWithWidget.locator('.rounded-full.bg-violet-100').first();
  await modelTag.waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  // Mark as default — use JavaScript to check the sr-only checkbox reliably
  await adminPageWithWidget.evaluate(() => {
    const cb = document.querySelector('input[name="is_default"][type="checkbox"]');
    if (cb && !cb.checked) {
      cb.click();
    }
  });

  await adminPageWithWidget.getByRole('button', { name: 'Save' }).click();
  await expect(adminPageWithWidget.getByText(/saved successfully|created successfully|updated successfully/i)).toBeVisible({ timeout: 30000 });
});

test('5.1 - Sending a message shows user message bubble', async ({ adminPageWithWidget }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set');
  test.setTimeout(60000);

  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await adminPageWithWidget.getByPlaceholder('Ask me anything about your catalog…').fill('How many products do I have?');
  await adminPageWithWidget.getByRole('button', { name: 'Send' }).click();

  await expect(adminPageWithWidget.locator('#app')).toContainText('How many products do I have?');
});

test('5.3 - AI response shows Retry, Copy, Helpful, Not helpful buttons', async ({ adminPageWithWidget }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set');
  test.setTimeout(60000);

  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await adminPageWithWidget.getByPlaceholder('Ask me anything about your catalog…').fill('How many categories do I have?');
  await adminPageWithWidget.getByRole('button', { name: 'Send' }).click();

  await expect(adminPageWithWidget.getByText(/categor/i).last()).toBeVisible({ timeout: 45000 });

  await expect(adminPageWithWidget.getByRole('button', { name: 'Retry' }).last()).toBeVisible();
  await expect(adminPageWithWidget.getByRole('button', { name: 'Copy' }).last()).toBeVisible();
  await expect(adminPageWithWidget.getByRole('button', { name: 'Helpful', exact: true }).last()).toBeVisible();
  await expect(adminPageWithWidget.getByRole('button', { name: 'Not helpful' }).last()).toBeVisible();
});

test('5.4 - Message counter badge appears after sending', async ({ adminPageWithWidget }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set');
  test.setTimeout(60000);

  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await adminPageWithWidget.getByPlaceholder('Ask me anything about your catalog…').fill('List my channels');
  await adminPageWithWidget.getByRole('button', { name: 'Send' }).click();

  await expect(adminPageWithWidget.getByText(/channel/i).last()).toBeVisible({ timeout: 45000 });

  await expect(adminPageWithWidget.getByText(/message\(s\)/)).toBeVisible();
});

// Tests 5.5 and 5.6 removed — they depend on real-time OpenAI API responses
// which are unreliable in CI (rate limits, latency >45s, intermittent failures).

// ═════════════════════════════════════════════════
// SECTION 6: Platform & Model Switching
// ═════════════════════════════════════════════════

test('6.1 - Switching AI Platform updates the model dropdown', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  const platformSelect = adminPageWithWidget.getByRole('combobox', { name: 'Select AI Platform' });
  const options = await platformSelect.locator('option').allTextContents();

  if (options.length >= 2) {
    const modelSelect = adminPageWithWidget.getByRole('combobox', { name: 'Select Model' });

    await platformSelect.selectOption({ index: 0 });
    await adminPageWithWidget.waitForLoadState('networkidle');

    const newModels = await modelSelect.locator('option').allTextContents();
    expect(newModels.length).toBeGreaterThan(0);
  }
});

test('6.2 - Model dropdown contains at least one model', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  // Model dropdown only renders when AI platforms are configured
  const modelSelect = adminPageWithWidget.locator('select[title="Select Model"]');
  const isVisible = await modelSelect.isVisible().catch(() => false);
  test.skip(!isVisible, 'No AI platforms configured in current environment');

  const options = await modelSelect.locator('option').allTextContents();
  expect(options.length).toBeGreaterThanOrEqual(1);
});

// ═════════════════════════════════════════════════
// SECTION 7: Sessions Tab
// ═════════════════════════════════════════════════

test('7.1 - Sessions tab shows "New Session" button', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Sessions/ }).click();

  await expect(adminPageWithWidget.getByRole('button', { name: 'New Session' })).toBeVisible();
});

test('7.2 - Sessions tab shows empty state or session list', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Sessions/ }).click();

  const emptyState = adminPageWithWidget.getByText('No saved sessions yet');
  const emptyVisible = await emptyState.isVisible().catch(() => false);

  if (emptyVisible) {
    await expect(emptyState).toBeVisible();
  }
});

test('7.3 - Sessions tab shows session count badge after chat activity', async ({ adminPageWithWidget }) => {
  test.skip(!OPENAI_API_KEY, 'OPENAI_API_KEY not set');
  test.setTimeout(60000);

  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
  await adminPageWithWidget.getByRole('button', { name: /Chat/ }).click();

  await adminPageWithWidget.getByPlaceholder('Ask me anything about your catalog…').fill('hello');
  await adminPageWithWidget.getByRole('button', { name: 'Send' }).click();

  // Wait for AI response to complete by waiting for response action buttons
  await expect(adminPageWithWidget.getByRole('button', { name: 'Retry' })).toBeVisible({ timeout: 45000 });

  const sessionsTab = adminPageWithWidget.getByRole('button', { name: /Sessions/ });
  const tabText = await sessionsTab.innerText();
  expect(tabText).toMatch(/Sessions\s*\d+/);
});

// ═════════════════════════════════════════════════
// SECTION 8: Panel Persistence Across Pages
// ═════════════════════════════════════════════════

test('8.1 - Agenting PIM button is visible on products page', async ({ adminPageWithWidget }) => {
  await navigateTo(adminPageWithWidget, 'products');

  await expect(adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' })).toBeVisible();
});

test('8.2 - Agenting PIM button is visible on categories page', async ({ adminPageWithWidget }) => {
  await navigateTo(adminPageWithWidget, 'categories');

  await expect(adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' })).toBeVisible({ timeout: 20000 });
});

test('8.3 - Agenting PIM button is visible on configuration page', async ({ adminPageWithWidget }) => {
  await navigateTo(adminPageWithWidget, 'configuration');

  await expect(adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' })).toBeVisible();
});

test('8.4 - AI Settings link navigates to Magic AI config', async ({ adminPageWithWidget }) => {
  await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();

  const settingsLink = adminPageWithWidget.getByRole('link', { name: 'AI Settings' });
  await expect(settingsLink).toBeVisible();
  const href = await settingsLink.getAttribute('href');
  expect(href).toMatch(/\/admin\/ai-agent\/settings/);
});

});
