const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

const STUB_STATS = {
  statistics: {
    totalProducts: 100,
    typeDistribution: {
      configurable: 20,
      simple: 50,
      variant_group: 20,
      custom_kit: 10,
    },
    statusBreakdown: { active: 100, inactive: 0 },
    newThisWeek: 0,
    withVariants: 20,
    avgCompleteness: 75,
    enrichedThisWeek: 0,
    enrichedLastWeek: 0,
  },
};

test.describe('Product Statistics — type distribution', () => {

test('legend swatches use a distinct colour per product type', async ({ adminPage }) => {
  await adminPage.route('**/admin/dashboard/stats*', (route) => {
    const url = new URL(route.request().url());

    if (url.searchParams.get('type') === 'product-stats') {
      return route.fulfill({ json: STUB_STATS });
    }

    return route.continue();
  });

  await adminPage.goto('/admin/dashboard', { waitUntil: 'load' });

  const legendLinks = adminPage.locator('a[href*="filters[type][]"]');
  await expect(legendLinks).toHaveCount(Object.keys(STUB_STATS.statistics.typeDistribution).length * 2);

  const swatches = legendLinks.locator('span.rounded-sm');
  const colours = [];

  for (let i = 0; i < await swatches.count(); i++) {
    colours.push(await swatches.nth(i).evaluate((el) => getComputedStyle(el).backgroundColor));
  }

  expect(new Set(colours).size).toBe(Object.keys(STUB_STATS.statistics.typeDistribution).length);
});

test('clicking a type chip lands on the product grid with the type filter applied', async ({ adminPage }) => {
  await navigateTo(adminPage, 'dashboard');

  const chip = adminPage.locator('a[href*="filters[type][]"]').last();
  await expect(chip).toBeVisible();

  const type = new URL(await chip.evaluate((el) => el.href)).searchParams.get('filters[type][]');

  const gridRequest = adminPage.waitForRequest(
    (req) => /catalog\/products\?/.test(req.url()) && /filters%5Btype%5D/.test(req.url()),
    { timeout: 15000 }
  );

  await chip.click();

  const url = new URL((await gridRequest).url());
  expect(url.searchParams.get('filters[type][0]')).toBe(type);

  await expect(adminPage).toHaveURL(new RegExp('filters\\[type\\]\\[\\]=' + type));
});

});
