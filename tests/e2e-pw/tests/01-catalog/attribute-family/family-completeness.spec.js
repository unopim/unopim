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

    const wrapper = page.locator('input[name="channel_requirements"]')
      .first().locator('xpath=ancestor::*[contains(@class,"multiselect")][1]');
    await wrapper.waitFor({ state: 'visible', timeout: 25000 });
    await wrapper.click();
    const option = page.locator('.multiselect__content-wrapper li:visible, .multiselect__element:visible').first();
    await option.waitFor({ state: 'visible', timeout: 8000 });
    await option.click();

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
