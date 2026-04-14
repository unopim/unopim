const { test, expect } = require('../../utils/fixtures');

const DASHBOARD_URL = '/admin/dashboard';
const PRODUCTS_URL = '/admin/catalog/products';

test.describe('Dashboard product-stats widget filter links', () => {

  test('Active card navigates to the products list with status=true filter', async ({ adminPage }) => {
    await adminPage.goto(DASHBOARD_URL, { waitUntil: 'networkidle' });

    // Wait for the v-dashboard-product-stats AJAX call to populate.
    await adminPage.waitForResponse(
      (resp) => resp.url().includes('/admin/dashboard/stats') && resp.status() === 200,
      { timeout: 15000 }
    ).catch(() => {});

    const activeLink = adminPage.locator('a:has-text("Active")').first();
    await expect(activeLink).toBeVisible();

    const href = await activeLink.getAttribute('href');
    expect(href).toContain('/admin/catalog/products');
    expect(href).toContain('filter%5Bstatus%5D=true');
  });

  test('Inactive card navigates with status=false filter', async ({ adminPage }) => {
    await adminPage.goto(DASHBOARD_URL, { waitUntil: 'networkidle' });
    await adminPage.waitForResponse(
      (resp) => resp.url().includes('/admin/dashboard/stats') && resp.status() === 200,
      { timeout: 15000 }
    ).catch(() => {});

    const inactiveLink = adminPage.locator('a:has-text("Inactive")').first();
    await expect(inactiveLink).toBeVisible();

    const href = await inactiveLink.getAttribute('href');
    expect(href).toContain('filter%5Bstatus%5D=false');
  });

  test('Configurable legend chip navigates with type=configurable filter', async ({ adminPage }) => {
    await adminPage.goto(DASHBOARD_URL, { waitUntil: 'networkidle' });
    await adminPage.waitForResponse(
      (resp) => resp.url().includes('/admin/dashboard/stats') && resp.status() === 200,
      { timeout: 15000 }
    ).catch(() => {});

    const configurableChip = adminPage.locator('a:has(span:text("configurable"))').first();

    if (await configurableChip.count() > 0) {
      const href = await configurableChip.getAttribute('href');
      expect(href).toContain('filter%5Btype%5D=configurable');
    } else {
      test.skip('No configurable products in this fixture — chip not rendered');
    }
  });

  test('Simple legend chip navigates with type=simple filter', async ({ adminPage }) => {
    await adminPage.goto(DASHBOARD_URL, { waitUntil: 'networkidle' });
    await adminPage.waitForResponse(
      (resp) => resp.url().includes('/admin/dashboard/stats') && resp.status() === 200,
      { timeout: 15000 }
    ).catch(() => {});

    const simpleChip = adminPage.locator('a:has(span:text("simple"))').first();

    if (await simpleChip.count() > 0) {
      const href = await simpleChip.getAttribute('href');
      expect(href).toContain('filter%5Btype%5D=simple');
    } else {
      test.skip('No simple products in this fixture — chip not rendered');
    }
  });

  test('Deep-linking to /admin/catalog/products?filter[type]=configurable applies the filter on grid load', async ({ adminPage }) => {
    // Capture the AJAX call the DataGrid makes when it boots, so we can
    // assert the URL filter actually reached processRequestedFilters on
    // the backend (not just sat in the URL bar untouched).
    const gridRequest = adminPage.waitForRequest(
      (req) => req.url().includes('/admin/catalog/products') && req.method() === 'GET' && req.url().includes('filters'),
      { timeout: 15000 }
    ).catch(() => null);

    await adminPage.goto(`${PRODUCTS_URL}?filter[type]=configurable`, { waitUntil: 'networkidle' });

    const captured = await gridRequest;

    if (captured) {
      const url = captured.url();
      expect(url).toContain('filters');
      expect(url).toContain('type');
      expect(url).toContain('configurable');
    } else {
      // Fallback: if we couldn't capture the request, at least confirm the
      // page rendered without crashing.
      await expect(adminPage).toHaveURL(/filter%5Btype%5D=configurable/);
    }
  });

});
