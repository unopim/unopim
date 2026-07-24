// Digital Product Passport E2E: enable publishing -> create a complete
// product -> publish both locales -> view the public passport -> switch
// locale -> withdraw from the DataGrid -> tombstone renders as 200.
//
// Prerequisite (not part of this spec, run once per environment):
//   docker exec unopim-unopim-fpm-1 php artisan unopim:passport:install-attributes
//
// The Task 13 panel's publish button intentionally ships without real
// locale_ids wiring yet (documented, deferred follow-up in the plan — it
// always posts `locale_ids: []`, which the FormRequest rejects). This spec
// drives the SAME endpoint (`admin.catalog.passports.publish`) via an
// authenticated fetch() from inside the page (reusing the session cookie +
// CSRF token the real button will use once that follow-up lands), rather
// than clicking a button that cannot yet collect real locale ids.
const { test, expect, generateUid, withFamilyPage, deleteFamilyByCode, createDppFamily, createProduct } = require('../fixtures/passport');

/** Read a DataGrid's rows via its own AJAX JSON branch (X-Requested-With). */
async function fetchGridRows(page, path) {
  return page.evaluate(async (url) => {
    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    });
    return res.json();
  }, path);
}

function csrfToken(page) {
  return page.evaluate(() => document.querySelector('meta[name="csrf-token"]')?.content);
}

test.describe.serial('Digital Product Passport', () => {
  let family;
  let product;
  let channelId;
  let channelCode = 'default';
  let localeCodes = [];
  let localeIds = [];
  let publicationUuid;

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(180000);

    await withFamilyPage(browser, async (page) => {
      family = await createDppFamily(page, channelCode);

      const channels = await fetchGridRows(page, '/admin/settings/channels');
      const row = (channels.data ?? channels.rows ?? []).find((r) => r.code === channelCode);
      channelId = row?.id;
    });
  });

  test.afterAll(async ({ browser }) => {
    if (!family) {
      return;
    }
    await withFamilyPage(browser, (page) => deleteFamilyByCode(page, family.code).catch(() => {}));
  });

  test('enable Digital Product Passport publishing via the System Settings hub', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto('/admin/configuration/system/system.product_passport', { waitUntil: 'domcontentloaded' });
    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });

    const enabled = page.getByRole('checkbox', { name: 'Enabled', exact: true }).first();
    if (!(await enabled.isChecked().catch(() => false))) {
      await enabled.check();
    }

    await page.getByRole('textbox', { name: /Completeness Threshold/i }).fill('1');
    await page.getByRole('button', { name: /Save/i }).first().click();
    await expect(page.locator('#app').getByText(/updated successfully/i).first())
      .toBeVisible({ timeout: 20000 });
  });

  test('create a complete product with a dpp value', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `dppprod_${generateUid()}`;
    product = await createProduct(page, family.code, sku);

    // dpp_manufacturer_name is common-bucket (no locale/channel scoping), so
    // one value satisfies completeness for every locale on the channel.
    const manufacturerField = page.locator('input[name="manufacturer_name"], textarea[name="manufacturer_name"]').first();
    await manufacturerField.waitFor({ state: 'visible', timeout: 20000 });
    await manufacturerField.fill('Acme Corp');

    const saveBar = page.getByRole('button', { name: 'Save changes' });
    await saveBar.waitFor({ state: 'visible', timeout: 15000 });
    await saveBar.evaluate((el) => el.click());
    await expect(page.locator('#app').getByText(/updated successfully/i).first())
      .toBeVisible({ timeout: 20000 });

    const status = await fetchGridRows(page, `/admin/catalog/products/${product.id}/passport`);
    localeCodes = (status.rows || []).map((row) => row.locale_code);
    expect(localeCodes.length).toBeGreaterThan(0);

    const locales = await fetchGridRows(page, '/admin/settings/locales');
    localeIds = (locales.data ?? locales.rows ?? [])
      .filter((row) => localeCodes.includes(row.code))
      .map((row) => row.id);
    expect(localeIds.length).toBe(localeCodes.length);
  });

  test('publishes both locales and shows a version number in the panel', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto(`/admin/catalog/products/edit/${product.id}`, { waitUntil: 'domcontentloaded' });
    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });

    const token = await csrfToken(page);

    const publishResponse = await page.evaluate(
      async ({ productId, cid, lids, csrf }) => {
        const res = await fetch(`/admin/catalog/passports/publish/${productId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify({ channel_id: cid, locale_ids: lids }),
        });

        return { status: res.status, body: await res.json().catch(() => null) };
      },
      { productId: product.id, cid: channelId, lids: localeIds, csrf: token },
    );

    expect(publishResponse.status).toBe(200);

    // QUEUE_CONNECTION=sync — the dispatched job has already run by the time
    // the request above returns.
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });

    const status = await fetchGridRows(page, `/admin/catalog/products/${product.id}/passport`);
    const versionedRows = (status.rows || []).filter((row) => row.version != null);
    expect(versionedRows.length).toBe(localeCodes.length);
  });

  test('public passport url redirects to the canonical locale url and renders', async ({ adminPage }) => {
    const page = adminPage;
    const grid = await fetchGridRows(page, '/admin/catalog/passports');
    const row = (grid.data ?? grid.rows ?? []).find((r) => r.sku === product.sku);
    expect(row).toBeTruthy();
    publicationUuid = row.uuid;

    const response = await page.goto(`/p/${publicationUuid}`, { waitUntil: 'domcontentloaded' });
    expect(response.url()).toMatch(new RegExp(`/p/${publicationUuid}/[a-z]{2}_[A-Z]{2}$`));

    await expect(page.getByText('Digital Product Passport', { exact: false }).first()).toBeVisible();

    expect(response.headers()['x-robots-tag']).toContain('noindex');
    expect(response.headers()['content-security-policy']).toContain("default-src 'none'");
  });

  test('switches locale via the template and updates html lang', async ({ adminPage }) => {
    test.skip(localeCodes.length < 2, 'requires at least two published locales');

    const page = adminPage;
    const current = await page.getAttribute('html', 'lang');
    const other = localeCodes.find((code) => code !== current);

    await page.getByRole('link', { name: other, exact: true }).click();
    await expect(page.locator('html')).toHaveAttribute('lang', other, { timeout: 15000 });
  });

  test('withdrawing keeps the public url at 200 with a tombstone', async ({ adminPage }) => {
    const page = adminPage;
    const grid = await fetchGridRows(page, '/admin/catalog/passports');
    const row = (grid.data ?? grid.rows ?? []).find((r) => r.uuid === publicationUuid);

    const token = await csrfToken(page);
    const withdrawResponse = await page.evaluate(
      async ({ id, csrf }) => {
        const res = await fetch(`/admin/catalog/passports/withdraw/${id}`, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
          },
        });

        return res.status;
      },
      { id: row.id, csrf: token },
    );

    expect(withdrawResponse).toBe(200);

    const response = await page.goto(`/p/${publicationUuid}/${localeCodes[0]}`, { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(200);
    await expect(page.getByText(/no longer available/i).first()).toBeVisible();
  });
});
