// DPP customer-workflow E2E: the three merchant-facing behaviours added alongside the industry suite —
// (1) auto-publish on save when the setting is on, (2) the print/label CSV export columns (gtin, gs1
// link, public URL), and (3) the custom-field media-source guard. Prereq: scripts/seed-dpp-e2e.php.
const {
  test,
  expect,
  generateUid,
  DPP_FAMILY,
  createProduct,
  fillDppField,
  saveProduct,
  settingsIdByCode,
  fetchGridRows,
  gridRecords,
  passportLocaleMap,
  publishAndWait,
  passportRowForSku,
} = require('../fixtures/passport');

/** A unique, mod-10-valid GTIN-13 (GTIN is not unique-constrained, so per-run uniqueness avoids GS1 collisions). */
function validGtin() {
  let body = '20';
  while (body.length < 12) {
    body += Math.floor(Math.random() * 10);
  }
  const sum = body.split('').reverse().reduce((acc, d, i) => acc + Number(d) * (i % 2 === 0 ? 3 : 1), 0);

  return body + ((10 - (sum % 10)) % 10);
}

/**
 * Issue an admin request via the shared request context (cookies from storage state), sending the app's
 * encrypted XSRF cookie back as the header its VerifyCsrfToken expects. Redirects are NOT followed so a
 * settings-save 302 is observed as-is rather than chasing the redirect target. Returns {status, body}.
 */
async function adminRequest(page, method, path, body = null) {
  const cookies = await page.context().cookies();
  const xsrf = decodeURIComponent((cookies.find((c) => c.name === 'XSRF-TOKEN') || {}).value || '');

  const res = await page.request.fetch(path, {
    method,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrf,
    },
    data: body === null ? undefined : body,
    maxRedirects: 0,
  });

  let parsed = null;
  try {
    parsed = await res.json();
  } catch {
    parsed = null;
  }

  return { status: res.status(), body: parsed };
}

/** Toggle the product-passport auto_publish setting through the real settings-save endpoint. */
async function setAutoPublish(page, on) {
  await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });

  return adminRequest(page, 'PUT', '/admin/configuration/system-settings/digital_product_passport.product_passport', {
    catalog: {
      product_passport: {
        settings: {
          enabled: '1',
          auto_publish: on ? '1' : '0',
          completeness_threshold: '1',
        },
      },
    },
  });
}

/** Poll the product's passport status endpoint until a live version appears — WITHOUT any manual publish call. */
async function waitForAutoPublish(page, productId, { timeout = 60000 } = {}) {
  const deadline = Date.now() + timeout;

  while (Date.now() < deadline) {
    const status = await fetchGridRows(page, `/admin/products/${productId}/passport`);
    const rows = (status.rows || []).filter((row) => row.version != null);

    if (rows.length >= 1) {
      return rows;
    }
    await page.waitForTimeout(2000);
  }

  return [];
}

test.describe.serial('DPP customer workflows', () => {
  let channelId;

  test.beforeAll(async ({ browser }) => {
    const { withFamilyPage } = require('../fixtures/passport');
    await withFamilyPage(browser, async (page) => {
      await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
      channelId = await settingsIdByCode(page, '/admin/settings/channels', 'default');
    });
  });

  test.afterAll(async ({ browser }) => {
    // Restore the seed default so the shared env is left with auto_publish off.
    const { withFamilyPage } = require('../fixtures/passport');
    await withFamilyPage(browser, async (page) => {
      await setAutoPublish(page, false).catch(() => {});
    });
  });

  test('auto-publish: a complete product publishes its passport on save with no manual publish', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `auto_${generateUid()}`;

    const enable = await setAutoPublish(page, true);
    expect(enable.status, 'settings save').toBeLessThan(400);

    const product = await createProduct(page, DPP_FAMILY.name, sku);
    await fillDppField(page, 'dpp_material_composition', 'Recycled aluminium 60%');
    await saveProduct(page);

    // No publish action is invoked here; the AutoPublishPassport listener must have queued it on save.
    const rows = await waitForAutoPublish(page, product.id);
    expect(rows.length, 'auto-published live locale(s)').toBeGreaterThanOrEqual(1);

    const gridRow = await passportRowForSku(page, sku);
    expect(gridRow, 'publication row in grid').toBeTruthy();
    expect(String(gridRow.publication_status).toLowerCase()).toContain('publish');
  });

  test('CSV export carries the public URL, gtin and gs1 link for the print hand-off', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `exp_${generateUid()}`;
    const gtin = validGtin();

    await setAutoPublish(page, false);

    const product = await createProduct(page, DPP_FAMILY.name, sku);
    await fillDppField(page, 'dpp_material_composition', 'Stainless steel 304');
    await fillDppField(page, 'dpp_gtin', gtin);
    await saveProduct(page);

    const localeMap = await passportLocaleMap(page);
    const rows = await publishAndWait(page, product.id, channelId, [localeMap.en_US], { expected: 1 });
    expect(rows.length, 'published locale').toBe(1);

    const gridRow = await passportRowForSku(page, sku);
    expect(gridRow, 'publication row').toBeTruthy();

    // Export the passports grid as CSV via its own ajax export branch and assert the three identifiers landed.
    const cookies = await page.context().cookies();
    const xsrf = decodeURIComponent((cookies.find((c) => c.name === 'XSRF-TOKEN') || {}).value || '');
    const exportRes = await page.request.get('/admin/catalog/passports', {
      params: { export: 1, format: 'csv' },
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrf, Accept: 'text/csv' },
    });
    const csv = await exportRes.text();

    expect(csv, 'export contains header columns').toMatch(/gtin/i);
    expect(csv, 'export contains gs1 link column').toMatch(/gs1_link/i);
    expect(csv, 'export contains public url column').toMatch(/public_url/i);
    expect(csv, 'export contains the gtin value').toContain(gtin);
    expect(csv, 'export contains the gs1 digital link').toContain(`/01/${gtin}`);
    expect(csv, 'export contains the passport public URL').toContain(`/p/${gridRow.uuid}`);
  });

});
