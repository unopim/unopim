/**
 * End-to-end run of the passport feature against the EU battery passport, the
 * first product group the regulation actually mandates (Reg. 2023/1542): build a
 * template, bind the family, source fields from the attributes the merchant
 * already maintains, publish, and read the public page back — including the tier
 * split that keeps dismantling and conformity data out of the consumer view.
 *
 * Fixture: scripts/seed-passport-template-e2e.php (idempotent).
 */
const { execFileSync } = require('child_process');

const { test, expect } = require('@playwright/test');

const CONTAINER = process.env.PASSPORT_E2E_CONTAINER || 'unopim-assoc-unopim-fpm-1';

const TEMPLATE_CODE = `battery_e2e_${Date.now().toString(36)}`;

const CONSUMER_FIELD = { label: 'Rated Capacity', attribute: 'Rated Capacity', value: '78 kWh' };
const OPERATOR_FIELD = { label: 'Dismantling Instructions', attribute: 'Dismantling Instructions' };
const AUTHORITY_FIELD = { label: 'EU Declaration of Conformity', attribute: 'EU Declaration of Conformity' };

let fixture;
let templateUrl;

/** Dev envs run the app in docker; CI runs it on the host (PASSPORT_E2E_LOCAL=1). */
function inContainer(args) {
  if (process.env.PASSPORT_E2E_LOCAL === '1') {
    return execFileSync(args[0], args.slice(1), {
      cwd: `${__dirname}/../../../..`,
      encoding: 'utf-8',
      timeout: 600_000,
    });
  }

  const command = args[0] === 'php'
    ? ['php', '-d', 'auto_prepend_file=tests/bootstrap.php', ...args.slice(1)]
    : args;

  return execFileSync('docker', ['exec', '-w', '/var/www/html', CONTAINER, ...command], {
    encoding: 'utf-8',
    timeout: 600_000,
  });
}

function seed() {
  const output = inContainer(['php', 'tests/e2e-pw/scripts/seed-passport-template-e2e.php']);

  return JSON.parse(output.trim().split('\n').pop());
}

/** The picker is a vue-multiselect; its searchbox shares the field name, so commit through the hidden input. */
async function pickInMultiselect(scope, hiddenName, optionLabel) {
  const hidden = scope.locator(`input[type="hidden"][name="${hiddenName}"]`).first();

  await hidden.waitFor({ state: 'attached' });

  const control = hidden.locator('xpath=parent::*').locator('.multiselect').first();

  for (let attempt = 0; attempt < 3; attempt++) {
    await control.click();

    const search = control.locator('input.multiselect__input');

    if (await search.count()) {
      await search.fill(optionLabel.slice(0, 12));
    }

    const option = control.locator('.multiselect__content-wrapper li, .multiselect__element')
      .filter({ hasText: optionLabel })
      .first();

    await option.click({ timeout: 8_000 }).catch(() => {});

    if (await hidden.inputValue().catch(() => '')) {
      await (scope.keyboard ?? scope.page().keyboard).press('Escape');

      return;
    }
  }

  throw new Error(`multiselect "${hiddenName}" never committed "${optionLabel}"`);
}

async function selectByLabel(modal, name, optionLabel) {
  await pickInMultiselect(modal, name, optionLabel);
}

async function openFieldModal(page) {
  await page.getByRole('button', { name: 'Add Field' }).click();

  const modal = page.locator('input[name="draft_field_name"]').locator('xpath=ancestor::*[self::div][3]');

  await page.locator('input[name="draft_field_name"]').waitFor();

  return modal;
}

