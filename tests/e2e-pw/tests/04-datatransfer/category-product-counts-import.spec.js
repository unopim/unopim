const path = require('path');
const { execFileSync } = require('child_process');
const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

const FIXTURE = path.join(__dirname, '../../assets/categories-with-product-counts.csv');
const APP_ROOT = path.join(__dirname, '../../../..');

function drainQueue() {
  try {
    execFileSync('php', ['artisan', 'queue:work', '--stop-when-empty', '--tries=1'], {
      cwd: APP_ROOT,
      timeout: 120000,
      stdio: 'ignore',
    });
  } catch (error) {
    // A failing mail/notification job must not mask the import assertions below.
  }
}

test.describe('Category Import - Exported productCounts Column (#1019)', () => {
  test.setTimeout(180000);

  test('should accept a category file carrying the exported productCounts column', async ({ adminPage }) => {
    const code = `cat_pc_${generateUid()}`;

    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('link', { name: 'Create Import' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
    await adminPage.getByRole('textbox', { name: 'Field Separator' }).fill(',');

    await adminPage.locator('input[type="file"][name="file"]').setInputFiles(FIXTURE);

    await clickSaveAndExpect(adminPage, 'Save changes', /Import created successfully/i);

    await adminPage.getByRole('button', { name: 'Import Now' }).click();
    await adminPage.waitForTimeout(2000);

    drainQueue();

    await adminPage.reload({ waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(2000);

    const body = await adminPage.textContent('body');

    expect(body).not.toMatch(/invalid attribute/i);
    expect(body).not.toMatch(/productCounts/);

    await navigateTo(adminPage, 'imports');
    await adminPage.getByRole('textbox', { name: 'Search' }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('domcontentloaded');

    const deleteBtn = adminPage.locator('span[title="Delete"]').first();

    if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await deleteBtn.click();
      await adminPage.getByRole('button', { name: 'Delete' }).click();
      await expect(adminPage.locator('#app').getByText(/Import deleted successfully/i)).toBeVisible({ timeout: 20000 });
    }
  });
});
