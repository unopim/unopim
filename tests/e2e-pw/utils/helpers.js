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
  attributeGroups:   '/admin/catalog/attribute-groups',
  attributeFamilies: '/admin/catalog/attribute-families',
  channels:          '/admin/settings/channels',
  currencies:        '/admin/settings/currencies',
  locales:           '/admin/settings/locales',
  roles:             '/admin/settings/roles',
  users:             '/admin/settings/users',
  integrations:      '/admin/configuration/integrations',
  webhook:           '/admin/configuration/webhook',
  exports:           '/admin/data-transfer/exports',
  imports:           '/admin/data-transfer/imports',
  configuration:     '/admin/configuration',
  notifications:     '/admin/notifications',
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
  // Scope to an actual DataGrid record row (div.row.grid.cursor-pointer) so the
  // Edit icon belongs to the matched row and not to some earlier row folded into
  // a broader container match.
  const row = page.locator('div.row.grid.cursor-pointer').filter({ hasText: rowText }).first();
  await row.locator('span[title="Edit"]').first().click();
  await page.waitForLoadState('networkidle');
}

/**
 * Click the Delete action button on a DataGrid row matching the given text.
 * @param {import('@playwright/test').Page} page
 * @param {string} rowText — text to identify the row
 */
async function clickDeleteOnRow(page, rowText) {
  const row = page.locator('div.row.grid.cursor-pointer').filter({ hasText: rowText }).first();
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
 * Click a form's save action, tolerant of the global unsaved-changes bar.
 *
 * Forms that track changes hide their in-form submit button once dirty and move
 * saving to the sticky "Save changes" bar. Modal sub-forms and non-tracked forms
 * (e.g. login) keep their own button. This clicks the named button when it is
 * actually visible, otherwise falls back to the bar.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} buttonName — the in-form save button label (e.g. 'Save Product')
 */
async function clickSave(page, buttonName) {
  const named = page.getByRole('button', { name: buttonName });
  const bar = page.getByRole('button', { name: 'Save changes' });

  // Wait for whichever save affordance the form exposes: the in-form button
  // (non-tracked / modal forms) or the unsaved-changes bar (tracked forms),
  // which may take a moment to appear once the form is dirty.
  await Promise.race([
    named.waitFor({ state: 'visible', timeout: 8000 }).catch(() => {}),
    bar.waitFor({ state: 'visible', timeout: 8000 }).catch(() => {}),
  ]);

  if (await named.isVisible().catch(() => false)) {
    await named.click();

    return;
  }

  await bar.click();
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

  // Register the URL watcher BEFORE clicking to avoid missing fast redirects.
  const navPromise = urlPattern
    ? page.waitForURL(urlPattern, { timeout: 20000 })
    : page.waitForURL((url) => url.toString() !== currentUrl, { timeout: 20000 });

  await clickSave(page, buttonName);

  const toastPromise = page.locator('#app').getByText(regex).first().waitFor({ state: 'visible', timeout: 20000 });

  // Either toast appears OR URL changes (redirect after save).
  // Uses Promise.any — fails only if BOTH timeout (real failure).
  await Promise.any([toastPromise, navPromise]);
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
  clickSave,
  clickSaveAndExpect,
  generateUid,
};