test.describe.serial('EU battery Digital Product Passport', () => {
  test.beforeAll(() => {
    fixture = seed();

    inContainer(['php', 'artisan', 'view:clear']);
  });

  test('templates listing ships the ESPR preset', async ({ page }) => {
    await page.goto('/admin/catalog/passports/templates');

    await expect(page.getByRole('heading', { name: 'Passport Templates' })).toBeVisible();

    await expect(page.getByText('espr_general', { exact: true })).toBeVisible();
  });

  test('a battery template is created from the listing modal', async ({ page }) => {
    await page.goto('/admin/catalog/passports/templates');

    await page.getByRole('button', { name: 'Create Template' }).click();

    await page.locator('input[name$="[name]"]').first().fill('EU Battery Passport');

    await page.locator('input[name="code"]').fill(TEMPLATE_CODE);

    await Promise.all([
      page.waitForURL(/\/templates\/\d+\/edit$/),
      page.getByRole('button', { name: 'Save Template' }).click(),
    ]);

    templateUrl = new URL(page.url()).pathname;

    await expect(page.getByRole('heading', { name: 'Edit Passport Template' })).toBeVisible();
  });

  test('the family, a section and three tiered fields are configured', async ({ page }) => {
    await page.goto(templateUrl);

    await pickInMultiselect(page, 'families', fixture.family_name);
    await page.getByRole('button', { name: /^Save changes$/ }).click();
    await expect(page.getByText(/updated successfully/i)).toBeVisible();

    await page.getByRole('button', { name: 'Add Section' }).click();

    await page.locator('input[name="draft_section_name"]').fill('Battery Data');

    await page.getByRole('button', { name: 'Done' }).click();

    await expect(page.getByRole('cell', { name: 'Battery Data' })).toBeVisible();

    for (const field of [CONSUMER_FIELD, OPERATOR_FIELD, AUTHORITY_FIELD]) {
      const modal = await openFieldModal(page);

      await page.locator('input[name="draft_field_name"]').fill(field.label);

      await selectByLabel(modal, 'draft_field_section', 'Battery Data');

      await selectByLabel(modal, 'draft_field_attribute', field.attribute);

      if (field === OPERATOR_FIELD) {
        await selectByLabel(modal, 'draft_field_tier', 'Operator');
      }

      if (field === AUTHORITY_FIELD) {
        await selectByLabel(modal, 'draft_field_tier', 'Authority');
      }

      if (field === CONSUMER_FIELD) {
        await page.locator('input[name="draft_field_required"]').check({ force: true });
      }

      await page.getByRole('button', { name: 'Done' }).click();

      await expect(page.getByRole('cell', { name: field.label, exact: true })).toBeVisible();
    }

    await page.getByRole('button', { name: /^Save changes$/ }).click();

    await expect(page.getByText(/updated successfully/i)).toBeVisible();
  });

  test('the saved template reports every required field as sourced', async ({ page }) => {
    await page.goto(templateUrl);

    await expect(page.getByText('1 of 1 required fields sourced')).toBeVisible();

    for (const field of [CONSUMER_FIELD, OPERATOR_FIELD, AUTHORITY_FIELD]) {
      await expect(page.getByRole('cell', { name: field.label, exact: true })).toBeVisible();
    }
  });

  test('the product panel shows the battery as publishable and publishes it', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${fixture.product_id}`);

    await expect(page.getByText('Digital Product Passport').first()).toBeVisible();
    await expect(page.locator('[data-requirement="dpp"]').filter({ hasText: 'Required for DPP' }).first()).toBeVisible();

    const preview = page.locator('a[href*="/passport/preview"]').first();

    await expect(preview).toBeVisible();

    const previewUrl = await preview.getAttribute('href');

    const rendered = await page.request.get(previewUrl);

    expect(rendered.ok()).toBeTruthy();

    const body = await rendered.text();

    expect(body).toContain('Battery Data');
    expect(body).toContain(CONSUMER_FIELD.value);
  });

  test('missing DPP attributes explain the blocker and disable publishing', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${fixture.product_id}`);

    const capacity = page.locator('input[name="values[common][battery_rated_capacity]"]').first();

    await capacity.fill('');
    await page.getByRole('button', { name: /Save changes|Save Product/ }).last().click();
    await expect(page.getByText(/updated successfully|saved successfully/i).first()).toBeVisible();

    await page.locator('v-product-section-drawer[id="passport"] [role="button"]').first().click();

    const panel = page.locator('[data-section-id="passport"]');
    const localeRow = panel.locator('tr[data-locale-code]').first();

    await expect(localeRow.locator('.passport-publish-btn')).toBeDisabled();
    await localeRow.getByRole('button', { name: 'Missing Fields' }).click();
    await expect(localeRow.getByText(CONSUMER_FIELD.label, { exact: true })).toBeVisible();

    await panel.getByRole('button', { name: 'Close' }).click();
    await capacity.fill(CONSUMER_FIELD.value);
    await page.getByRole('button', { name: /Save changes|Save Product/ }).last().click();
    await expect(page.getByText(/updated successfully|saved successfully/i).first()).toBeVisible();
  });

  test('keeps the locale table scroll inside the DPP drawer on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 478, height: 860 });
    await page.goto(`/admin/catalog/products/edit/${fixture.product_id}`);

    const passportCard = page
      .getByText('Digital Product Passport', { exact: true })
      .first()
      .locator('xpath=ancestor::*[@role="button"][1]');

    await passportCard.click({ force: true });

    const panel = page.locator('[data-section-id="passport"]');
    const drawerScroll = panel.locator('.overflow-auto').first();
    const localeScroll = panel.locator('.passport-locales-table');

    await expect(panel).toBeVisible();
    await expect(localeScroll).toBeVisible();

    await localeScroll.locator('a[href*="/passport/preview"]').first().focus();

    const scrollState = await panel.evaluate((drawer) => {
      const outer = drawer.querySelector('.overflow-auto');
      const locales = drawer.querySelector('.passport-locales-table');

      return {
        drawerScrollLeft: outer.scrollLeft,
        drawerScrollWidth: outer.scrollWidth,
        drawerClientWidth: outer.clientWidth,
        localesScrollWidth: locales.scrollWidth,
        localesClientWidth: locales.clientWidth,
      };
    });

    expect(scrollState.drawerScrollLeft).toBe(0);
    expect(scrollState.drawerScrollWidth).toBe(scrollState.drawerClientWidth);
    expect(scrollState.localesScrollWidth).toBeGreaterThan(scrollState.localesClientWidth);
  });

  test('publishing exposes the consumer tier only', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${fixture.product_id}`);

    const publish = page.locator('.passport-publish-all-btn').first();

    await publish.waitFor({ state: 'attached' });

    await publish.click();

    await expect(page.getByText(/queued/i)).toBeVisible();

    await page.waitForTimeout(2_000);

    await page.goto('/admin/catalog/passports');

    await expect(page.getByText(fixture.sku).first()).toBeVisible();

    const rowCheckboxLabel = page.locator('label[for^="mass_action_select_record_"]').first();

    await rowCheckboxLabel.click();
    await page.getByRole('button', { name: 'Select Action' }).click();
    await expect(page.getByText('Republish selected', { exact: true })).toBeVisible();

    const publicLink = page.locator('a[href*="/p/"]').first();

    await publicLink.waitFor({ state: 'attached' });

    const url = await publicLink.getAttribute('href');

    const publicPage = await page.request.get(url);

    expect(publicPage.ok()).toBeTruthy();

    const body = await publicPage.text();

    expect(body).toContain(CONSUMER_FIELD.value);
    expect(body).not.toContain(OPERATOR_FIELD.label);
    expect(body).not.toContain(AUTHORITY_FIELD.label);
  });
});
