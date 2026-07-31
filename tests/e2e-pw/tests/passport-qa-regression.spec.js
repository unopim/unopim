// One test per QA-reported passport defect, driven through the real admin UI.
//   1. grid ✕ withdraw did nothing visible (no flash, no row change)
//   2. publishing a locale into a withdrawn passport looked like it worked
//   3. Live Locales counted locales the public could not reach
//   4. no way back from Withdrawn in the admin
//   5. role tree was missing the passport template permissions
//   6. QR carrier encoded a link that could not resolve
//   7. public locale switcher offered locales that were never published
const {
  test,
  expect,
  generateUid,
  ensureDppFamily,
  createProduct,
  fillDppField,
  saveProduct,
  fetchGridRows,
  settingsIdByCode,
  passportLocaleIds,
  publishAndWait,
  passportRowForSku,
  gotoProductEdit,
  withFamilyPage,
  adminPost,
} = require('../fixtures/passport');

/**
 * The publications grid row for a SKU, read through the grid's own JSON branch.
 * `publication_status` arrives as the translated label, not the raw enum, because
 * the column carries a closure. The fetch is relative, so the page has to be on
 * the app's origin first — a fixture page starts on about:blank.
 */
async function gridRow(page, sku) {
  if (! page.url().startsWith('http')) {
    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
  }

  const grid = await fetchGridRows(page, `/admin/catalog/passports?${new URLSearchParams({ 'filters[all][]': sku })}`);

  return (grid.records ?? []).find((row) => row.sku === sku);
}

/** Click a row action by title, confirm the modal, and return the flash payloads emitted. */
async function runRowAction(page, sku, title) {
  await page.goto('/admin/catalog/passports', { waitUntil: 'domcontentloaded' });
  await page.getByRole('textbox', { name: 'Search' }).fill(sku);
  await page.getByRole('textbox', { name: 'Search' }).press('Enter');

  const row = page.locator('.row', { hasText: sku }).first();
  await row.locator(`span[title='${title}']`).waitFor({ state: 'visible', timeout: 15000 });

  const flashes = await page.evaluate(async ({ title }) => {
    const wait = (ms) => new Promise((r) => setTimeout(r, ms));
    const seen = [];
    window.app.config.globalProperties.$emitter.on('add-flash', (flash) => seen.push(flash));

    document.querySelector(`span[title='${title}']`).click();
    await wait(500);
    [...document.querySelectorAll('button.primary-button')].find((b) => b.textContent.trim() === 'Agree').click();
    await wait(3000);

    return seen;
  }, { title });

  return flashes;
}

