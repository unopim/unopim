const path = require('path');
const { test, expect } = require('../../utils/fixtures');
const { clickSave, generateUid, searchInDataGrid } = require('../../utils/helpers');

const STORAGE_STATE = path.resolve(__dirname, '../../.state/admin-auth.json');
const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const MEASUREMENT_FAMILY = 'Length';

const adminUrl = (p) => `${BASE}${p}`;

const HIDE_OVERLAYS = () => {
  const style = document.createElement('style');
  style.id = 'pw-hide-widget';
  style.textContent = '.ap-shell, .phpdebugbar { display: none !important; }';
  if (document.head) {
    document.head.appendChild(style);
  } else {
    document.addEventListener('DOMContentLoaded', () => document.head.appendChild(style));
  }
};

async function newAdminContext(browser) {
  const context = await browser.newContext({ storageState: STORAGE_STATE });
  await context.addInitScript(HIDE_OVERLAYS);
  return context;
}

async function gotoAdmin(page, p) {
  await page.goto(adminUrl(p), { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('load').catch(() => {});
}

async function openMultiselect(page, name) {
  const input = page.locator(`input[name="${name}"]`).first();
  await input.waitFor({ state: 'attached', timeout: 15000 });

  const wrapper = input.locator(
    'xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " multiselect ")][1]'
  );

  await wrapper.scrollIntoViewIfNeeded().catch(() => {});

  const tags = wrapper.locator('.multiselect__tags');
  await (await tags.count() ? tags.first() : wrapper).click();

  await wrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 10000 });

  return wrapper;
}

function cleanOptionLabel(text) {
  return (text || '').trim().replace(/\s*Press enter to select\s*$/i, '').trim();
}

async function selectMultiselectOption(page, name, label, { filter = true } = {}) {
  const wrapper = await openMultiselect(page, name);

  if (filter) {
    await wrapper.locator('input.multiselect__input').first().fill(label).catch(() => {});
    await page.waitForTimeout(400);
  }

  const option = page.getByRole('option', { name: label }).first();
  await option.waitFor({ state: 'visible', timeout: 10000 });
  await option.scrollIntoViewIfNeeded().catch(() => {});
  await option.click();
  await page.keyboard.press('Escape').catch(() => {});
}

async function selectFirstMultiselectOption(page, name) {
  await openMultiselect(page, name);
  await page.waitForTimeout(600);

  const first = page.locator('.multiselect__content-wrapper:visible li.multiselect__element').first();
  await first.waitFor({ state: 'visible', timeout: 12000 });

  const label = cleanOptionLabel(await first.innerText());
  await first.click();
  await page.keyboard.press('Escape').catch(() => {});

  return label;
}

async function clickCreate(page, label) {
  const control = page.getByRole('link', { name: label }).or(page.getByRole('button', { name: label }));
  await control.first().waitFor({ state: 'visible', timeout: 30000 });
  await control.first().click();
}

