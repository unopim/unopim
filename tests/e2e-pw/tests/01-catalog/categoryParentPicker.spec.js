const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('Category parent picker', () => {
  test('updates the parent field and closes the drawer when a category is picked', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    await adminPage.waitForLoadState('networkidle');

    await adminPage.getByText('[root]', { exact: true }).first().click();
    await adminPage.waitForLoadState('networkidle');

    const parentField = adminPage.locator('div.cursor-pointer:has(> span.truncate)').filter({
      hasText: /Root level|\//,
    }).first();

    const before = (await parentField.innerText()).trim();

    await parentField.click();

    const drawerOption = adminPage.locator('input[name="parent_id_picker"]').nth(1);
    await drawerOption.waitFor({ state: 'attached' });
    await drawerOption.dispatchEvent('change');

    await expect
      .poll(async () => (await parentField.innerText()).trim(), { timeout: 5000 })
      .not.toBe(before);

    await expect(adminPage.locator('input[name="parent_id_picker"]')).toHaveCount(0);
  });

  test('resets the parent field to root level and closes the drawer', async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    await adminPage.waitForLoadState('networkidle');

    await adminPage.getByText('[root]', { exact: true }).first().click();
    await adminPage.waitForLoadState('networkidle');

    const parentField = adminPage.locator('div.cursor-pointer:has(> span.truncate)').filter({
      hasText: /Root level|\//,
    }).first();

    await parentField.click();

    const rootOption = adminPage.locator('input[name="parent_id_picker"][value=""]');
    await rootOption.waitFor({ state: 'attached' });
    await rootOption.dispatchEvent('change');

    await expect
      .poll(async () => (await parentField.innerText()).trim(), { timeout: 5000 })
      .toBe('Root level');

    await expect(adminPage.locator('input[name="parent_id_picker"]')).toHaveCount(0);
  });
});