test.describe.serial('Passport QA regressions', () => {
  let family;
  let product;
  let channelId;
  let localeIds = [];
  let localeCodes = [];
  let uuid;

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(120000);

    family = await ensureDppFamily();

    await withFamilyPage(browser, async (page) => {
      await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
      channelId = await settingsIdByCode(page, '/admin/settings/channels', 'default');
    });
  });

  test('publishes a passport to work against', async ({ adminPage }) => {
    const page = adminPage;

    product = await createProduct(page, family.name, `qa_${generateUid()}`);
    await fillDppField(page, 'dpp_manufacturer_name', 'Acme Corp');
    await saveProduct(page);

    const status = await fetchGridRows(page, `/admin/products/${product.id}/passport`);
    localeCodes = (status.rows || []).map((row) => row.locale_code);
    localeIds = await passportLocaleIds(page);

    await gotoProductEdit(page, product.id);
    const versioned = await publishAndWait(page, product.id, channelId, localeIds);
    expect(versioned.length).toBe(localeCodes.length);

    const row = await passportRowForSku(page, product.sku);
    uuid = row.uuid;
    expect(row.publication_status).toMatch(/^published$/i);
  });

  test('1+3. withdrawing flashes success, flips the row and zeroes Live Locales', async ({ adminPage }) => {
    const page = adminPage;

    const flashes = await runRowAction(page, product.sku, 'Withdraw');

    expect(flashes.map((f) => f.type)).toContain('success');
    expect(flashes.map((f) => f.message).join(' ')).toMatch(/withdrawn successfully/i);

    const row = await gridRow(page, product.sku);
    expect(row.publication_status).toMatch(/^withdrawn$/i);
    expect(Number(row.live_locale_count)).toBe(0);
  });

  test('4. the withdrawn row offers Reinstate and not Withdraw', async ({ adminPage }) => {
    const row = await gridRow(adminPage, product.sku);
    const actions = row.actions.map((action) => action.index);

    expect(actions).toContain('reinstate');
    expect(actions).not.toContain('withdraw');
  });

  test('2. the product panel blocks publishing while the passport is withdrawn', async ({ adminPage }) => {
    const page = adminPage;
    await gotoProductEdit(page, product.id);

    const panel = page.locator('#passport-panel');
    await panel.waitFor({ state: 'attached', timeout: 30000 });

    const state = await page.evaluate(() => ({
      notice: document.querySelector('#passport-panel')?.innerText ?? '',
      buttons: [...document.querySelectorAll('.passport-publish-btn, .passport-publish-all-btn')]
        .map((b) => ({ disabled: b.disabled, title: b.title })),
    }));

    expect(state.notice).toMatch(/reinstate it before publishing/i);
    expect(state.buttons.length).toBeGreaterThan(0);
    expect(state.buttons.every((b) => b.disabled)).toBe(true);
  });

  test('2. the publish endpoint refuses while the passport is withdrawn', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });

    const response = await adminPost(page, `/admin/catalog/passports/publish/${product.id}`, {
      channel_id: channelId,
      locale_ids: localeIds,
    });

    expect(response.status).toBe(422);
    expect(response.body.message).toMatch(/reinstate/i);
  });

  test('6+7. the withdrawn passport still renders a tombstone per published locale', async ({ adminPage }) => {
    const page = adminPage;

    const response = await page.goto(`/p/${uuid}/${localeCodes[0]}`, { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(200);

    const offered = await page.evaluate(() =>
      [...document.querySelectorAll('.locale-list a')].map((a) => a.textContent.trim()));

    expect(offered.sort()).toEqual([...localeCodes].sort());

    for (const code of offered) {
      const hit = await page.goto(`/p/${uuid}/${code}`, { waitUntil: 'domcontentloaded' });
      expect(hit.status(), `locale ${code} must resolve`).toBe(200);
    }
  });

  test('4. reinstating restores the passport and its Live Locales', async ({ adminPage }) => {
    const page = adminPage;

    const flashes = await runRowAction(page, product.sku, 'Reinstate');

    expect(flashes.map((f) => f.type)).toContain('success');

    const row = await gridRow(page, product.sku);
    expect(row.publication_status).toMatch(/^published$/i);
    expect(Number(row.live_locale_count)).toBe(localeCodes.length);
  });

  test('6. the QR carrier serves an SVG and its GS1 link resolves', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });

    const carrier = await page.request.get(`/p/${uuid}/carrier`);
    expect(carrier.status()).toBe(200);
    expect(carrier.headers()['content-type']).toContain('image/svg+xml');

    const row = await gridRow(page, product.sku);

    // No GTIN on this fixture, so the column stays empty and the QR falls back to
    // the passport url — the fallback is what must never 404.
    if (row.gs1_link) {
      const gs1 = await page.request.get(row.gs1_link, { maxRedirects: 0 });
      expect([301, 302]).toContain(gs1.status());
    }

    const landing = await page.request.get(`/p/${uuid}`, { maxRedirects: 0 });
    expect([301, 302]).toContain(landing.status());
  });

  test('5. the role permission tree exposes the passport template permissions', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto('/admin/settings/roles/create', { waitUntil: 'domcontentloaded' });

    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
    await page.getByRole('textbox', { name: /^Name/ }).first().fill(`qa_role_${generateUid()}`);

    const labels = await page.evaluate(() =>
      [...document.querySelectorAll('.v-tree-item label, .v-tree-item span')]
        .map((e) => e.textContent.trim())
        .filter(Boolean));

    expect(labels).toContain('Templates');
    expect(labels).toEqual(expect.arrayContaining([
      'View Templates', 'Create Template', 'Edit Template', 'Delete Template',
    ]));
  });
});
