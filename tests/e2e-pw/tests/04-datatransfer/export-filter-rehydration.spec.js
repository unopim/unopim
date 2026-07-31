const { test, expect } = require('../../utils/fixtures');

const dirtyGroups = async (page) =>
  page.$$eval('[data-control-group].unsaved-dirty', groups =>
    groups.map(group => (group.querySelector('label')?.textContent || '').replace(/\s+/g, ' ').trim())
  );

test.describe('Export profile filter rehydration', () => {
  test('does not mark dependent filters dirty when the edit page loads', async ({ adminPage }) => {
    await adminPage.goto('/admin/data-transfer/exports');
    await adminPage.waitForLoadState('networkidle');

    await adminPage.locator('a[href*="/data-transfer/exports/edit/"]').first().click();
    await adminPage.waitForLoadState('networkidle');

    await adminPage.waitForTimeout(1500);

    expect(await dirtyGroups(adminPage)).toEqual([]);

    await expect(adminPage.locator('button[data-unsaved-save]')).toBeHidden();
  });

  test('still marks a filter dirty when the user changes it', async ({ adminPage }) => {
    await adminPage.goto('/admin/data-transfer/exports');
    await adminPage.waitForLoadState('networkidle');

    await adminPage.locator('a[href*="/data-transfer/exports/edit/"]').first().click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.waitForTimeout(1500);

    const locales = adminPage.locator('[data-control-group]:has(input[name="filters[locales]"])');

    await locales.locator('.multiselect').click();

    const option = locales.locator('.multiselect__element').first();
    await option.waitFor({ state: 'visible' });
    await option.click();

    await expect
      .poll(async () => (await dirtyGroups(adminPage)).length, { timeout: 5000 })
      .toBeGreaterThan(0);
  });
});
