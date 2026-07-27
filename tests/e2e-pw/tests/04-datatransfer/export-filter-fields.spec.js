const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

const filter = (page, name) => page.locator(`[name="filters[${name}]"]`);

const filterValue = (page, name) => page.locator(`input[name="filters[${name}]"][type="hidden"]`);

async function openCreateExport(page) {
  await navigateTo(page, 'exports');
  await page.getByRole('link', { name: 'Create Export' }).click();
  await expect(page.locator('#export-type')).toBeVisible();
}

async function selectEntityType(page, label) {
  await page.locator('#export-type').locator('.multiselect__single, .multiselect__placeholder').first().click();
  await page.getByRole('option', { name: label }).locator('span').first().click();
}

async function openFilter(page, name) {
  await filter(page, name)
    .locator('..')
    .locator('.multiselect__placeholder, .multiselect__single, .multiselect__tags')
    .first()
    .click();
}

async function chooseFilterOption(page, name, option) {
  await openFilter(page, name);

  await page.getByRole('option', { name: option }).locator('span').first().click();
}

test.describe('Export filter fields — reusable input components', () => {
  test('publishes one normalized field-set registry to the browser', async ({ adminPage }) => {
    await openCreateExport(adminPage);

    const sets = await adminPage.evaluate(() => Object.values(window.unopim?.fieldSets ?? {})[0] ?? null);

    expect(sets).not.toBeNull();
    expect(Object.keys(sets)).toEqual(expect.arrayContaining(['products', 'categories']));

    const products = Object.fromEntries(sets.products.map((field) => [field.name, field]));
    expect(Object.keys(products)).toEqual(
      expect.arrayContaining(['file_format', 'channels', 'locales', 'currencies', 'status', 'sku'])
    );

    expect(products.status.label).not.toContain('::');
    expect(products.status.type).toBe('select');

    expect(products.locales.list_route).toContain('/admin/');
    expect(products.locales.depends_on).toEqual({ field: 'channels', as: 'channels' });
    expect(products.currencies.depends_on).toEqual({ field: 'channels', as: 'channels' });
  });

  test('switching entity type swaps the rendered filter fields', async ({ adminPage }) => {
    await openCreateExport(adminPage);

    await expect(filter(adminPage, 'file_format').first()).toBeAttached();
    await expect(filter(adminPage, 'status')).toHaveCount(0);
    await expect(filter(adminPage, 'sku')).toHaveCount(0);

    await selectEntityType(adminPage, 'Products');

    await expect(filter(adminPage, 'status').first()).toBeAttached();
    await expect(filter(adminPage, 'sku').first()).toBeAttached();
    await expect(filter(adminPage, 'channels').first()).toBeAttached();
    await expect(filter(adminPage, 'locales').first()).toBeAttached();
    await expect(filter(adminPage, 'file_format').first()).toBeAttached();

    await selectEntityType(adminPage, 'Categories');

    await expect(filter(adminPage, 'status')).toHaveCount(0);
    await expect(filter(adminPage, 'sku')).toHaveCount(0);
  });

  test('visible_when reveals the time fields only for the matching condition', async ({ adminPage }) => {
    await openCreateExport(adminPage);
    await selectEntityType(adminPage, 'Products');

    await expect(filter(adminPage, 'time_value')).toHaveCount(0);
    await expect(filter(adminPage, 'time_date')).toHaveCount(0);

    await chooseFilterOption(adminPage, 'time_condition', /last N days/i);

    await expect(filter(adminPage, 'time_value').first()).toBeAttached();
    await expect(filter(adminPage, 'time_date')).toHaveCount(0);

    await chooseFilterOption(adminPage, 'time_condition', /between two dates/i);

    await expect(filter(adminPage, 'time_date').first()).toBeAttached();
    await expect(filter(adminPage, 'time_date_end').first()).toBeAttached();
    await expect(filter(adminPage, 'time_value')).toHaveCount(0);
  });

  test('depends_on scopes the locales option list to the selected channel', async ({ adminPage }) => {
    await openCreateExport(adminPage);
    await selectEntityType(adminPage, 'Products');

    const channelsLoaded = adminPage.waitForResponse((r) => r.url().includes('/filters/channels'));
    await openFilter(adminPage, 'channels');
    await channelsLoaded;

    const channel = adminPage.locator('.multiselect__content-wrapper:visible li.multiselect__element').first();
    await expect(channel).toBeVisible();
    await channel.locator('span').first().click();

    await expect(filterValue(adminPage, 'channels')).not.toHaveValue('');

    const scopedRequest = adminPage.waitForRequest(
      (request) => request.url().includes('/filters/locales') && request.url().includes('channels'),
      { timeout: 15000 }
    );

    await openFilter(adminPage, 'locales');

    expect(decodeURIComponent((await scopedRequest).url())).toContain('channels[]=');
  });

  test('saved filter values are rehydrated on the edit page', async ({ adminPage }) => {
    const code = `filter-fields-${generateUid()}`;

    await openCreateExport(adminPage);
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await selectEntityType(adminPage, 'Products');

    await chooseFilterOption(adminPage, 'file_format', 'XLSX');
    await chooseFilterOption(adminPage, 'status', 'Enable');

    await clickSaveAndExpect(adminPage, 'Save changes', /Export created successfully/i);

    await adminPage.getByRole('link', { name: 'Edit' }).click();
    await adminPage.waitForLoadState('networkidle');

    await expect(filterValue(adminPage, 'file_format')).toHaveValue('Xlsx');
    await expect(filterValue(adminPage, 'status')).toHaveValue('enable');

    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible({ timeout: 20000 });
  });

  test('datagrid column filters render through the shared field component', async ({ adminPage }) => {
    await navigateTo(adminPage, 'exports');
    await adminPage.getByText('Filter', { exact: true }).click();

    // The filter panel renders every column filter through the shared field component;
    // basic column filters sit in a collapsed section, so assert they are rendered with
    // the right shape rather than expanded into view.
    const codeFilter = adminPage.locator('input[name="code"]');
    await expect(codeFilter).toBeAttached();
    await expect(codeFilter).toHaveAttribute('type', 'text');
    await expect(codeFilter).toHaveAttribute('placeholder', 'Code');

    await expect(adminPage.locator('input[name="id"]')).toHaveAttribute('type', 'number');
    await expect(adminPage.locator('input[name="entity_type"]')).toHaveAttribute('type', 'text');
  });
});

