const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

const REAL_CHANNEL = process.env.API_DEFAULT_CHANNEL || 'default';
const REAL_LOCALE = process.env.API_DEFAULT_LOCALE || 'en_US';

const STUB_READINESS = {
  statistics: [
    { channel: 'pw_readiness_a', locale: 'en_US', total: 10, ready: 3, percentage: 30 },
    { channel: 'pw_readiness_b', locale: 'fr_FR', total: 10, ready: 9, percentage: 90 },
  ],
};

/**
 * Serve a known readiness payload so the drill-down links exist regardless of
 * what the seeded catalog happens to score.
 */
async function stubReadiness(adminPage) {
  await adminPage.route('**/admin/dashboard/stats*', (route) => {
    const url = new URL(route.request().url());

    if (url.searchParams.get('type') === 'channel-readiness') {
      return route.fulfill({ json: STUB_READINESS });
    }

    return route.continue();
  });
}

test.describe('Channel readiness drill-down', () => {

test('each channel links to the products below the readiness threshold in that channel and locale', async ({ adminPage }) => {
  await stubReadiness(adminPage);

  await navigateTo(adminPage, 'dashboard');

  for (const row of STUB_READINESS.statistics) {
    const link = adminPage.locator(`a[href*="channel=${row.channel}"]`).first();

    await expect(link).toBeVisible();

    const href = new URL(await link.evaluate((el) => el.href));

    expect(href.searchParams.get('channel')).toBe(row.channel);
    expect(href.searchParams.get('locale')).toBe(row.locale);
    expect(href.searchParams.get('filters[completeness][operator]')).toBe('lt');
    expect(href.searchParams.get('filters[completeness][value]')).toBe('80');
  }
});

test('the grid request carries the channel from the link, not a stored view scope', async ({ adminPage }) => {
  const [row] = STUB_READINESS.statistics;

  await stubReadiness(adminPage);

  // The grid keys its stored state by the absolute `src` route it was rendered with.
  await adminPage.addInitScript((stale) => {
    window.localStorage.setItem('datagrids', JSON.stringify([{
      src: window.location.origin + '/admin/catalog/products',
      applied: {
        pagination: { page: 1, perPage: 10 },
        sort: {},
        filters: { columns: [] },
      },
      available: { meta: {} },
      viewScope: stale,
    }]));
  }, { channel: 'pw_stale_view_channel', locale: 'de_DE' });

  await navigateTo(adminPage, 'dashboard');

  const link = adminPage.locator(`a[href*="channel=${row.channel}"]`).first();
  await expect(link).toBeVisible();

  // `pagination` is only on the grid's own ajax call, never on the page navigation.
  const gridRequest = adminPage.waitForRequest(
    (req) => /catalog\/products\?/.test(req.url()) && /pagination/.test(req.url()),
    { timeout: 20000 }
  );

  await link.click();

  const url = new URL((await gridRequest).url());

  expect(url.searchParams.get('channel')).toBe(row.channel);
  expect(url.searchParams.get('locale')).toBe(row.locale);
  expect(url.searchParams.get('filters[completeness][0][operator]')).toBe('lt');
  expect(url.searchParams.get('filters[completeness][0][value]')).toBe('80');
});

test('every row the drill-down lists scores below the readiness threshold in that channel', async ({ adminPage }) => {
  // A channel that really resolves, so the grid applies the per-channel column
  // rather than falling back to the cross-channel average.
  const row = { channel: REAL_CHANNEL, locale: REAL_LOCALE, total: 10, ready: 1, percentage: 10 };

  await adminPage.route('**/admin/dashboard/stats*', (route) => {
    const url = new URL(route.request().url());

    if (url.searchParams.get('type') === 'channel-readiness') {
      return route.fulfill({ json: { statistics: [row] } });
    }

    return route.continue();
  });

  await navigateTo(adminPage, 'dashboard');

  const link = adminPage.locator(`a[href*="channel=${row.channel}"]`).first();
  await expect(link).toBeVisible();

  const gridResponse = adminPage.waitForResponse(
    (res) => /catalog\/products\?/.test(res.url()) && /pagination/.test(res.url()),
    { timeout: 20000 }
  );

  await link.click();

  const body = await (await gridResponse).json();

  expect(new URL((await gridResponse).url()).searchParams.get('channel')).toBe(row.channel);

  for (const record of body.records ?? []) {
    // The column renders as a pill; rows without a completeness setting carry no score.
    const score = String(record.completeness ?? '').match(/(\d+(?:\.\d+)?)\s*%/);

    if (score) {
      expect(Number(score[1])).toBeLessThan(80);
    }
  }
});

});
