const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Fill channel creation form and submit.
 * @param {import('@playwright/test').Page} adminPage
 * @param {object} opts
 * @param {string} [opts.code]
 * @param {string} [opts.name]
 * @param {boolean} [opts.selectRootCategory=true]
 * @param {boolean} [opts.selectLocale=true]
 * @param {boolean} [opts.selectCurrency=true]
 */
async function fillChannelForm(adminPage, opts = {}) {
  const {
    code = '',
    name = '',
    selectRootCategory = true,
    selectLocale = true,
    selectCurrency = true,
  } = opts;

  if (code !== null) {
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  }

  if (selectRootCategory) {
    await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
    await adminPage.getByRole('option', { name: '[root]' }).locator('span').first().click();
  }

  if (name) {
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill(name);
  }

  if (selectLocale) {
    await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
    await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
    await adminPage.keyboard.press('Escape');
    await expect(adminPage.locator('#locales')).toBeVisible();
  }

  if (selectCurrency) {
    await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
    await adminPage.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
    await adminPage.keyboard.press('Escape');
    await expect(adminPage.locator('#currencies')).toBeVisible();
  }
}

/**
 * Helper: Create a channel end-to-end and return to listing.
 */
async function createChannel(adminPage, code, name) {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('link', { name: 'Create Channel' }).click();
  await fillChannelForm(adminPage, { code, name });
  await clickSaveAndExpect(adminPage, 'Save Channel', /Channel created successfully/i);
}

/**
 * Helper: Delete a channel by code (search, find, delete, confirm).
 * Silently succeeds if the channel is not found.
 */
async function deleteChannel(adminPage, code) {
  await navigateTo(adminPage, 'channels');
  await searchInDataGrid(adminPage, code);
  const row = adminPage.locator('#app div').filter({ hasText: code });
  const deleteBtn = row.locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

test.describe('Channel Management', () => {

  // --- Validation Tests (no cleanup needed) ---

  test('Create Channel with empty Code shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code: '', name: 'E-Commerce' });
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create Channel with empty Root Category shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code: `${uid}rc`, name: 'E-Commerce', selectRootCategory: false });
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.locator('#app').getByText('The Root Category field is required')).toBeVisible();
  });

  test('Create Channel with empty Locales shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code: `${uid}lc`, name: 'E-Commerce', selectLocale: false });
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.locator('#app').getByText('The Locales field is required')).toBeVisible();
  });

  test('Create Channel with empty Currency shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code: `${uid}cu`, name: 'E-Commerce', selectCurrency: false });
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.locator('#app').getByText('The Currencies field is required')).toBeVisible();
  });

  test('Create Channel with all required fields empty shows all validation errors', async ({ adminPage }) => {
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Root Category field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Locales field is required')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('The Currencies field is required')).toBeVisible();
  });

  // --- CRUD Tests (each creates its own data) ---

  test('Create Channel successfully', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `${uid}ch`;
    const name = `${uid} Channel`;
    await createChannel(adminPage, code, name);

    // Cleanup
    await deleteChannel(adminPage, code);
  });

  test('Create Channel without translations succeeds', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `${uid}nt`;

    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code, name: '' });
    await clickSaveAndExpect(adminPage, 'Save Channel', /Channel created successfully/i);

    // Cleanup
    await deleteChannel(adminPage, code);
  });

  test('Create Channel with duplicate Code shows error', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `${uid}dup`;
    const name = `${uid} Dup`;

    // Create first channel
    await createChannel(adminPage, code, name);

    // Try to create another with same code
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code, name: 'Other Name' });
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.locator('#app').getByText('The Code has already been taken.')).toBeVisible();

    // Cleanup
    await deleteChannel(adminPage, code);
  });

  test('Search for default channel', async ({ adminPage }) => {
    await navigateTo(adminPage, 'channels');
    await searchInDataGrid(adminPage, 'default');
    await expect(adminPage.locator('#app').getByText('default', { exact: true }).first()).toBeVisible();
  });

  test('Update Channel', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `${uid}upd`;
    const originalName = `${uid} Original`;

    // Create
    await createChannel(adminPage, code, originalName);

    // Search and edit
    await navigateTo(adminPage, 'channels');
    await searchInDataGrid(adminPage, code);
    const row = adminPage.locator('#app div').filter({ hasText: code });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill(`${uid} Updated`);
    await clickSaveAndExpect(adminPage, 'Save Channel', /Update Channel Successfully/i);

    // Cleanup
    await deleteChannel(adminPage, code);
  });

  test('Delete Channel', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `${uid}del`;
    const name = `${uid} Delete`;

    // Create
    await createChannel(adminPage, code, name);

    // Search and delete
    await navigateTo(adminPage, 'channels');
    await searchInDataGrid(adminPage, code);
    const row = adminPage.locator('#app div').filter({ hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Channel deleted successfully/i)).toBeVisible();
  });

  test('Delete default Channel shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'channels');
    await searchInDataGrid(adminPage, 'default');
    const row = adminPage.locator('#app div').filter({ hasText: 'default' }).first();
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/default channel cannot be deleted|can.t delete the channel.*default/i)).toBeVisible();
  });
});
