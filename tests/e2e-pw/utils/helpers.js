// utils/helpers.js — Shared Playwright test utilities for UnoPim E2E tests

/**
 * Admin route map — direct URLs for all admin sections.
 * Use navigateTo() instead of clicking through the sidebar.
 */
const ROUTES = {
  dashboard:         '/admin/dashboard',
  products:          '/admin/catalog/products',
  categories:        '/admin/catalog/categories',
  categoryFields:    '/admin/catalog/category-fields',
  attributes:        '/admin/catalog/attributes',
  attributeGroups:   '/admin/catalog/attributegroups',
  attributeFamilies: '/admin/catalog/families',
  channels:          '/admin/settings/channels',
  currencies:        '/admin/settings/currencies',
  locales:           '/admin/settings/locales',
  roles:             '/admin/settings/roles',
  users:             '/admin/settings/users',
  integrations:      '/admin/integrations/api-keys',
  webhook:           '/admin/webhook/settings',
  exports:           '/admin/settings/data-transfer/exports',
  imports:           '/admin/settings/data-transfer/imports',
  configuration:     '/admin/configuration',
};

/**
 * Navigate directly to an admin route (replaces 2-click sidebar navigation).
 * @param {import('@playwright/test').Page} page
 * @param {keyof ROUTES} route — key from ROUTES map
 */
async function navigateTo(page, route) {
  const url = ROUTES[route];
  if (!url) throw new Error(`Unknown route: "${route}". Available: ${Object.keys(ROUTES).join(', ')}`);
  await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 }).catch(async () => {
    // networkidle may timeout due to debugbar — fallback to checking Vue rendered
    await page.waitForLoadState('load', { timeout: 10000 }).catch(() => {});
  });
}

/**
 * Search in a DataGrid using the search input.
 * @param {import('@playwright/test').Page} page
 * @param {string} text — search query
 * @param {string} [placeholder='Search'] — placeholder text of search input
 */
async function searchInDataGrid(page, text, placeholder = 'Search') {
  const searchInput = page.getByPlaceholder(placeholder).first();
  await searchInput.waitFor({ state: 'visible', timeout: 30000 });
  await searchInput.fill(text);
  await page.keyboard.press('Enter');
  // Wait for the DataGrid to refresh after search
  await page.waitForLoadState('load');
  await page.waitForTimeout(500);
}

/**
 * Click the Edit action button on a DataGrid row matching the given text.
 * @param {import('@playwright/test').Page} page
 * @param {string} rowText — text to identify the row
 */
async function clickEditOnRow(page, rowText) {
  const row = page.locator('#app div').filter({ hasText: rowText }).first();
  await row.locator('span[title="Edit"]').first().click();
  await page.waitForLoadState('networkidle');
}

/**
 * Click the Delete action button on a DataGrid row matching the given text.
 * @param {import('@playwright/test').Page} page
 * @param {string} rowText — text to identify the row
 */
async function clickDeleteOnRow(page, rowText) {
  const row = page.locator('#app div').filter({ hasText: rowText }).first();
  await row.locator('span[title="Delete"]').first().click();
}

/**
 * Confirm the delete modal by clicking the Delete/Agree button.
 * @param {import('@playwright/test').Page} page
 */
async function confirmDelete(page) {
  await page.getByRole('button', { name: 'Delete' }).click();
  await page.waitForLoadState('networkidle');
}

/**
 * Assert a success toast message is visible.
 * @param {import('@playwright/test').Page} page
 * @param {RegExp|string} pattern — message pattern to match
 * @param {number} [timeout=20000]
 */
async function expectSuccessToast(page, pattern, timeout = 20000) {
  const { expect } = require('@playwright/test');
  const regex = pattern instanceof RegExp ? pattern : new RegExp(pattern, 'i');
  await expect(page.locator('#app').getByText(regex).first()).toBeVisible({ timeout });
}

/**
 * Click Save and verify success — accepts either toast message OR URL redirect.
 * Solves the CI issue where the page redirects before the toast is visible.
 * @param {import('@playwright/test').Page} page
 * @param {string} buttonName — name of the save button (e.g. 'Save Attribute')
 * @param {RegExp|string} toastPattern — success toast text pattern
 * @param {RegExp} [urlPattern] — optional URL pattern to wait for on redirect
 */
async function clickSaveAndExpect(page, buttonName, toastPattern, urlPattern) {
  const currentUrl = page.url();
  const regex = toastPattern instanceof RegExp ? toastPattern : new RegExp(toastPattern, 'i');

  await page.getByRole('button', { name: buttonName }).click();

  // Either toast appears OR URL changes (redirect after save).
  // Uses Promise.any — fails only if BOTH timeout (real failure).
  await Promise.any([
    page.locator('#app').getByText(regex).first().waitFor({ state: 'visible', timeout: 20000 }),
    urlPattern
      ? page.waitForURL(urlPattern, { timeout: 20000 })
      : page.waitForURL((url) => url.toString() !== currentUrl, { timeout: 20000 }),
  ]);
}

/**
 * Generate a unique identifier for test data isolation.
 * @returns {string} unique ID like "m1abc2def"
 */
function generateUid() {
  const { randomBytes } = require('crypto');
  return Date.now().toString(36) + randomBytes(4).toString('hex');
}

module.exports = {
  ROUTES,
  navigateTo,
  searchInDataGrid,
  clickEditOnRow,
  clickDeleteOnRow,
  confirmDelete,
  expectSuccessToast,
  clickSaveAndExpect,
  generateUid,
};
