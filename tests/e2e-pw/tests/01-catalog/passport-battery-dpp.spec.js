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

/**
 * The AI agent shell (`.ap-shell`) can open by default and, being a fixed overlay,
 * sits above whatever the test is trying to reach — hiding it with an injected
 * stylesheet races the app's own styles, and it is `v-if`-gated so a client-side
 * "hide" can still leave it intercepting clicks underneath. Closing it for real
 * via its own Close button is the only interaction that reliably removes it.
 */
async function closeAgentShell(page) {
  const closeBtn = page.locator('.ap-shell').getByRole('button', { name: 'Close', exact: true });

  if (await closeBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
    await closeBtn.click().catch(() => {});
  }

  await page.evaluate(() => {
    document.querySelectorAll('.ap-shell').forEach((el) => {
      el.style.setProperty('display', 'none', 'important');
    });
  }).catch(() => {});
}

/**
 * A `php artisan`/plain-`php` CLI process reads `.env` fresh on every boot, but the
 * already-running server this suite drives via BASE_URL may have been started with
 * DB_* env vars overriding a `.env` that has since drifted (shared checkout, other
 * suites editing it) — dotenv never overwrites an already-set process env var, so
 * the server keeps the database/prefix it booted with regardless. Without matching
 * overrides here, the seed script would write to whatever `.env` currently says
 * and the browser-driven test would never see the fixture it just created.
 */
const LOCAL_DB_ENV = process.env.PASSPORT_E2E_LOCAL === '1'
  ? {
    ...(process.env.PASSPORT_E2E_DB_DATABASE ? { DB_DATABASE: process.env.PASSPORT_E2E_DB_DATABASE } : {}),
    ...(process.env.PASSPORT_E2E_DB_PREFIX !== undefined ? { DB_PREFIX: process.env.PASSPORT_E2E_DB_PREFIX } : {}),
  }
  : {};

