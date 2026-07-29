const { test, expect } = require('../../utils/fixtures');
const { navigateTo, clickEditOnRow, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Editing an export profile whose code predates the code format rule.
 *
 * The seeded "Product Export" profile carries a space in its code. The edit
 * form renders the code disabled and the update never persists it, yet the
 * update still validated the code's format — so saving any change to such a
 * profile failed with "The code format is invalid." on a field the user cannot
 * even edit.
 */
test.describe('Export profile — legacy code', () => {
  test.setTimeout(120000);

  const localesField = (page) => page.locator('input[name="filters[locales]"][type="text"]');
  const localesValue = (page) => page.locator('input[name="filters[locales]"][type="hidden"]');

  /** Open the edit page of the seeded profile whose code contains a space. */
  async function openLegacyProfile(page) {
    await navigateTo(page, 'exports');

    const row = page.locator('div.row.grid.cursor-pointer').filter({ hasText: 'Product Export' }).first();
    await expect(row, 'the seeded "Product Export" profile must exist').toBeVisible({ timeout: 20000 });

    await clickEditOnRow(page, 'Product Export');
    await page.waitForURL(/\/data-transfer\/exports\/edit\/\d+/, { timeout: 20000 });

    // The filter multiselects resolve their stored selection over XHR, so the
    // hidden value is empty for a moment after load. Wait for it to settle
    // before reading it, otherwise a toggle would act on stale state.
    await page.waitForLoadState('networkidle').catch(() => {});
    await expect
      .poll(
        async () => {
          const first = await localesValue(page).inputValue();
          await page.waitForTimeout(700);

          return first === (await localesValue(page).inputValue());
        },
        { timeout: 20000 },
      )
      .toBe(true);
  }

  test('saves a locale change on a profile whose code contains a space', async ({ adminPage }) => {
    await openLegacyProfile(adminPage);

    // The code is presented read-only, so its format is not something the user can fix.
    const code = adminPage.locator('input[name="code"][type="text"]');
    await expect(code).toBeDisabled();
    await expect(code).toHaveValue(/\s/);

    const before = await localesValue(adminPage).inputValue();

    // Toggle the locale filter to whichever of the two locales is not selected,
    // so the test is not order-dependent across runs.
    const target = before.includes('de_DE') ? 'English (United States)' : 'German (Germany)';
    const targetCode = target === 'German (Germany)' ? 'de_DE' : 'en_US';

    await localesField(adminPage).locator('xpath=ancestor::div[contains(@class,"multiselect")][1]').click();
    await adminPage.getByRole('option', { name: target }).first().click();

    await expect(localesValue(adminPage)).toHaveValue(new RegExp(targetCode));

    await clickSaveAndExpect(
      adminPage,
      'Save changes',
      /updated successfully/i,
      /\/data-transfer\/exports\/export\/\d+/,
    );

    // No validation error was raised on the untouched code field.
    await expect(adminPage.locator('#app').getByText(/code format is invalid/i)).toHaveCount(0);

    // The change survived the round trip.
    await openLegacyProfile(adminPage);
    await expect(localesValue(adminPage)).toHaveValue(new RegExp(targetCode));
  });
});
