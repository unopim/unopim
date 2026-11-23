const { test, expect } = require('../../utils/fixtures');
const { clickSave, generateUid, closeDropdown, fillLocalizedField } = require('../../utils/helpers');

const MEASUREMENT_FAMILY = 'Length';

/**
 * Open the multiselect that wraps the given named input and return its wrapper.
 */
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

async function selectMultiselectOption(page, name, label) {
  const wrapper = await openMultiselect(page, name);

  await wrapper.locator('input.multiselect__input').first().fill(label).catch(() => {});
  await page.waitForTimeout(400);

  const option = page.getByRole('option', { name: label }).first();
  await option.waitFor({ state: 'visible', timeout: 10000 });
  await option.scrollIntoViewIfNeeded().catch(() => {});
  await option.click();
  await closeDropdown(page);
}

async function selectFirstMultiselectOption(page, name) {
  await openMultiselect(page, name);
  await page.waitForTimeout(600);

  const first = page.locator('.multiselect__content-wrapper:visible li.multiselect__element').first();
  await first.waitFor({ state: 'visible', timeout: 12000 });

  const label = cleanOptionLabel(await first.innerText());
  await first.click();
  await closeDropdown(page);

  return label;
}

/**
 * Open the quick-create "Create Attribute" modal and pick a type.
 */
async function openQuickCreate(page, code, type) {
  await page.goto('/admin/catalog/attributes', { waitUntil: 'domcontentloaded' });
  await page.getByRole('button', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).waitFor({ state: 'visible', timeout: 20000 });
  await page.getByRole('textbox', { name: 'Code' }).fill(code);

  await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await page.locator('input[name="type"][type="text"]').fill(type);
  await page.getByRole('option', { name: type }).first().click();
}

/**
 * The visible vue-multiselect control wrapping the named measurement field.
 * Its inner search input stays hidden until the control is opened, so assert
 * visibility on the wrapper rather than the input.
 */
function measurementControl(page, name) {
  return page
    .locator(`input[name="${name}"][type="text"]`)
    .locator('xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " multiselect ")][1]');
}

test.describe('Measurement - Add Attribute modal', () => {
  test('choosing the measurement type shows the family field', async ({ adminPage }) => {
    test.setTimeout(60000);

    await openQuickCreate(adminPage, `meas_${generateUid()}`, 'Measurement');

    await expect(measurementControl(adminPage, 'measurement_family')).toBeVisible({ timeout: 15000 });
  });

  test('choosing a family loads its units', async ({ adminPage }) => {
    test.setTimeout(60000);

    await openQuickCreate(adminPage, `meas_${generateUid()}`, 'Measurement');

    await adminPage.locator('input[name="measurement_family"][type="text"]').waitFor({ state: 'attached', timeout: 15000 });
    await selectMultiselectOption(adminPage, 'measurement_family', MEASUREMENT_FAMILY);

    await expect(measurementControl(adminPage, 'measurement_unit')).toBeVisible({ timeout: 15000 });
  });

  test('saving keeps the family and unit on the edit page', async ({ adminPage }) => {
    test.setTimeout(90000);

    const code = `meas_${generateUid()}`;

    await openQuickCreate(adminPage, code, 'Measurement');
    await fillLocalizedField(adminPage, `Measurement ${generateUid()}`);

    await adminPage.locator('input[name="measurement_family"][type="text"]').waitFor({ state: 'attached', timeout: 15000 });
    await selectMultiselectOption(adminPage, 'measurement_family', MEASUREMENT_FAMILY);

    await adminPage.locator('input[name="measurement_unit"][type="text"]').waitFor({ state: 'attached', timeout: 15000 });
    const unitLabel = await selectFirstMultiselectOption(adminPage, 'measurement_unit');

    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 30000 }),
      clickSave(adminPage, 'Save Attribute'),
    ]);

    await expect(adminPage.locator('#app').getByText('Edit Attribute').first()).toBeVisible({ timeout: 20000 });

    const measurementCard = adminPage.locator('div.rounded.bg-white, div.dark\\:bg-cherry-900')
      .filter({ hasText: 'Measurement Family' })
      .first();

    const singles = measurementCard.locator('.multiselect__single');
    await expect(singles.nth(0)).toContainText(MEASUREMENT_FAMILY, { timeout: 20000 });
    await expect(singles.nth(1)).toContainText(unitLabel, { timeout: 15000 });
  });

  test('switching from measurement to text clears the fields and still saves', async ({ adminPage }) => {
    test.setTimeout(90000);

    const code = `text_${generateUid()}`;

    await openQuickCreate(adminPage, code, 'Measurement');
    await fillLocalizedField(adminPage, `Text ${generateUid()}`);

    await adminPage.locator('input[name="measurement_family"][type="text"]').waitFor({ state: 'attached', timeout: 15000 });
    await selectMultiselectOption(adminPage, 'measurement_family', MEASUREMENT_FAMILY);

    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__tags').click();
    await adminPage.locator('input[name="type"][type="text"]').fill('Text');
    await adminPage.getByRole('option', { name: /^Text\b/ }).first().click();

    await expect(adminPage.locator('input[name="measurement_family"]')).toHaveCount(0);
    await expect(adminPage.locator('input[name="measurement_unit"]')).toHaveCount(0);

    await Promise.all([
      adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 30000 }),
      clickSave(adminPage, 'Save Attribute'),
    ]);

    await expect(adminPage.locator('#app').getByText('Edit Attribute').first()).toBeVisible({ timeout: 20000 });
  });
});
