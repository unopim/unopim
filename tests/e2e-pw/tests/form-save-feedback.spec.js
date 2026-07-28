const { test, expect } = require('@playwright/test');

// Ids differ per seeded workspace, hence the overrides.
const PRODUCT_EDIT = `/admin/catalog/products/edit/${process.env.PRODUCT_EDIT_ID || 1}`;
const EXPORT_EDIT = `/admin/data-transfer/exports/edit/${process.env.EXPORT_EDIT_ID || 1}`;

const GENERIC_ERROR = /Something went wrong while saving/i;

const NUMBER_INPUT = 'input[name="values[common][product_number]"]';

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
    await page.goto(PRODUCT_EDIT);

    // Dirty an optional field so the save bar opens while required fields stay empty.
    await page.locator(NUMBER_INPUT).fill('e2e-save-feedback');
    await page.locator(NUMBER_INPUT).blur();

    await expect(saveButton(page)).toBeVisible();

    await clickSave(page);

    // Inline error proves validation ran and blocked the submit.
    await expect(page.getByText(/The Name field is required/i).first()).toBeVisible();

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
    await page.goto(EXPORT_EDIT);

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

    await page.goto(EXPORT_EDIT);
    await selectFormat(page, original);
    await clickSave(page);
    await page.waitForURL(/\/data-transfer\/exports\/export\//);
  });
});
