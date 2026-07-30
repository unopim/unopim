const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('Association Type - History Tab Update Button (#1261)', () => {
  test.setTimeout(90000);

  test('should not show the Update Association Type button on the History tab', async ({ adminPage }) => {
    await navigateTo(adminPage, 'associationTypes');

    await adminPage.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('domcontentloaded');

    const updateButton = adminPage.getByRole('button', { name: 'Update Association Type' });

    await adminPage.getByText('History', { exact: true }).click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    expect(adminPage.url()).toContain('history');

    await expect(updateButton).toHaveCount(0);
  });
});
