const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Generate a random 3-letter uppercase currency code unlikely to collide with real ones.
 */
function randomCurrencyCode() {
  const { randomInt } = require('crypto');
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  // Start with Z to avoid common real currency codes
  return 'Z' + chars[randomInt(26)] + chars[randomInt(26)];
}

/**
 * Helper: Fill the currency creation modal fields.
 */
async function fillCurrencyModal(adminPage, { code = '', symbol = '', decimal = '2', enableStatus = true } = {}) {
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill(code);
  if (symbol) {
    await adminPage.getByRole('textbox', { name: 'Symbol' }).fill(symbol);
  }
  await adminPage.fill('input[type="number"][name="decimal"]', decimal);
  if (enableStatus) {
    await adminPage.locator('label[for="status"]').click();
  }
}

/**
 * Helper: Create a currency and verify success.
 */
async function createCurrency(adminPage, code, symbol = '$', decimal = '2') {
  await navigateTo(adminPage, 'currencies');
  await adminPage.getByRole('button', { name: 'Create Currency' }).click();
  await fillCurrencyModal(adminPage, { code, symbol, decimal });
  await clickSaveAndExpect(adminPage, 'Save Currency', /Currency created successfully/i);
}

/**
 * Helper: Delete a currency by code (search, delete, confirm).
 */
async function deleteCurrency(adminPage, code) {
  await navigateTo(adminPage, 'currencies');
  await searchInDataGrid(adminPage, code, 'Search by code or id');
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

test.describe('Currency Management', () => {

  // --- Validation Tests ---

  test('Create Currency with empty Code shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'currencies');
    await adminPage.getByRole('button', { name: 'Create Currency' }).click();
    await fillCurrencyModal(adminPage, { code: '', symbol: '$', decimal: '2' });
    await adminPage.getByRole('button', { name: 'Save Currency' }).click();
    await expect(adminPage.locator('#app').getByText(/The Code field is required/i)).toBeVisible();
  });

  test('Create Currency with Code less than 3 characters shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'currencies');
    await adminPage.getByRole('button', { name: 'Create Currency' }).click();
    await fillCurrencyModal(adminPage, { code: 'gh', symbol: '$', decimal: '2' });
    await adminPage.getByRole('button', { name: 'Save Currency' }).click();
    await expect(adminPage.locator('#app').getByText(/The code must be at least 3 characters/i)).toBeVisible();
  });

  test('Create Currency with Code more than 3 characters shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'currencies');
    await adminPage.getByRole('button', { name: 'Create Currency' }).click();
    await fillCurrencyModal(adminPage, { code: 'ghdn', symbol: '$', decimal: '2' });
    await adminPage.getByRole('button', { name: 'Save Currency' }).click();
    await expect(adminPage.locator('#app').getByText(/The code may not be greater than 3 characters/i)).toBeVisible();
  });

  // --- CRUD Tests ---

  test('Create Currency successfully', async ({ adminPage }) => {
    const code = randomCurrencyCode();
    await createCurrency(adminPage, code, '₫', '2');

    // Cleanup
    await deleteCurrency(adminPage, code);
  });

  test('Search for seeded currency USD', async ({ adminPage }) => {
    await navigateTo(adminPage, 'currencies');
    await searchInDataGrid(adminPage, 'USD', 'Search by code or id');
    await expect(adminPage.locator('#app').getByText('USD', { exact: true }).first()).toBeVisible();
  });

  test('Update Currency', async ({ adminPage }) => {
    const code = randomCurrencyCode();
    // Create
    await createCurrency(adminPage, code, '₫', '2');

    // Search and edit
    await navigateTo(adminPage, 'currencies');
    await searchInDataGrid(adminPage, code, 'Search by code or id');
    const row = adminPage.locator('#app div').filter({ hasText: code });
    await row.locator('span[title="Edit"]').first().click();
    await adminPage.fill('input[type="number"][name="decimal"]', '3');
    await clickSaveAndExpect(adminPage, 'Save Currency', /Currency updated successfully/i);

    // Cleanup
    await deleteCurrency(adminPage, code);
  });

  test('Delete Currency', async ({ adminPage }) => {
    const code = randomCurrencyCode();
    // Create
    await createCurrency(adminPage, code, '₫', '2');

    // Delete
    await navigateTo(adminPage, 'currencies');
    await searchInDataGrid(adminPage, code, 'Search by code or id');
    const row = adminPage.locator('#app div').filter({ hasText: code });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Currency deleted successfully/i)).toBeVisible();
  });

  test('Delete channel-linked currency shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'currencies');
    await searchInDataGrid(adminPage, 'USD', 'Search by code or id');
    const row = adminPage.locator('#app div').filter({ hasText: 'USD' });
    await row.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/cannot delete a currency linked to a channel/i)).toBeVisible();
  });
});