async function createMeasurementAttribute(page, { familyCode = MEASUREMENT_FAMILY } = {}) {
  const uid = generateUid();
  const code = `meas_${uid}`;
  const name = `Measurement ${uid}`;

  await gotoAdmin(page, '/admin/catalog/attributes');
  await clickCreate(page, 'Create Attribute');

  const nameInput = page.locator('input[name="en_US\\[name\\]"]').first();
  await nameInput.waitFor({ state: 'visible', timeout: 20000 });
  await nameInput.fill(name);

  await page.getByRole('textbox', { name: 'Code' }).fill(code);

  await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await page.locator('input[name="type"][type="text"]').fill('Measurement');
  await page.waitForTimeout(500);

  const measOption = page.getByRole('option', { name: 'Measurement' }).first();
  if (await measOption.isVisible().catch(() => false)) {
    await measOption.click();
  } else {
    await page.keyboard.press('Enter');
  }

  await Promise.all([
    page.waitForURL(/\/attributes\/edit\//, { timeout: 30000 }),
    page.getByRole('button', { name: 'Save Attribute' }).click(),
  ]);

  await expect(page.getByRole('heading', { name: 'Edit Attribute' })).toBeVisible({ timeout: 20000 });

  await page.locator('input[name="measurement_family"]').first().waitFor({ state: 'attached', timeout: 25000 });
  await selectMultiselectOption(page, 'measurement_family', familyCode);

  await page.locator('input[name="measurement_unit"]').first().waitFor({ state: 'attached', timeout: 15000 });
  const unitLabel = await selectFirstMultiselectOption(page, 'measurement_unit');

  await clickSave(page, 'Save Attribute');
  await expect(page.locator('#app').getByText(/updated successfully/i).first()).toBeVisible({ timeout: 25000 });

  return { code, name, unitLabel };
}

async function createAttributeFamily(page, { basedOn } = {}) {
  const uid = generateUid();
  const code = `mfam_${uid}`;

  await gotoAdmin(page, '/admin/catalog/attribute-families');
  await page.getByRole('button', { name: 'Create Attribute Family' }).waitFor({ state: 'visible', timeout: 30000 });
  await page.getByRole('button', { name: 'Create Attribute Family' }).click();
  await page.getByPlaceholder('Enter Name').fill(code);
  await page.getByPlaceholder('Enter Code').fill(code);

  if (basedOn) {
    await selectMultiselectOption(page, 'based_on', basedOn).catch(() => {});
  }

  await Promise.all([
    page.waitForURL(/\/attribute-families\/edit\/\d+/, { timeout: 45000 }),
    page.getByRole('button', { name: 'Save Attribute Family' }).click(),
  ]);

  await page.waitForSelector('.group_node', { timeout: 45000 });

  const id = page.url().match(/\/edit\/(\d+)/)[1];

  return { id, code };
}

async function selectUnassignedAttribute(page, matchText) {
  const row = page.locator('#unassigned-attributes > div')
    .filter({ hasText: matchText })
    .filter({ has: page.locator('button') })
    .first();

  await row.waitFor({ state: 'visible', timeout: 10000 });

  const checkbox = row.locator('button').first();

  for (let attempt = 0; attempt < 4; attempt++) {
    if (await row.locator('button.icon-checkbox-check').count()) {
      return;
    }

    await checkbox.dispatchEvent('click').catch(() => {});
    await page.waitForTimeout(400);
  }

  if (!(await row.locator('button.icon-checkbox-check').count())) {
    throw new Error(`Could not select unassigned attribute: ${matchText}`);
  }
}

async function assignAttributesToFamily(page, familyId, entries, group = 'General') {
  await gotoAdmin(page, `/admin/catalog/attribute-families/edit/${familyId}`);
  await page.waitForSelector('.group_node', { timeout: 30000 });

  const unassignedHeader = page.locator('div.flex.items-center.justify-between')
    .filter({ hasText: 'Unassigned Attributes' })
    .first();
  const searchToggle = unassignedHeader.locator('button.icon-search');
  if (await searchToggle.isVisible({ timeout: 5000 }).catch(() => false)) {
    await searchToggle.click();
  }
  const search = unassignedHeader.locator('xpath=..').locator('input[placeholder="Search"]').first();
  await search.waitFor({ state: 'visible', timeout: 8000 });

  for (const entry of entries) {
    await search.fill(entry.query);
    await page.keyboard.press('Enter');
    await page.waitForTimeout(1500);

    await selectUnassignedAttribute(page, entry.match);
  }

  const bulkBox = page.locator('div')
    .filter({ hasText: 'Assign selected attributes to group' })
    .last();

  await expect(bulkBox).toBeVisible({ timeout: 8000 });

  const groupMs = bulkBox.locator('.multiselect[role="combobox"]').first();
  await groupMs.waitFor({ state: 'visible', timeout: 8000 });
  await groupMs.scrollIntoViewIfNeeded().catch(() => {});
  const groupInput = groupMs.locator('input[name="bulk_group_picker"]').first();

  let picked = false;
  for (let attempt = 0; attempt < 4 && !picked; attempt++) {
    if (attempt % 2 === 0) {
      await groupInput.evaluate((el) => el.focus()).catch(() => {});
    } else {
      await groupMs.locator('.multiselect__select').first().click({ force: true }).catch(() => {});
    }
    await page.waitForTimeout(500);

    const opt = page.getByRole('option', { name: group }).first();
    if (await opt.isVisible({ timeout: 2000 }).catch(() => false)) {
      await opt.click();
      picked = true;
    }
  }

  if (!picked) {
    throw new Error(`Could not select group "${group}" in bulk assign`);
  }

  await bulkBox.getByRole('button', { name: /Assign/ }).first().click();
  await page.waitForTimeout(500);

  const bar = page.getByRole('button', { name: 'Save changes' });
  const named = page.getByRole('button', { name: 'Save Attribute Family' });

  await Promise.race([
    bar.waitFor({ state: 'visible', timeout: 12000 }).catch(() => {}),
    named.waitFor({ state: 'visible', timeout: 12000 }).catch(() => {}),
  ]);

  const target = (await bar.isVisible().catch(() => false)) ? bar : named;
  await target.scrollIntoViewIfNeeded().catch(() => {});
  await target.evaluate((el) => el.click());

  await expect(page.locator('#app').getByText(/updated successfully/i).first()).toBeVisible({ timeout: 25000 });
}

async function createSimpleProduct(page, familyLabel) {
  const sku = `meas_prod_${generateUid()}`;

  await gotoAdmin(page, '/admin/catalog/products');
  await clickCreate(page, 'Create Product');
  await page.waitForLoadState('networkidle').catch(() => {});

  await selectMultiselectOption(page, 'type', 'Simple');
  await selectMultiselectOption(page, 'attribute_family_id', familyLabel);
  await page.locator('input[name="sku"]').fill(sku);

  await Promise.all([
    page.waitForURL(/\/catalog\/products\/edit\//, { timeout: 30000 }),
    clickSave(page, 'Save Product'),
  ]);

  await page.waitForLoadState('networkidle').catch(() => {});

  return sku;
}

function measurementContainer(page) {
  return page.locator('input[placeholder="Enter value"]').first()
    .locator('xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " grid ") and contains(concat(" ", normalize-space(@class), " "), " gap-4 ")][1]');
}

async function setMeasurementValue(page, amount) {
  const valueInput = page.locator('input[placeholder="Enter value"]').first();
  await valueInput.waitFor({ state: 'visible', timeout: 20000 });
  await valueInput.scrollIntoViewIfNeeded();
  await valueInput.fill('');
  await valueInput.fill(amount);

  const container = measurementContainer(page);
  const unitSelect = container.locator('.multiselect').first();

  await unitSelect.locator('.multiselect__tags').click();
  await unitSelect.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 10000 });
  await page.waitForTimeout(900);

  const firstOption = unitSelect.locator('li.multiselect__element').first();
  await firstOption.waitFor({ state: 'visible', timeout: 12000 });
  const unitLabel = cleanOptionLabel(await firstOption.innerText());
  await firstOption.click();
  await page.keyboard.press('Escape').catch(() => {});

  return unitLabel;
}

