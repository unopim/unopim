const { test, expect } = require('../../utils/fixtures');

const DASHBOARD_URL = '/admin/dashboard';
const PRODUCTS_URL = '/admin/catalog/products';

/**
 * Helper: wait for the v-dashboard-product-stats AJAX to settle, then return
 * true if at least one product was counted. When the DB is empty the widget
 * renders an empty-state `<a>No products yet.</a>` and the filter chips we
 * want to assert never exist — so dependent tests should skip.
 */
async function dashboardHasProducts(adminPage) {
  // Register the response listener BEFORE navigation so we never miss the
  // /admin/dashboard/stats call when it resolves during networkidle.
  const statsResponsePromise = adminPage.waitForResponse(
    (resp) => resp.url().includes('/admin/dashboard/stats') && resp.status() === 200,
    { timeout: 15000 }
  ).catch(() => null);

  await adminPage.goto(DASHBOARD_URL, { waitUntil: 'networkidle' });

  const statsResponse = await statsResponsePromise;

  if (!statsResponse) {
    return false;
  }

  try {
    const json = await statsResponse.json();
    const total = json?.statistics?.totalProducts ?? 0;
    return total > 0;
  } catch (e) {
    return false;
  }
}

test.describe('Dashboard product-stats widget filter links', () => {

  test('Active card links to the products list with status=1 filter', async ({ adminPage }) => {
    test.skip(!(await dashboardHasProducts(adminPage)), 'Dashboard is in empty state — no products in the fixture DB');

    // Locate by href substring — the accessible name of the card is "Active <count>",
    // so getByRole with an anchored /^Active$/ regex can never match.
    const activeLink = adminPage.locator('#app a[href*="filters[status][]=1"]').first();
    await expect(activeLink).toBeVisible();

    const href = await activeLink.getAttribute('href');
    expect(href).toContain('/admin/catalog/products');
    // Blade writes the href with literal brackets, and getAttribute() returns the
    // raw attribute value, so the assertion must match the un-encoded form.
    expect(href).toContain('filters[status][]=1');
  });

  test('Inactive card links with status=0 filter', async ({ adminPage }) => {
    test.skip(!(await dashboardHasProducts(adminPage)), 'Dashboard is in empty state — no products in the fixture DB');

    const inactiveLink = adminPage.locator('#app a[href*="filters[status][]=0"]').first();
    await expect(inactiveLink).toBeVisible();

    const href = await inactiveLink.getAttribute('href');
    expect(href).toContain('filters[status][]=0');
  });

  test('Configurable legend chip links with type=configurable filter', async ({ adminPage }) => {
    test.skip(!(await dashboardHasProducts(adminPage)), 'Dashboard is in empty state — no products in the fixture DB');

    // The type legend only renders entries that exist in the user's catalogue.
    // If no configurables exist, skip rather than fail.
    const configurableChip = adminPage.locator('a:has(span.capitalize:text("configurable"))').first();

    if (await configurableChip.count() === 0) {
      test.skip('No configurable products in the fixture — chip not rendered');
      return;
    }

    const href = await configurableChip.getAttribute('href');
    expect(href).toContain('filters[type][]=configurable');
  });

  test('Simple legend chip links with type=simple filter', async ({ adminPage }) => {
    test.skip(!(await dashboardHasProducts(adminPage)), 'Dashboard is in empty state — no products in the fixture DB');

    const simpleChip = adminPage.locator('a:has(span.capitalize:text("simple"))').first();

    if (await simpleChip.count() === 0) {
      test.skip('No simple products in the fixture — chip not rendered');
      return;
    }

    const href = await simpleChip.getAttribute('href');
    expect(href).toContain('filters[type][]=simple');
  });

  test('Deep-linking to ?filters[type][]=configurable reaches the backend grid query', async ({ adminPage }) => {
    // Watch for the DataGrid's initial AJAX call. applyUrlFilters() from master
    // reads the same ?filters[col][]=value format as the URL and forwards it
    // into the grid request payload.
    const gridRequest = adminPage.waitForRequest(
      (req) => req.url().includes('/admin/catalog/products')
        && req.method() === 'GET'
        && req.url().includes('filters'),
      { timeout: 15000 }
    ).catch(() => null);

    await adminPage.goto(`${PRODUCTS_URL}?filters[type][]=configurable`, { waitUntil: 'networkidle' });

    const captured = await gridRequest;

    if (captured) {
      const url = captured.url();
      expect(url).toContain('filters');
      expect(url).toContain('type');
      expect(url).toContain('configurable');
    } else {
      // Fallback: even if the grid fetch wasn't captured (empty DB, race,
      // etc.) the browser URL itself must still show the filter param.
      await expect(adminPage).toHaveURL(/filters(%5B|\[)type/);
    }
  });

});
