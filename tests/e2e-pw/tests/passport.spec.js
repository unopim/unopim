// DPP E2E regression: enable publishing via the re-keyed System Settings hub, create a complete product,
// publish every channel locale, view/switch/withdraw. Uses the canonical `dpp_e2e` family + config seeded
// by scripts/seed-dpp-e2e.php (see fixtures/passport.js header for the one-time prereq commands).
const {
  test,
  expect,
  generateUid,
  withFamilyPage,
  ensureDppFamily,
  createProduct,
  fillDppField,
  gotoProductEdit,
  saveProduct,
  fetchGridRows,
  settingsIdByCode,
  passportLocaleIds,
  publishAndWait,
  passportRowForSku,
} = require('../fixtures/passport');

test.describe.serial('Digital Product Passport', () => {
  let family;
  let product;
  let channelId;
  const channelCode = 'default';
  let localeCodes = [];
  let localeIds = [];
  let publicationUuid;

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(120000);

    family = await ensureDppFamily();

    await withFamilyPage(browser, async (page) => {
      await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
      channelId = await settingsIdByCode(page, '/admin/settings/channels', channelCode);
    });
  });

  test('enable Digital Product Passport publishing via the System Settings hub', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto('/admin/configuration/system/digital_product_passport.product_passport', { waitUntil: 'domcontentloaded' });
    await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });

    // The boolean toggle carries no accessible name; `enabled` is the first checkbox on the page (order-stable).
    const enabled = page.getByRole('checkbox').first();
    if (!(await enabled.isChecked().catch(() => false))) {
      await enabled.check({ force: true });
    }

    // A unique operator name guarantees the form goes dirty every run (surfacing the sticky save bar,
    // which replaces the tracker-removed in-form submit) and feeds the JSON-LD manufacturer name.
    await page.getByRole('textbox', { name: /Economic Operator Name/i }).fill(`Acme Corp GmbH ${generateUid()}`);

    const saveBar = page.locator('[data-unsaved-save]');
    await saveBar.waitFor({ state: 'visible', timeout: 15000 });
    await saveBar.click();

    await expect(page.locator('#app').getByText(/saved successfully/i).first())
      .toBeVisible({ timeout: 20000 });
  });

  test('create a complete product with a dpp value', async ({ adminPage }) => {
    const page = adminPage;
    const sku = `dppprod_${generateUid()}`;
    product = await createProduct(page, family.name, sku);

    // dpp_manufacturer_name is a common-bucket value (no locale/channel scoping): one value satisfies every locale.
    await fillDppField(page, 'dpp_manufacturer_name', 'Acme Corp');
    await saveProduct(page);

    const status = await fetchGridRows(page, `/admin/products/${product.id}/passport`);
    localeCodes = (status.rows || []).map((row) => row.locale_code);
    expect(localeCodes.length).toBeGreaterThan(0);

    // Locale ids come from the passport panel on the edit page (the grid is paginated across 200+ locales).
    localeIds = await passportLocaleIds(page);
    expect(localeIds.length).toBe(localeCodes.length);
  });

  test('publishes every channel locale and shows version numbers', async ({ adminPage }) => {
    const page = adminPage;
    await gotoProductEdit(page, product.id);

    const versioned = await publishAndWait(page, product.id, channelId, localeIds);
    expect(versioned.length).toBe(localeCodes.length);
  });

  test('public passport url redirects to the canonical locale url and renders', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
    const row = await passportRowForSku(page, product.sku);
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
    await page.goto(`/p/${publicationUuid}`, { waitUntil: 'domcontentloaded' });

    const current = await page.getAttribute('html', 'lang');
    const other = localeCodes.find((code) => code !== current);

    // The locale links live inside a collapsed <details> switcher; open it before clicking.
    await page.locator('.switcher summary').click();
    await page.locator('.switcher .locale-list').getByRole('link', { name: other, exact: true }).click();
    await expect(page.locator('html')).toHaveAttribute('lang', other, { timeout: 15000 });
  });

  test('withdraws from the grid, flashes success and drops the action', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto('/admin/catalog/passports', { waitUntil: 'domcontentloaded' });

    await page.getByRole('textbox', { name: 'Search' }).fill(product.sku);
    await page.getByRole('textbox', { name: 'Search' }).press('Enter');

    const row = page.locator('.row', { hasText: product.sku }).first();
    await row.locator("span[title='Withdraw']").click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText(/withdrawn successfully/i).first()).toBeVisible({ timeout: 15000 });
    await expect(row).toContainText(/withdrawn/i);
    await expect(row.locator("span[title='Withdraw']")).toHaveCount(0);
  });

  test('withdrawing keeps the public url at 200 with a tombstone', async ({ adminPage }) => {
    const page = adminPage;
    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });

    // Already withdrawn by the grid test above; re-withdrawing is refused now that the
    // transition is guarded, so this only asserts what the withdrawn state renders.
    const row = await passportRowForSku(page, product.sku);
    expect(row).toBeTruthy();

    const response = await page.goto(`/p/${publicationUuid}/${localeCodes[0]}`, { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(200);
    await expect(page.locator('.tombstone [role="alert"]').first()).toBeVisible();
    await expect(page.locator('.card')).toHaveCount(0);
  });
});