async function saveProduct(page) {
  const bar = page.getByRole('button', { name: 'Save changes' });
  const named = page.getByRole('button', { name: 'Save Product' });

  await Promise.race([
    bar.waitFor({ state: 'visible', timeout: 12000 }).catch(() => {}),
    named.waitFor({ state: 'visible', timeout: 12000 }).catch(() => {}),
  ]);

  const target = (await bar.isVisible().catch(() => false)) ? bar : named;
  await target.scrollIntoViewIfNeeded().catch(() => {});
  await target.evaluate((el) => el.click());
}

async function deleteProductBySku(page, sku) {
  await gotoAdmin(page, '/admin/catalog/products');
  await searchInDataGrid(page, sku);

  const deleteIcon = page.locator('span[title="Delete"]').first();
  if (!(await deleteIcon.isVisible({ timeout: 3000 }).catch(() => false))) {
    return;
  }

  await deleteIcon.click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await page.locator('#app').getByText(/Product deleted successfully/i).waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
}

test.describe('Measurement - Attribute Family', () => {
  test('measurement attribute joins a family but is never offered as a variant axis', async ({ adminPage }) => {
    test.setTimeout(150000);

    const attr = await createMeasurementAttribute(adminPage, { familyCode: MEASUREMENT_FAMILY });
    const family = await createAttributeFamily(adminPage, { basedOn: 'Default' });

    await assignAttributesToFamily(
      adminPage,
      family.id,
      [{ query: attr.code, match: attr.name }],
      'General'
    );

    await gotoAdmin(adminPage, `/admin/catalog/attribute-families/edit/${family.id}`);
    await adminPage.waitForSelector('.group_node', { timeout: 30000 });
    await expect(adminPage.locator('#assigned-attribute-groups').getByText(attr.name).first())
      .toBeVisible({ timeout: 20000 });

    await gotoAdmin(adminPage, `/admin/catalog/attribute-families/edit/${family.id}?variants`);
    await adminPage.locator('#app').waitFor({ state: 'visible', timeout: 30000 });

    const addVariant = adminPage.getByRole('button', { name: /Add Variant/i });
    await addVariant.waitFor({ state: 'visible', timeout: 20000 });
    await addVariant.click();

    await openMultiselect(adminPage, 'draft_axis_leaf');
    await adminPage.waitForTimeout(500);

    const variantModal = adminPage.locator('.fixed').filter({ hasText: 'Add Variant' }).last();

    await expect(variantModal.getByText('Color', { exact: false }).first()).toBeVisible({ timeout: 10000 });
    await expect(variantModal.getByText('Size', { exact: false }).first()).toBeVisible({ timeout: 10000 });

    await expect(variantModal.getByText(attr.name, { exact: false })).toHaveCount(0);
    await expect(variantModal.getByText(attr.code, { exact: false })).toHaveCount(0);
  });
});

