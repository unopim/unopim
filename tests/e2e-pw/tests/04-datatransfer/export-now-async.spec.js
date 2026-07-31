const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Helper: Create an export job and land on its export profile page.
 */
async function createExport(adminPage, code) {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder, .multiselect__single').click();
  await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await clickSaveAndExpect(adminPage, 'Save changes', /Export created successfully/i);
  await expect(adminPage).toHaveURL(/\/admin\/data-transfer\/exports\/export\//);
}

test.describe('Export Now triggers asynchronously', () => {
  test('Export Now runs through XHR without a full page reload', async ({ adminPage }) => {
    const code = `exp-async-${generateUid()}`;

    await createExport(adminPage, code);

    // Marker on the live document: a full page reload wipes it, an XHR does not.
    await adminPage.evaluate(() => {
      window.__exportNowNoReload = true;
    });

    const [request] = await Promise.all([
      adminPage.waitForRequest((req) => /\/data-transfer\/exports\/export-now\//.test(req.url())),
      adminPage.getByRole('button', { name: 'Export Now' }).click(),
    ]);

    expect(request.isNavigationRequest()).toBe(false);

    const response = await request.response();
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/json');

    const payload = await response.json();
    expect(payload.redirect_url).toContain('job-tracker');

    // The tracker is reached through the SPA visit, so the marker survives.
    await expect(adminPage).toHaveURL(/\/admin\/data-transfer\/job-tracker\/track\//);
    expect(await adminPage.evaluate(() => window.__exportNowNoReload)).toBe(true);
  });
});
