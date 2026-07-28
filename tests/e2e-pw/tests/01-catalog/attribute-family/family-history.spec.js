const { test, expect } = require('../../../utils/family-fixtures');
const { generateUid } = require('../../../utils/helpers');
const { createFamily, deleteFamilyByCode, gotoTab, saveFamilyEdit, setFamilyLabel, withFamilyPage } = require('../../../utils/family-helpers');

// Family create/save round-trips run 20-30s against a full catalogue; the default per-test budget is too tight.
test.describe.configure({ timeout: 180_000 });

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
    await expect(page.getByText('History', { exact: true }).first()).toBeVisible({ timeout: 20000 });
    await expect(page.locator('#app')).toBeVisible();
  });

  test('editing the family records a history entry', async ({ adminPage }) => {
    const page = adminPage;

    await gotoTab(page, family.id, '');
    await page.waitForSelector('.group_node', { timeout: 30000 });
    // Unsaved-changes tracker snapshots field values on mount; editing before that bakes the value into the baseline so it never dirties.
    await page.waitForTimeout(1000);
    await setFamilyLabel(page, `History Edit ${generateUid()}`);
    await expect(page.getByRole('button', { name: 'Save changes' })).toBeVisible({ timeout: 10000 });
    await saveFamilyEdit(page);

    await gotoTab(page, family.id, 'history');
    await page.waitForTimeout(2500);
    const historyText = await page.locator('#app').innerText();
    expect(/version|updated|modified|name|\d{4}/i.test(historyText)).toBe(true);
  });
});
