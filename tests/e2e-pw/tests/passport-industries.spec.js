// DPP industry use-case E2E: ten realistic Digital Product Passport workflows, each exercising a
// different set of dpp fields/paths end to end (create in the canonical `dpp_e2e` family → fill →
// publish → verify public output). Prereq: scripts/seed-dpp-e2e.php (see fixtures/passport.js header).
const path = require('path');
const {
  test,
  expect,
  generateUid,
  withFamilyPage,
  DPP_FAMILY,
  createProduct,
  fillDppField,
  uploadDppFile,
  saveProduct,
  settingsIdByCode,
  passportLocaleMap,
  passportRowLinks,
  publishAndWait,
  passportRowForSku,
  withdrawViaFetch,
  adminPost,
} = require('../fixtures/passport');

const SAMPLE_PDF = path.resolve(__dirname, '../fixtures/files/sample.pdf');

/** A unique, mod-10-valid GTIN-13 (GTIN is not unique-constrained, so per-run uniqueness avoids GS1 collisions). */
function validGtin() {
  let body = '20';
  while (body.length < 12) {
    body += Math.floor(Math.random() * 10);
  }
  const sum = body.split('').reverse().reduce((acc, d, i) => acc + Number(d) * (i % 2 === 0 ? 3 : 1), 0);

  return body + ((10 - (sum % 10)) % 10);
}