test.describe('Measurement - Attribute', () => {
  test('create a measurement attribute with family and unit, and it persists', async ({ adminPage }) => {
    test.setTimeout(120000);

    const attr = await createMeasurementAttribute(adminPage, { familyCode: MEASUREMENT_FAMILY });

    await adminPage.reload({ waitUntil: 'domcontentloaded' });
    await expect(adminPage.getByRole('heading', { name: 'Edit Attribute' })).toBeVisible({ timeout: 20000 });

    const measurementCard = adminPage.locator('div.rounded.bg-white, div.dark\\:bg-cherry-900')
      .filter({ hasText: 'Measurement Family' })
      .first();

    const singles = measurementCard.locator('.multiselect__single');
    await expect(singles.nth(0)).toContainText(MEASUREMENT_FAMILY, { timeout: 20000 });
    await expect(singles.nth(1)).toContainText(attr.unitLabel, { timeout: 15000 });
  });
});

test.describe.serial('Measurement - Product value', () => {
  const shared = {};

  test.beforeAll(async ({ browser }) => {
    const context = await newAdminContext(browser);
    const page = await context.newPage();

    try {
      shared.attr = await createMeasurementAttribute(page, { familyCode: MEASUREMENT_FAMILY });
      shared.family = await createAttributeFamily(page);
      await assignAttributesToFamily(
        page,
        shared.family.id,
        [{ query: shared.attr.code, match: shared.attr.name }],
        'General'
      );
    } finally {
      await page.close();
      await context.close();
    }
  });

  test.afterAll(async ({ browser }) => {
    if (!shared.sku) {
      return;
    }

    const context = await newAdminContext(browser);
    const page = await context.newPage();

    try {
      await deleteProductBySku(page, shared.sku);
    } catch (e) {
      void e;
    } finally {
      await page.close();
      await context.close();
    }
  });

  test('create a product with a measurement value and it reloads with the value', async ({ adminPage }) => {
    test.setTimeout(120000);

    expect(shared.family, 'prerequisite family was not created in beforeAll').toBeTruthy();

    shared.sku = await createSimpleProduct(adminPage, shared.family.code);
    shared.productUrl = adminPage.url();

    shared.unitLabel = await setMeasurementValue(adminPage, '15');

    await saveProduct(adminPage);
    await expect(adminPage.locator('#app').getByText(/updated successfully/i).first()).toBeVisible({ timeout: 25000 });

    await adminPage.reload({ waitUntil: 'domcontentloaded' });

    const valueInput = adminPage.locator('input[placeholder="Enter value"]').first();
    await expect(valueInput).toHaveValue('15', { timeout: 20000 });

    await expect(measurementContainer(adminPage).locator('.multiselect')).toContainText(shared.unitLabel, { timeout: 15000 });
  });

  test('update the measurement value on the product and it persists', async ({ adminPage }) => {
    test.setTimeout(120000);

    expect(shared.productUrl, 'prerequisite product was not created in the previous test').toBeTruthy();

    await adminPage.goto(shared.productUrl, { waitUntil: 'domcontentloaded' });

    const valueInput = adminPage.locator('input[placeholder="Enter value"]').first();
    await valueInput.waitFor({ state: 'visible', timeout: 20000 });
    await valueInput.scrollIntoViewIfNeeded();
    await valueInput.fill('');
    await valueInput.fill('42');

    await saveProduct(adminPage);
    await expect(adminPage.locator('#app').getByText(/updated successfully/i).first()).toBeVisible({ timeout: 25000 });

    await adminPage.reload({ waitUntil: 'domcontentloaded' });

    const reloaded = adminPage.locator('input[placeholder="Enter value"]').first();
    await expect(reloaded).toHaveValue('42', { timeout: 20000 });
  });
});

test.describe('Measurement - System configuration', () => {
  test('precision settings page shows strategy, amount and base fields', async ({ adminPage }) => {
    await gotoAdmin(adminPage, '/admin/configuration/system/system.measurement');

    await expect(adminPage.getByText('Decimal strategy').first()).toBeVisible({ timeout: 25000 });
    await expect(adminPage.getByText('Amount decimals').first()).toBeVisible({ timeout: 15000 });
    await expect(adminPage.getByText('Base value decimals').first()).toBeVisible({ timeout: 15000 });

    await expect(adminPage.getByText('Round').first()).toBeVisible({ timeout: 15000 });
  });
});
