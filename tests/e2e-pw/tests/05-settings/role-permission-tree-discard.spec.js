const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

/**
 * Discarding used to dispatch a synthetic `change` on every control in the form.
 * Each permission checkbox re-entered the tree's own change handler, which
 * toggles the node together with its ancestors and children — so discarding a
 * single tick left most of the tree selected instead of clearing it.
 */
const checkedPermissions = (page) =>
  page.evaluate(() =>
    [...document.querySelectorAll('.unsaved-root input[name="permissions[]"]')]
      .filter((box) => box.checked)
      .map((box) => box.value)
  );

async function discard(page) {
  await page.locator('.unsaved-bar').getByRole('button', { name: 'Discard' }).click();
  await page.locator('button.danger-button').click();
  await expect(page.locator('.unsaved-bar')).toBeHidden();
}

test.describe('Role permission tree discard', () => {
  test('ticking a permission then discarding clears it and nothing else', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');
    await adminPage.getByRole('link', { name: 'Create Role' }).click();
    await adminPage.waitForLoadState('networkidle');

    expect(await checkedPermissions(adminPage)).toEqual([]);

    const dashboard = adminPage.locator('label[for] span.icon-checkbox-normal').first();

    await dashboard.click();

    expect(await checkedPermissions(adminPage)).toEqual(['dashboard']);
    await expect(adminPage.locator('.unsaved-bar')).toBeVisible();

    await discard(adminPage);

    expect(await checkedPermissions(adminPage)).toEqual([]);
  });

  test('checking a permission marks the form as unsaved', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');
    await adminPage.getByRole('link', { name: 'Create Role' }).click();
    await adminPage.waitForLoadState('networkidle');

    await expect(adminPage.locator('.unsaved-bar')).toBeHidden();

    await adminPage.locator('label[for] span.icon-checkbox-normal').first().click();

    await expect(adminPage.locator('.unsaved-bar')).toBeVisible();
  });
});