/** Dev envs run the app in docker; CI runs it on the host (PASSPORT_E2E_LOCAL=1). */
function inContainer(args) {
  if (process.env.PASSPORT_E2E_LOCAL === '1') {
    return execFileSync(args[0], args.slice(1), {
      cwd: `${__dirname}/../../../..`,
      encoding: 'utf-8',
      timeout: 600_000,
      env: { ...process.env, ...LOCAL_DB_ENV },
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

/**
 * A bare `php script.php` run (no Artisan command runner) prints whatever
 * PHP deprecations/warnings the boot triggers straight onto the same stdout
 * stream as our payload — CI's `display_errors=on` surfaces these where a
 * locally-muted `error_reporting` doesn't, so "the last line is the JSON"
 * isn't a safe assumption. The seed script wraps its payload in delimiters
 * so it can be pulled out regardless of what else lands on stdout.
 */
function seed() {
  const output = inContainer(['php', 'tests/e2e-pw/scripts/seed-passport-template-e2e.php']);

  const match = output.match(/<<<SEED_JSON>>>\s*([\s\S]*?)\s*<<<END_SEED_JSON>>>/);

  if (!match) {
    throw new Error(`seed-passport-template-e2e.php produced no parseable payload. Raw output:\n${output}`);
  }

  return JSON.parse(match[1]);
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

    inContainer([
      'php', 'artisan', 'tinker', '--execute',
      "\\Webkul\\ProductPassport\\Models\\PassportTemplateProxy::modelClass()::where('code', 'like', 'battery_e2e_%')->get()->each->delete();",
    ]);

    inContainer([
      'php', 'artisan', 'tinker', '--execute',
      "\\Webkul\\Core\\Models\\CoreConfig::updateOrCreate(['code' => 'general.magic_ai.agentic_pim.open_by_default'], ['value' => '0']);",
    ]);

    inContainer(['php', 'artisan', 'view:clear']);
  });

  /**
   * Dev builds render the Debugbar and the AI agent shell over the page; both
   * swallow clicks. A single injected stylesheet races the app's own styles
   * (whichever attaches last wins a same-specificity, same-!important tie), so
   * a MutationObserver re-asserts the hide on every DOM mutation instead of
   * relying on load order.
   */
  test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
      const hide = () => {
        document.querySelectorAll('.ap-shell, .phpdebugbar, .phpdebugbar-open-handler').forEach((el) => {
          el.style.setProperty('display', 'none', 'important');
        });
      };

      const start = () => {
        hide();
        new MutationObserver(hide).observe(document.documentElement, { childList: true, subtree: true });
      };

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
      } else {
        start();
      }
    });
  });

  test('templates listing ships the ESPR preset', async ({ page }) => {
    await page.goto('/admin/catalog/passports/templates');
    await closeAgentShell(page);

    await expect(page.getByRole('heading', { name: 'Passport Templates' })).toBeVisible();

    await expect(page.getByText('espr_general', { exact: true })).toBeVisible();
  });

  test('a battery template is created from the listing modal', async ({ page }) => {
    await page.goto('/admin/catalog/passports/templates');
    await closeAgentShell(page);

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
    await closeAgentShell(page);

    await pickInMultiselect(page, 'families', fixture.family_name);
    await page.locator('label[for="is_enabled"]').click();
    await page.getByRole('button', { name: /^Save changes$/ }).click();
    await expect(page.getByText(/updated successfully/i)).toBeVisible();
    await page.waitForLoadState('load');
    await page.waitForTimeout(1500);
    await closeAgentShell(page);

    await page.getByRole('button', { name: 'Add Section' }).click();
    await closeAgentShell(page);

    await page.locator('input[name="draft_section_name"]').fill('Battery Data');

    await page.getByRole('button', { name: 'Done' }).click();

    await expect(page.getByRole('cell', { name: 'Battery Data' })).toBeVisible();

    for (const field of [CONSUMER_FIELD, OPERATOR_FIELD, AUTHORITY_FIELD]) {
      await closeAgentShell(page);
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
      await page.locator('input[name="draft_field_name"]').waitFor({ state: 'detached', timeout: 10000 }).catch(() => {});

      await expect(page.getByRole('cell', { name: field.label, exact: true }).first()).toBeVisible();
    }

    await closeAgentShell(page);
    await page.getByRole('button', { name: /^Save changes$/ }).click();

    await expect(page.getByText(/updated successfully/i)).toBeVisible();
  });

  test('the saved template reports every required field as sourced', async ({ page }) => {
    await page.goto(templateUrl);
    await closeAgentShell(page);

    await expect(page.getByText('1 of 1 required fields sourced')).toBeVisible();

    for (const field of [CONSUMER_FIELD, OPERATOR_FIELD, AUTHORITY_FIELD]) {
      await expect(page.getByRole('cell', { name: field.label, exact: true }).first()).toBeVisible();
    }
  });

  test('the product panel shows the battery as publishable and publishes it', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${fixture.product_id}`);
    await closeAgentShell(page);

    await expect(page.getByText('Digital Product Passport').first()).toBeVisible();
    await expect(page.locator('[data-requirement="dpp"]').filter({ hasText: 'Required for DPP' }).first()).toBeVisible();

    await page.locator('[role="button"]').filter({ hasText: 'Digital Product Passport' })
      .filter({ hasText: 'View' })
      .first()
      .click();

    const preview = page.locator('[data-section-id="passport"] tr[data-locale-code="en_US"] a[href*="/passport/preview"]').first();

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
    await closeAgentShell(page);

    const capacity = page.locator('input[name="values[common][battery_rated_capacity]"]').first();

    if (await capacity.inputValue()) {
      await capacity.fill('');
      await page.getByRole('button', { name: /Save changes|Save Product/ }).last().click();
      await expect(page.getByText(/updated successfully|saved successfully/i).first()).toBeVisible();
      await page.goto(`/admin/catalog/products/edit/${fixture.product_id}`);
      await closeAgentShell(page);
    }

    await page.locator('[role="button"]').filter({ hasText: 'Digital Product Passport' })
      .filter({ hasText: 'View' })
      .first()
      .click({ force: true });

    const panel = page.locator('[data-section-id="passport"]');
    const localeRow = panel.locator('tr[data-locale-code]').first();

    await expect(localeRow.locator('.passport-publish-btn')).toBeDisabled();
    await localeRow.getByRole('button', { name: 'Missing Fields' }).click();

    const popover = page.locator('.passport-missing-fields:visible');

    await expect(popover.getByText(CONSUMER_FIELD.label, { exact: true })).toBeVisible();

    const clipping = await popover.evaluate((element) => {
      const rect = element.getBoundingClientRect();
      const clippers = [];

      for (let node = element.parentElement; node && node !== document.body; node = node.parentElement) {
        const style = getComputedStyle(node);

        if (! /auto|scroll|hidden/.test(style.overflowX + style.overflowY)) {
          continue;
        }

        const bounds = node.getBoundingClientRect();

        if (
          rect.top < bounds.top - 1
          || rect.bottom > bounds.bottom + 1
          || rect.left < bounds.left - 1
          || rect.right > bounds.right + 1
        ) {
          clippers.push(node.className);
        }
      }

      return {
        clippers,
        overflowsViewport: rect.bottom > window.innerHeight + 1 || rect.top < -1,
      };
    });

    expect(clipping.clippers).toEqual([]);
    expect(clipping.overflowsViewport).toBe(false);

    await panel.getByRole('button', { name: 'Close' }).click();
    await capacity.fill(CONSUMER_FIELD.value);
    await page.getByRole('button', { name: /Save changes|Save Product/ }).last().click();
    await expect(page.getByText(/updated successfully|saved successfully/i).first()).toBeVisible();
  });

  test('keeps the locale table scroll inside the DPP drawer on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 478, height: 860 });
    await page.goto(`/admin/catalog/products/edit/${fixture.product_id}`);
    await closeAgentShell(page);

    const passportCard = page.locator('[role="button"]').filter({ hasText: 'Digital Product Passport' })
      .filter({ hasText: 'View' })
      .first();

    await passportCard.click({ force: true });

    const panel = page.locator('[data-section-id="passport"]');
    const drawerScroll = panel.locator('.overflow-y-auto').first();
    const localeScroll = panel.locator('.passport-locales-table');

    await expect(panel).toBeVisible();
    await expect(localeScroll).toBeVisible();

    await localeScroll.locator('a[href*="/passport/preview"]').first().focus();

    const scrollState = await panel.evaluate((drawer) => {
      const outer = drawer.querySelector('.overflow-y-auto');
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
    await closeAgentShell(page);

    await page.locator('[role="button"]').filter({ hasText: 'Digital Product Passport' })
      .filter({ hasText: 'View' })
      .first()
      .click();

    const publish = page.locator('.passport-publish-all-btn').first();

    await publish.waitFor({ state: 'visible' });

    await publish.click();

    await expect(page.getByText(/queued|published/i).first()).toBeVisible();

    await page.waitForTimeout(2_000);

    await page.goto('/admin/catalog/passports');
    await closeAgentShell(page);

    const searchInput = page.getByPlaceholder('Search').first();
    await searchInput.waitFor({ state: 'visible', timeout: 30000 });
    await searchInput.fill(fixture.sku);
    await page.keyboard.press('Enter');
    await page.waitForLoadState('load');

    const row = page.locator('div.row').filter({ hasText: fixture.sku }).first();

    await expect(row).toBeVisible();

    const rowCheckboxLabel = row.locator('label[for^="mass_action_select_record_"]').first();

    await rowCheckboxLabel.click();
    await page.getByRole('button', { name: 'Select Action' }).click();
    await expect(page.getByText('Republish selected', { exact: true })).toBeVisible();

    const url = await row.locator('p.truncate[title^="http"]').first().getAttribute('title');

    expect(url).toBeTruthy();

    const publicPage = await page.request.get(url);

    expect(publicPage.ok()).toBeTruthy();

    const body = await publicPage.text();

    expect(body).toContain(CONSUMER_FIELD.value);
    expect(body).not.toContain(OPERATOR_FIELD.label);
    expect(body).not.toContain(AUTHORITY_FIELD.label);
  });
});
