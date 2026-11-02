// utils/helpers.js — Shared Playwright test utilities for UnoPim E2E tests

/**
 * Admin route map — direct URLs for all admin sections.
 * Use navigateTo() instead of clicking through the sidebar.
 */
const ROUTES = {
  dashboard:         '/admin/dashboard',
  products:          '/admin/catalog/products',
  categories:        '/admin/catalog/categories',
  categoriesList:    '/admin/catalog/categories?view=list',
  categoryFields:    '/admin/catalog/category-fields',
  attributes:        '/admin/catalog/attributes',
  attributeGroups:   '/admin/catalog/attribute-groups',
  attributeFamilies: '/admin/catalog/attribute-families',
  associationTypes:  '/admin/catalog/association-types',
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
 * Wait until the admin page is usable: the document has loaded, the Vue app is
 * mounted (an ajax navigation has finished swapping and remounting #app), and
 * no loading shimmer is left on screen (datagrid records, dashboard widgets…).
 * Image placeholders are ignored: a missing image keeps its shimmer forever.
 *
 * This replaces `networkidle`, which never settles reliably here because the
 * header polls notifications and costs at least 500ms on every call.
 * @param {import('@playwright/test').Page} page
 * @param {number} [timeout=30000]
 */
async function waitForAppReady(page, timeout = 30000) {
  await page.waitForLoadState('load', { timeout });
  await page.waitForFunction(() => {
    const bar = document.getElementById('ajax-nav-progress');

    return (!bar || bar.style.opacity === '0') && !!document.querySelector('#app[data-v-app]');
  }, null, { timeout });
  await waitForShimmerGone(page, timeout);
}

/**
 * Wait until no DataGrid/widget loading shimmer is visible (image placeholders ignored).
 * @param {import('@playwright/test').Page} page
 * @param {number} [timeout=15000]
 */
async function waitForShimmerGone(page, timeout = 15000) {
  await page.waitForFunction(() => ![...document.querySelectorAll('.shimmer')].some(
    (el) => el.getClientRects().length > 0 && el.nextElementSibling?.tagName !== 'IMG',
  ), null, { timeout });
}

/**
 * Open an admin URL and wait for the page to be ready.
 * @param {import('@playwright/test').Page} page
 * @param {string} url
 */
async function gotoPage(page, url) {
  await page.goto(url, { waitUntil: 'load', timeout: 60000 });
  await waitForAppReady(page);
}

/**
 * Navigate directly to an admin route (replaces 2-click sidebar navigation).
 * @param {import('@playwright/test').Page} page
 * @param {keyof ROUTES} route — key from ROUTES map
 */
async function navigateTo(page, route) {
  const url = ROUTES[route];
  if (!url) throw new Error(`Unknown route: "${route}". Available: ${Object.keys(ROUTES).join(', ')}`);
  await gotoPage(page, url);
}

/**
 * Whether a response is the DataGrid's own XHR refresh carrying the given search text.
 * @param {import('@playwright/test').Response} response
 * @param {string} text
 * @returns {boolean}
 */
function isDataGridSearchResponse(response, text) {
  const request = response.request();

  if (request.method() !== 'GET' || !['xhr', 'fetch'].includes(request.resourceType())) {
    return false;
  }

  let url = response.url().replace(/\+/g, ' ');

  try {
    url = decodeURIComponent(url);
  } catch {
    return false;
  }

  return url.includes('filters') && url.includes(text);
}

/**
 * Submit a search typed into a DataGrid search input and wait for the grid to
 * render the refreshed records.
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} input — the DataGrid search input
 * @param {string} text — search query
 */
async function submitDataGridSearch(page, input, text) {
  await input.fill(text);

  const refreshed = page.waitForResponse((response) => isDataGridSearchResponse(response, text), { timeout: 30000 });
  await input.press('Enter');
  await refreshed;
  await waitForShimmerGone(page);
}

/**
 * Search in a DataGrid using the search input and wait for the grid to show the result.
 * @param {import('@playwright/test').Page} page
 * @param {string} text — search query
 * @param {string} [placeholder='Search'] — placeholder text of search input
 */
async function searchInDataGrid(page, text, placeholder = 'Search') {
  const searchInput = page.getByPlaceholder(placeholder).first();
  await searchInput.waitFor({ state: 'visible', timeout: 30000 });
  await submitDataGridSearch(page, searchInput, text);
}

/**
 * Click something that navigates (full load or ajax navigation) and wait for
 * the destination page to be ready.
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} target
 * @param {RegExp} [urlPattern] — destination URL; any URL change when omitted
 */
async function clickAndWaitForPage(page, target, urlPattern) {
  const from = page.url();
  await target.click();
  await page.waitForURL(urlPattern ?? ((url) => url.toString() !== from), { timeout: 30000 });
  await waitForAppReady(page);
}

/**
 * Click a submit control and wait for its outcome: the request it sends gets
 * answered, or client-side validation blocks it and shows a field error.
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} submit
 * @param {(request: import('@playwright/test').Request) => boolean} isSubmitRequest
 * @returns {Promise<import('@playwright/test').Response|null>} the response, or null when validation blocked it
 */
async function submitAndSettle(page, submit, isSubmitRequest) {
  const answered = page.waitForResponse((response) => isSubmitRequest(response.request()), { timeout: 30000 });
  const fieldError = page.locator('p.text-red-600:visible').filter({ hasText: /\S/ }).first();
  await submit.click();

  return Promise.race([
    answered,
    fieldError.waitFor({ state: 'visible', timeout: 30000 }).then(() => null),
  ]);
}

/**
 * Click the Edit action button on a DataGrid row matching the given text and
 * wait for the edit page it opens to be ready.
 * @param {import('@playwright/test').Page} page
 * @param {string} rowText — text to identify the row
 */
async function clickEditOnRow(page, rowText) {
  // Scope to an actual DataGrid record row (div.row.grid.cursor-pointer) so the
  // Edit icon belongs to the matched row and not to some earlier row folded into
  // a broader container match.
  const row = page.locator('div.row.grid.cursor-pointer').filter({ hasText: rowText }).first();
  await clickAndWaitForPage(page, row.locator('span[title="Edit"]').first());
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
  // Scoped to the confirm modal: the AI agent shell's suggestion cards can also
  // carry "Delete" in their accessible name (e.g. "Delete Products Remove"),
  // which turns an unscoped match ambiguous.
  const deleted = page.waitForResponse(
    (response) => ['DELETE', 'POST'].includes(response.request().method()) && ['xhr', 'fetch'].includes(response.request().resourceType()),
    { timeout: 30000 },
  );
  await page.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();
  await deleted;
  await waitForAppReady(page);
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

/**
 * Fill a localized name field regardless of how the page renders it: the
 * quick-create modals expose a visible `<locale>[name]` input, while full
 * pages use the translatable-field component whose named inputs are hidden
 * behind an unnamed visible editor.
 * @param {import('@playwright/test').Page} page
 * @param {string} value
 * @param {string} field
 */
async function fillLocalizedField(page, value, field = 'name') {
  const named = page.locator(`input[name$="[${field}]"]`).first();
  await named.waitFor({ state: 'attached', timeout: 20000 });

  if (await named.isVisible().catch(() => false)) {
    await named.fill(value);
    return;
  }

  await named.locator('..').locator('input[type="text"]').first().fill(value);
}

/**
 * Resolve a real, editable simple product id from the live catalog instead of
 * hardcoding one: demo-data rebuilds renumber/replace products, so a fixed id
 * can end up pointing at a deleted row or an edge-case fixture (no name,
 * configurable parent, etc.) left behind by another test.
 * @param {import('@playwright/test').Page} page — an already-authenticated admin page
 * @returns {Promise<number>} a product id safe to open on the edit page
 */
async function resolveEditableProductId(page) {
  await navigateTo(page, 'products');

  const grid = await page.evaluate(async () => {
    const response = await fetch(window.location.origin + '/admin/catalog/products?pagination[perPage]=50', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });

    return response.json();
  });

  const record = (grid.records || []).find((row) => row.type === 'Simple' && row.name);

  if (!record) {
    throw new Error('resolveEditableProductId: no Simple product with a name found in the catalog');
  }

  return record.product_id;
}

/**
 * The toolbar collapses the filter label to an icon once it measures narrow, so
 * the drawer is opened through the toggle itself rather than its label text.
 */
async function openFilterDrawer(page) {
  await page.locator('[data-grid-filter]').first().click();
}

/**
 * Dismiss an open select dropdown. Escape closes the surrounding modal as well,
 * so the dropdown is closed by blurring the field instead.
 */
async function closeDropdown(page) {
  await page.evaluate(() => {
    if (document.activeElement instanceof HTMLElement) {
      document.activeElement.blur();
    }
  });
}

/**
 * The first and last page controls collapse out of a narrow toolbar, so the
 * symbols a grid renders depend on the toolbar's measured width.
 */
async function visiblePaginationSymbols(page) {
  const toolbar = page.locator('.datagrid-toolbar').first();

  await toolbar.waitFor({ state: 'visible' });

  return await toolbar.evaluate((el) => el.classList.contains('is-tight'))
    ? ['\u2039', '\u203a']
    : ['\u00ab', '\u2039', '\u203a', '\u00bb'];
}

module.exports = {
  ROUTES,
  waitForAppReady,
  waitForShimmerGone,
  gotoPage,
  navigateTo,
  openFilterDrawer,
  closeDropdown,
  visiblePaginationSymbols,
  submitDataGridSearch,
  searchInDataGrid,
  clickAndWaitForPage,
  submitAndSettle,
  clickEditOnRow,
  clickDeleteOnRow,
  confirmDelete,
  expectSuccessToast,
  clickSave,
  clickSaveAndExpect,
  generateUid,
  fillLocalizedField,
  resolveEditableProductId,
};
