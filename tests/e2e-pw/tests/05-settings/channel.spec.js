const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

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

  // quick-create-modal selects are vue-multiselect keyed by hidden input name.
  const pickOption = async (inputName, optionName, exact = false) => {
    const ms = adminPage.locator('.multiselect').filter({ has: adminPage.locator(`input[name="${inputName}"]`) });
    await ms.locator('.multiselect__tags').click();
    // options are .multiselect__option, without role="option".
    const option = ms.locator('.multiselect__option', {
      hasText: exact ? new RegExp(`^${optionName}$`) : optionName,
    }).first();
    await option.waitFor({ state: 'visible', timeout: 10000 });
    await option.click();
    await adminPage.keyboard.press('Escape');
  };

  // Only fill when there is a value: fill('') marks the field touched-but-empty,
  // which suppresses the required message the empty-code test asserts.
  if (code) {
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  }

  if (selectRootCategory) {
    await pickOption('root_category_id', 'Root', true);
  }

  if (name) {
    // Name is keyed by the requested locale, which need not be en_US.
    await adminPage.locator('input[name$="[name]"]').first().fill(name);
  }

  if (selectLocale) {
    await pickOption('locales', 'English (United States)');
  }

  if (selectCurrency) {
    await pickOption('currencies', 'US Dollar');
  }
}

/**
 * Helper: Create a channel end-to-end and return to listing.
 */
async function createChannel(adminPage, code, name) {
  await navigateTo(adminPage, 'channels');
  await adminPage.getByRole('button', { name: 'Create Channel' }).click();
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
    await adminPage.getByRole('button', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code: '', name: 'E-Commerce' });
    // v-code auto-generates the code from the name, so clear it afterwards to
    // actually submit an empty code and trigger the required rule.
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
    await adminPage.getByRole('textbox', { name: 'Code' }).blur();
    await clickSave(adminPage, 'Save Channel');
    await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  });

  test('Create Channel with empty Root Category shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('button', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code: `${uid}rc`, name: 'E-Commerce', selectRootCategory: false });
    await clickSave(adminPage, 'Save Channel');
    await expect(adminPage.locator('#app').getByText('The Root Category field is required')).toBeVisible();
  });

  test('Create Channel with empty Locales shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('button', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code: `${uid}lc`, name: 'E-Commerce', selectLocale: false });
    await clickSave(adminPage, 'Save Channel');
    await expect(adminPage.locator('#app').getByText('The Locales field is required')).toBeVisible();
  });

  test('Create Channel with empty Currency shows validation error', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('button', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code: `${uid}cu`, name: 'E-Commerce', selectCurrency: false });
    await clickSave(adminPage, 'Save Channel');
    await expect(adminPage.locator('#app').getByText('The Currencies field is required')).toBeVisible();
  });

  test('Create Channel with all required fields empty shows all validation errors', async ({ adminPage }) => {
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('button', { name: 'Create Channel' }).click();
    // Leave code untouched (fill('') suppresses its required message) and submit.
    await clickSave(adminPage, 'Save Channel');
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

  test('Create Channel requires a name in the quick-create modal', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `${uid}nt`;

    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('button', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code, name: '' });
    await clickSave(adminPage, 'Save Channel');
    await expect(adminPage.locator('#app').getByText('The Name field is required')).toBeVisible();
  });

  test('Create Channel with duplicate Code shows error', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `${uid}dup`;
    const name = `${uid} Dup`;

    // Create first channel
    await createChannel(adminPage, code, name);

    // Try to create another with same code
    await navigateTo(adminPage, 'channels');
    await adminPage.getByRole('button', { name: 'Create Channel' }).click();
    await fillChannelForm(adminPage, { code, name: 'Other Name' });
    await clickSave(adminPage, 'Save Channel');
    await expect(adminPage.locator('#app').getByText('The code has already been taken.').first()).toBeVisible();

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
    await adminPage.locator('input[name$="[name]"]').first().fill(`${uid} Updated`);
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
    // '#app div' matches many containers; target the datagrid row instead.
    const row = adminPage.locator('.row').filter({ hasText: 'default' }).first();
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/default channel cannot be deleted|can.t delete the channel.*default/i)).toBeVisible();
  });
});
