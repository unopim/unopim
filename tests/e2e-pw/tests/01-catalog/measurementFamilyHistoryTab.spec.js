const { test, expect } = require('../../utils/fixtures');

test.describe('Edit page header - History tab Save button', () => {
  test.setTimeout(90000);

  test('should not render the Save button on the measurement family History tab', async ({ adminPage }) => {
    await adminPage.goto('/admin/measurement/families', { waitUntil: 'domcontentloaded' });

    await adminPage.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('domcontentloaded');

    await adminPage.getByText('History', { exact: true }).click();
    await adminPage.waitForURL(/history/, { timeout: 30000 });
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(adminPage.locator('button.primary-button[form="measurement_family_edit_form"]')).toHaveCount(0);
  });

  test('should not render the Save button on the channel History tab', async ({ adminPage }) => {
    await adminPage.goto('/admin/settings/channels', { waitUntil: 'domcontentloaded' });

    await adminPage.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('domcontentloaded');

    await adminPage.getByText('History', { exact: true }).click();
    await adminPage.waitForURL(/history/, { timeout: 30000 });
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(adminPage.getByRole('button', { name: 'Save Channel' })).toHaveCount(0);
  });
});