const conditions = (page) => page.locator('input[name="filters[custom_attributes]"]');

const conditionsRoot = (page) => conditions(page).locator('..');

const conditionRows = async (page) => JSON.parse((await conditions(page).inputValue()) || '[]');

async function addCondition(page) {
  await page.getByRole('button', { name: /Add another attribute condition/i }).click();
}

async function openAttributePicker(page) {
  const loaded = page.waitForResponse((response) => response.url().includes('/filters/attributes'));

  await conditionsRoot(page).locator('.multiselect__placeholder, .multiselect__tags').first().click();

  return loaded;
}

function conditionOption(page, label) {
  return conditionsRoot(page)
    .locator('li.multiselect__element')
    .filter({ hasText: new RegExp(`^${label}$`) })
    .first();
}

async function pickAttribute(page, label) {
  await openAttributePicker(page);

  await conditionOption(page, label).locator('span').first().click();
}

async function pickOperator(page, label) {
  await conditionsRoot(page).locator('.multiselect__single').first().click();

  await conditionOption(page, label).locator('span').first().click();
}

test.describe('Attribute conditions — a config-driven filter field', () => {
  test('renders only for the entity whose exporter config declares it', async ({ adminPage }) => {
    await openCreateExport(adminPage);

    await expect(conditions(adminPage)).toHaveCount(0);

    await selectEntityType(adminPage, 'Products');

    await expect(conditions(adminPage).first()).toBeAttached();
    await expect(adminPage.getByRole('button', { name: /Add another attribute condition/i })).toBeVisible();

    await selectEntityType(adminPage, 'Categories');

    await expect(conditions(adminPage)).toHaveCount(0);
  });

  test('asks the attributes route to exclude the sku it already filters on', async ({ adminPage }) => {
    await openCreateExport(adminPage);
    await selectEntityType(adminPage, 'Products');
    await addCondition(adminPage);

    const request = adminPage.waitForRequest((r) => r.url().includes('/filters/attributes'), { timeout: 15000 });

    await openAttributePicker(adminPage);

    expect(decodeURIComponent((await request).url())).toContain('exclude[]=sku');

    await expect(adminPage.getByRole('option', { name: 'SKU', exact: true })).toHaveCount(0);
  });

  test('serializes a completed condition into the hidden filters input', async ({ adminPage }) => {
    await openCreateExport(adminPage);
    await selectEntityType(adminPage, 'Products');
    await addCondition(adminPage);

    expect(await conditionRows(adminPage)).toEqual([]);

    await pickAttribute(adminPage, 'Name');

    await conditionsRoot(adminPage).locator('input[placeholder="Value"]').fill('Shirt');

    await expect
      .poll(() => conditionRows(adminPage))
      .toEqual([{ attribute: 'name', operator: 'contains', value: 'Shirt' }]);
  });

  test('drops the value control for an operator that needs no value', async ({ adminPage }) => {
    await openCreateExport(adminPage);
    await selectEntityType(adminPage, 'Products');
    await addCondition(adminPage);

    await pickAttribute(adminPage, 'Name');
    await pickOperator(adminPage, 'Is empty');

    await expect(conditionsRoot(adminPage).getByText('No value needed')).toBeVisible();

    await expect.poll(() => conditionRows(adminPage)).toEqual([{ attribute: 'name', operator: 'empty' }]);
  });

  test('rehydrates a saved condition on the edit page', async ({ adminPage }) => {
    const code = `attribute-conditions-${generateUid()}`;

    await openCreateExport(adminPage);
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await selectEntityType(adminPage, 'Products');

    await chooseFilterOption(adminPage, 'file_format', 'XLSX');

    await addCondition(adminPage);
    await pickAttribute(adminPage, 'Name');
    await conditionsRoot(adminPage).locator('input[placeholder="Value"]').fill('Shirt');

    await clickSaveAndExpect(adminPage, 'Save changes', /Export created successfully/i);

    await adminPage.getByRole('link', { name: 'Edit' }).click();
    await adminPage.waitForLoadState('networkidle');

    await expect
      .poll(() => conditionRows(adminPage))
      .toEqual([{ attribute: 'name', operator: 'contains', value: 'Shirt' }]);

    await expect(conditionsRoot(adminPage).locator('input[placeholder="Value"]')).toHaveValue('Shirt');

    await navigateTo(adminPage, 'exports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await adminPage.locator('span[title="Delete"]').first().click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible({ timeout: 20000 });
  });
});
