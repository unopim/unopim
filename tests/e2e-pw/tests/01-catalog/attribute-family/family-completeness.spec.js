const { test, expect } = require('../../../utils/family-fixtures');
const { generateUid } = require('../../../utils/helpers');
const { createFamily, deleteFamilyByCode, gotoTab, withFamilyPage } = require('../../../utils/family-helpers');

test.describe.serial('Attribute Family — Completeness tab', () => {
  let family;

  test.beforeAll(async ({ browser }) => {
    family = await withFamilyPage(browser, (page) => createFamily(page, `famcmp_${generateUid()}`, { basedOn: 'default' }));
  });

  test.afterAll(async ({ browser }) => {
    await withFamilyPage(browser, (page) => deleteFamilyByCode(page, family.code).catch(() => {}));
  });

  test('completeness tab renders datagrid with attributes', async ({ adminPage }) => {
    const page = adminPage;
    await gotoTab(page, family.id, 'completeness');
    // Grid header + at least one per-row channel-requirements multiselect.
    await expect(page.locator('#app').getByText('Required in Channels').first())
      .toBeVisible({ timeout: 25000 });
    await expect(page.locator('.multiselect').first()).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Search' }).first()).toBeVisible();
  });

  test('set a channel requirement on a row shows success toast', async ({ adminPage }) => {
    const page = adminPage;
    await gotoTab(page, family.id, 'completeness');

    await page.locator('input[name="channel_requirements"]').first().waitFor({ state: 'attached', timeout: 25000 });
    // The completeness datagrid re-creates its row nodes on each notification poll, so
    // Playwright never sees the option as "stable" for a click. Drive vue-multiselect's
    // native select synchronously (focus opens the dropdown; the option's mouse events
    // fire @select) so the re-render can't interleave.
    await page.waitForTimeout(1500);

    const updateSaved = page.waitForResponse(
      (r) => r.url().includes('/completeness-settings/update') && r.request().method() === 'POST',
      { timeout: 20000 },
    );

    const selected = await page.evaluate(async () => {
      const control = document.querySelector('input[name="channel_requirements"]')?.closest('.multiselect');
      if (! control) {
        return false;
      }

      control.querySelector('.multiselect__input')?.focus();
      await new Promise((resolve) => setTimeout(resolve, 400));

      const option = control.querySelector('.multiselect__option');
      if (! option) {
        return false;
      }

      ['mousedown', 'mouseup', 'click'].forEach((type) => {
        option.dispatchEvent(new MouseEvent(type, { bubbles: true }));
      });

      return true;
    });
    expect(selected).toBe(true);

    await updateSaved;
    await expect(page.locator('#app').getByText(/Completeness updated successfully/i).first())
      .toBeVisible({ timeout: 20000 });
  });

  test('completeness search filters the grid', async ({ adminPage }) => {
    const page = adminPage;
    await gotoTab(page, family.id, 'completeness');
    const search = page.getByRole('textbox', { name: 'Search' }).first();
    await search.fill('sku');
    await page.keyboard.press('Enter');
    await page.waitForTimeout(1500);
    await expect(page.locator('#app').getByText(/sku/i).first()).toBeVisible();
  });
});
