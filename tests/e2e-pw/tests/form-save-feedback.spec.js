const { test, expect } = require('@playwright/test');

const NUMBER_INPUT_SELECTOR = 'input[name="values[common][product_number]"]';

/**
 * Resolve a working product edit URL that actually carries the `product_number`
 * attribute this spec drives. `PRODUCT_EDIT_ID` (or id 1) can land on a product
 * whose family never got that attribute assigned (e.g. a bare `default`-family
 * fixture from another spec) — ids and family mixes both drift across reseeds —
 * so this falls back to scanning the products grid's own first page of rows
 * (via their mass-action checkbox values, no extra navigation needed to read
 * them) for one that does.
 */
async function resolveProductEditUrl(page) {
  const preferredId = process.env.PRODUCT_EDIT_ID;

  if (preferredId) {
    return `/admin/catalog/products/edit/${preferredId}`;
  }

  await page.goto('/admin/catalog/products', { waitUntil: 'load' });
  await page.waitForLoadState('networkidle');

  const ids = await page.locator('input[name^="mass_action_select_record_"]').evaluateAll(
    (els) => els.map((el) => el.value),
  );

  for (const id of ids) {
    const url = `/admin/catalog/products/edit/${id}`;
    const response = await page.goto(url, { waitUntil: 'load' });

    if (!response || response.status() >= 400) {
      continue;
    }

    if (await page.locator(NUMBER_INPUT_SELECTOR).count()) {
      return url;
    }
  }

  throw new Error('No product in the grid\'s first page carries the product_number attribute.');
}

/**
 * Resolve a working export edit URL. `EXPORT_EDIT_ID` (or id 1) can 404 in an
 * environment whose export history doesn't happen to include that id (ids are
 * never reused once an export/job-tracker row is pruned), so this falls back
 * to whatever the exports grid's own first row actually is.
 */
async function resolveExportEditUrl(page) {
  const preferredId = process.env.EXPORT_EDIT_ID || '1';
  const preferredUrl = `/admin/data-transfer/exports/edit/${preferredId}`;

  const response = await page.goto(preferredUrl, { waitUntil: 'load' });

  if (response && response.status() < 400) {
    return preferredUrl;
  }

  await page.goto('/admin/data-transfer/exports', { waitUntil: 'load' });
  await page.waitForLoadState('networkidle');

  const editIcon = page.locator('span[title="Edit"]').first();
  await editIcon.waitFor({ state: 'visible', timeout: 15000 });
  await editIcon.click();
  await page.waitForURL(/\/data-transfer\/exports\/edit\/\d+/, { timeout: 15000 });

  return new URL(page.url()).pathname;
}

const GENERIC_ERROR = /Something went wrong while saving/i;

const NUMBER_INPUT = NUMBER_INPUT_SELECTOR;

const saveButton = (page) => page.locator('[data-unsaved-save]').first();

// The bar animates in, so a real click can land while it is still moving.
const clickSave = (page) => saveButton(page).evaluate((el) => el.click());

const toasts = (page) => page.locator('[role="alert"]');

const toast = (page, pattern) => toasts(page).filter({ hasText: pattern });

// vue-multiselect renders no native <select>, so pick from its option list.
const formatControl = (page) => page.locator('.multiselect:has(input[name="filters[file_format]"])');

const selectFormat = async (page, label) => {
  const control = formatControl(page);

  await control.click();
  await control.locator('li.multiselect__element').filter({ hasText: label }).first().click();

  await expect(control.locator('.multiselect__single')).toHaveText(label);
};

test.describe('unsaved-changes save feedback', () => {
  test('a validation failure flashes the failing field, not the generic ajax error', async ({ page }) => {
    await page.goto(await resolveProductEditUrl(page));

    // Empty the always-required SKU so the save is guaranteed to fail validation.
    await page.locator('input[name="values[common][sku]"]').fill('');
    await page.locator(NUMBER_INPUT).fill('e2e-save-feedback');
    await page.locator(NUMBER_INPUT).blur();

    await expect(saveButton(page)).toBeVisible();

    await clickSave(page);

    // Inline error proves validation ran and blocked the submit.
    await expect(page.getByText(/The SKU field is required/i).first()).toBeVisible();

    // The toast must state what actually happened. The generic ajax error would mean
    // the bar reported a request failure it never observed — nothing was ever sent.
    await expect(toast(page, /not saved.*highlighted fields/i).first()).toBeVisible();

    // Outlast any deferred verdict before ruling the generic error out.
    await page.waitForTimeout(2000);

    await expect(toast(page, GENERIC_ERROR)).toHaveCount(0);

    // Nothing left the browser, so the bar stays open and clickable for a retry.
    await expect(saveButton(page)).toBeEnabled();
  });

  test('a valid save reports success only, never the generic error', async ({ page }) => {
    const exportEditUrl = await resolveExportEditUrl(page);

    const original = (await formatControl(page).locator('.multiselect__single').innerText()).trim();
    const next = original === 'XLSX' ? 'XLS' : 'XLSX';

    await selectFormat(page, next);

    await expect(saveButton(page)).toBeVisible();

    // A slow response must not be reported as a failure while it is in flight.
    await page.route('**/data-transfer/exports/**', async (route) => {
      if (route.request().method() === 'POST') {
        await new Promise((resolve) => setTimeout(resolve, 1500));
      }

      await route.continue();
    });

    await clickSave(page);

    await expect(toast(page, GENERIC_ERROR)).toHaveCount(0);

    // A save that lands redirects to the export view; the toast can be swapped out
    // by that navigation, so the redirect is the stable success signal.
    await page.waitForURL(/\/data-transfer\/exports\/export\//);

    await expect(toast(page, GENERIC_ERROR)).toHaveCount(0);

    await page.unroute('**/data-transfer/exports/**');

    await page.goto(exportEditUrl);
    await selectFormat(page, original);
    await clickSave(page);
    await page.waitForURL(/\/data-transfer\/exports\/export\//);
  });
});
