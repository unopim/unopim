const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('Catalog Locale - Fallback When Not Configured (#1247)', () => {
  test.setTimeout(90000);

  test('should scope translatable fields to the application locale, not an arbitrary channel locale', async ({ adminPage }) => {
    await navigateTo(adminPage, 'associationTypes');

    await adminPage.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    const appLocale = await adminPage.evaluate(
      () => document.querySelector('meta[http-equiv="content-language"]')?.content
    );

    expect(appLocale).toBeTruthy();

    const activeLocale = adminPage.locator(`text=(${appLocale})`).first();

    await expect(activeLocale).toBeVisible();

    const body = await adminPage.textContent('body');

    expect(body).not.toContain('(de_DE)');
  });
});