test.describe.serial('DPP industry use cases', () => {
  let channelId;

  test.beforeAll(async ({ browser }) => {
    await withFamilyPage(browser, async (page) => {
      await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
      channelId = await settingsIdByCode(page, '/admin/settings/channels', 'default');
    });
  });

  /**
   * Create a product in the dpp family, fill it via `fill`, save, and publish the requested locales.
   * Returns {product, uuid, localeMap}. Runs entirely on the admin origin so relative fetches work.
   */
  async function publishScenario(page, sku, fill, { locales = ['en_US'] } = {}) {
    const product = await createProduct(page, DPP_FAMILY.name, sku);
    await fill(page);
    await saveProduct(page);

    const localeMap = await passportLocaleMap(page);
    const ids = locales.map((code) => localeMap[code]).filter(Boolean);
    const rows = await publishAndWait(page, product.id, channelId, ids, { expected: ids.length });
    expect(rows.length, `expected ${ids.length} live locale(s)`).toBe(ids.length);

    const row = await passportRowForSku(page, product.sku);
    expect(row, 'publication row').toBeTruthy();

    return { product, uuid: row.uuid, localeMap };
  }

  /** Fetch a public passport page's text (authenticated context is fine; public routes need no auth). */
  async function publicText(page, uuid, locale = 'en_US') {
    const res = await page.goto(`/p/${uuid}/${locale}`, { waitUntil: 'domcontentloaded' });

    return { status: res.status(), body: await page.content() };
  }

  test('1. Textiles — material composition, care, recycled content on the public page', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `tex_${generateUid()}`;

    const { uuid } = await publishScenario(page, sku, async () => {
      await fillDppField(page, 'dpp_material_composition', 'Recycled cotton 80%, elastane 20%');
      await fillDppField(page, 'dpp_care_instructions', 'Machine wash cold, do not tumble dry');
      await fillDppField(page, 'dpp_recycled_content_pct', '80');
    });

    const { body } = await publicText(page, uuid);
    expect(body).toContain('Recycled cotton 80%, elastane 20%');
    expect(body).toContain('Machine wash cold, do not tumble dry');
    expect(body).toContain('80');
  });

  test('2. Electronics — energy, substances, disassembly document + private-disk asset serving', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `ele_${generateUid()}`;

    const { uuid } = await publishScenario(page, sku, async () => {
      await fillDppField(page, 'dpp_energy_consumption', '42 kWh/annum');
      await fillDppField(page, 'dpp_substances_of_concern', 'Lead-free solder; no SVHC above 0.1%');
      await uploadDppFile(page, 'dpp_disassembly_guide', SAMPLE_PDF);
    });

    const { body } = await publicText(page, uuid);
    expect(body).toContain('42 kWh/annum');

    // The disassembly guide is a consumer-tier document: its asset link must render and serve the PDF.
    const assetHref = await page.locator('.docs a').first().getAttribute('href');
    expect(assetHref, 'document link on public page').toContain(`/p/${uuid}/asset/`);

    const asset = await page.request.get(assetHref);
    expect(asset.status()).toBe(200);
    expect(asset.headers()['content-type']).toContain('pdf');
  });

  test('3. Batteries — carbon footprint, take-back, GTIN → GS1 /01 resolver', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `bat_${generateUid()}`;
    const gtin = validGtin(); // unique, mod-10-valid GTIN-13

    const { uuid } = await publishScenario(page, sku, async () => {
      await fillDppField(page, 'dpp_carbon_footprint', '12.5 kg CO2e');
      await fillDppField(page, 'dpp_take_back_scheme', 'Return to any retailer under the EU battery directive');
      await fillDppField(page, 'dpp_gtin', gtin);
    });

    const { body } = await publicText(page, uuid);
    expect(body).toContain('12.5 kg CO2e');
    expect(body).toContain(gtin);

    // The GS1 Digital Link resolves the scanned GTIN to this passport (proves gtin/alias sync on publish).
    const gs1 = await page.request.get(`/01/${gtin}`, { maxRedirects: 0 });
    expect([301, 302]).toContain(gs1.status());
    expect(gs1.headers().location).toContain(`/p/${uuid}/`);
  });

  test('4. Furniture — durability + multi-locale publish and locale switch', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `fur_${generateUid()}`;

    const published = ['en_US', 'fr_FR'];

    const { uuid } = await publishScenario(page, sku, async () => {
      await fillDppField(page, 'dpp_durability_statement', 'Rated for 10 years of domestic use');
      await fillDppField(page, 'dpp_model_identifier', 'CHAIR-2026');
    }, { locales: published });

    // en_US shows the durability statement; the model id (common) shows in every locale's identifier block.
    const en = await publicText(page, uuid, 'en_US');
    expect(en.body).toContain('Rated for 10 years of domestic use');
    expect(en.body).toContain('CHAIR-2026');

    // Only the locales we actually published are live; the panel's locale map
    // also lists channel locales that were never requested (de_DE in demo data).
    const other = published.find((code) => code !== 'en_US');
    if (other) {
      const alt = await publicText(page, uuid, other);
      expect(alt.status).toBe(200);
      expect(alt.body).toContain('CHAIR-2026');

      // Switch locale via the template switcher and confirm html lang follows.
      await page.goto(`/p/${uuid}`, { waitUntil: 'domcontentloaded' });
      const current = await page.getAttribute('html', 'lang');
      const target = current === 'en_US' ? other : 'en_US';
      await page.locator('.switcher summary').click();
      await page.locator('.switcher .locale-list').getByRole('link', { name: target, exact: true }).click();
      await expect(page.locator('html')).toHaveAttribute('lang', target, { timeout: 15000 });
    }
  });

  test('5. Footwear — country of origin publishes under its template field label', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `ftw_${generateUid()}`;

    const { uuid } = await publishScenario(page, sku, async () => {
      await fillDppField(page, 'dpp_repairability_score', '8.5 / 10');
      await fillDppField(page, 'dpp_country_of_origin', 'Portugal');
    });

    const { body } = await publicText(page, uuid);
    // The attribute-sourced value surfaces under the template field's own label.
    expect(body).toMatch(/Country Of Origin/i);
    expect(body).toContain('Portugal');
    expect(body).toContain('8.5 / 10');
  });

  test('6. Cosmetics — substances publish on the page and as a JSON-LD additionalProperty', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `cos_${generateUid()}`;

    const { uuid } = await publishScenario(page, sku, async () => {
      await fillDppField(page, 'dpp_substances_of_concern', 'No parabens; contains fragrance allergens');
    });

    const { body } = await publicText(page, uuid);
    expect(body).toContain('No parabens; contains fragrance allergens');

    // Every published template field mirrors into JSON-LD under its own label.
    const jsonld = await page.request.get(`/p/${uuid}/en_US`, { headers: { Accept: 'application/ld+json' } });
    const graph = await jsonld.json();
    const props = (graph.additionalProperty || []).map((p) => `${p.name}=${p.value}`);
    expect(props.join('|')).toContain('No parabens; contains fragrance allergens');
  });

  test('7. Toys — certificate document hidden from consumers, revealed on a signed authority URL', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `toy_${generateUid()}`;

    const { uuid, product } = await publishScenario(page, sku, async () => {
      await fillDppField(page, 'dpp_material_composition', 'ABS plastic, CE tested');
      await uploadDppFile(page, 'dpp_certificates', SAMPLE_PDF);
    });

    // Reload the edit page so the panel re-renders with the now-live version and its signed links.
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
    const links = await passportRowLinks(page, 'en_US');
    expect(links.authority, 'signed authority link').toBeTruthy();

    // Consumer page: the authority-tier certificate document must NOT appear.
    const consumer = await publicText(page, uuid);
    expect(consumer.body).not.toContain('Certificates');

    const authority = await page.request.get(links.authority);
    expect(authority.status()).toBe(200);
    expect(await authority.text()).toContain('Certificates');
  });

  test('8. Automotive — GTIN validation rejects an invalid check digit, accepts a valid one', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `auto_${generateUid()}`;

    const product = await createProduct(page, DPP_FAMILY.name, sku);
    await fillDppField(page, 'dpp_batch_identifier', 'LOT-2026-07');
    await fillDppField(page, 'dpp_gtin', '4006381333930'); // invalid check digit

    // Save must be rejected by the GTIN validator (catalog.product.update.before listener).
    await page.locator('[data-unsaved-save]').first().click();
    await expect(page.locator('#app').getByText(/valid GTIN/i).first()).toBeVisible({ timeout: 20000 });

    // Correct it and the save succeeds.
    await fillDppField(page, 'dpp_gtin', validGtin());
    await saveProduct(page);

    const localeMap = await passportLocaleMap(page);
    const rows = await publishAndWait(page, product.id, channelId, [localeMap.en_US], { expected: 1 });
    expect(rows.length).toBe(1);
  });

  test('9. Food/beverage — origin + JSON-LD schema.org Product and an SVG QR carrier', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `food_${generateUid()}`;

    const { uuid } = await publishScenario(page, sku, async () => {
      await fillDppField(page, 'dpp_country_of_origin', 'Italy');
      await fillDppField(page, 'dpp_material_composition', 'Durum wheat semolina');
    });

    const jsonld = await page.request.get(`/p/${uuid}/en_US`, { headers: { Accept: 'application/ld+json' } });
    expect(jsonld.headers()['content-type']).toContain('ld+json');
    const graph = await jsonld.json();
    expect(graph['@context']).toBe('https://schema.org');
    expect(graph['@type']).toBe('Product');
    expect(graph.productID).toBe(uuid);

    const carrier = await page.request.get(`/p/${uuid}/carrier.svg`);
    expect(carrier.status()).toBe(200);
    expect(carrier.headers()['content-type']).toContain('image/svg+xml');
    expect(await carrier.text()).toContain('<svg');
  });

  test('10. Pharma/medical — bulk publish two passports, then withdraw one to a tombstone', async ({ adminPage }) => {
    const page = adminPage;
    const skuA = `pha_${generateUid()}`;
    const skuB = `pha_${generateUid()}`;

    const a = await publishScenario(page, skuA, async () => {
      await fillDppField(page, 'dpp_warranty_terms', 'Store below 25C; 36-month shelf life');
    });
    const b = await publishScenario(page, skuB, async () => {
      await fillDppField(page, 'dpp_warranty_terms', 'Store below 25C; 24-month shelf life');
    });

    const rowA = await passportRowForSku(page, skuA);
    const rowB = await passportRowForSku(page, skuB);

    // Bulk publish mass-action over the two selected passport rows.
    const bulk = await adminPost(page, '/admin/catalog/passports/bulk-publish', { indices: [rowA.id, rowB.id] });
    expect(bulk.status).toBe(200);
    expect(JSON.stringify(bulk.body)).toMatch(/queued|publish/i);

    // Withdraw passport A; its public page stays 200 but shows only the tombstone (no field content).
    expect(await withdrawViaFetch(page, rowA.id)).toBe(200);

    const res = await page.goto(`/p/${a.uuid}/en_US`, { waitUntil: 'domcontentloaded' });
    expect(res.status()).toBe(200);
    await expect(page.getByText(/no longer available/i).first()).toBeVisible();
    expect(await page.content()).not.toContain('36-month shelf life');

    // Passport B remains live with its content.
    const liveB = await publicText(page, b.uuid);
    expect(liveB.body).toContain('24-month shelf life');
  });

  test('System Settings — Digital Product Passport section exposes Publication + Product Passport, tabs switch', async ({ adminPage }) => {
    const page = adminPage;

    // The re-keyed hub renders a top-level "Digital Product Passport" section with both rows reachable.
    await page.goto('/admin/configuration/system/digital_product_passport.product_passport', { waitUntil: 'domcontentloaded' });
    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
    await expect(page.getByRole('textbox', { name: /Operator Name/i }).first()).toBeVisible();

    await page.goto('/admin/configuration/system/digital_product_passport.publication', { waitUntil: 'domcontentloaded' });
    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
    await expect(page.getByRole('textbox', { name: /Base URL/i }).first()).toBeVisible();

    // The Passports surface carries two in-page tabs (Passports grid + Templates) that navigate.
    await page.goto('/admin/catalog/passports', { waitUntil: 'domcontentloaded' });
    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
    await page.getByRole('link', { name: /Templates/i }).click();
    await expect(page).toHaveURL(/passports\/templates/, { timeout: 15000 });
    await page.getByRole('link', { name: /Passports/i }).first().click();
    await expect(page).toHaveURL(/catalog\/passports$/, { timeout: 15000 });
  });
});
