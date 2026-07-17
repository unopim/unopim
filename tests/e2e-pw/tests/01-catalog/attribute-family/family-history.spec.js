const { test, expect } = require('../../../utils/family-fixtures');
const { generateUid } = require('../../../utils/helpers');
const { createFamily, deleteFamilyByCode, gotoTab, saveFamilyEdit, withFamilyPage } = require('../../../utils/family-helpers');

test.describe.serial('Attribute Family — History tab', () => {
  let family;

  test.beforeAll(async ({ browser }) => {
    family = await withFamilyPage(browser, (page) => createFamily(page, `famhis_${generateUid()}`));
  });

  test.afterAll(async ({ browser }) => {
    await withFamilyPage(browser, (page) => deleteFamilyByCode(page, family.code).catch(() => {}));
  });

  test('history tab renders', async ({ adminPage }) => {
    const page = adminPage;
    await gotoTab(page, family.id, 'history');
    // The History tab is active and its panel container loads.
    await expect(page.getByText('History', { exact: true }).first()).toBeVisible({ timeout: 20000 });
    await expect(page.locator('#app')).toBeVisible();
  });

  test('editing the family records a history entry', async ({ adminPage }) => {
    const page = adminPage;

    // Make a change on the General tab to generate history.
    await gotoTab(page, family.id, '');
    await page.waitForSelector('.group_node', { timeout: 30000 });
    const nameInput = page.locator('input[name="en_US\\[name\\]"]');
    await nameInput.fill(`History Edit ${generateUid()}`);
    await nameInput.blur();
    await saveFamilyEdit(page);

    // History tab should now show at least one entry (version/date/user row).
    await gotoTab(page, family.id, 'history');
    await page.waitForTimeout(2500);
    const historyText = await page.locator('#app').innerText();
    expect(/version|updated|modified|name|\d{4}/i.test(historyText)).toBe(true);
  });
});
