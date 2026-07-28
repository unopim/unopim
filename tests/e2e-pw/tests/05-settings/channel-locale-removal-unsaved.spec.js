const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('Channel locale removal', () => {
  test('removing a locale reveals the unsaved bar with Save changes', async ({ adminPage }) => {
    await navigateTo(adminPage, 'channels');

    await adminPage.locator('#app').locator('span[title="Edit"]').first().click();
    await adminPage.waitForURL(/\/channels\/edit\/\d+$/, { timeout: 20000 });
    await adminPage.waitForLoadState('networkidle');

    const group = adminPage.locator('[data-control-group]:has(input[name="locales"])');
    const hidden = adminPage.locator('input[name="locales"][type="hidden"]');

    await expect(group).toBeVisible();

    const before = await hidden.inputValue();

    expect(before, 'channel under test needs at least one locale assigned').not.toBe('');

    await expect(adminPage.locator('.unsaved-bar')).toBeHidden();

    await group.locator('.multiselect__tag-icon').first().click();

    await expect(hidden).not.toHaveValue(before);

    const bar = adminPage.locator('.unsaved-bar');

    await expect(bar).toBeVisible();
    await expect(bar.locator('[data-unsaved-save]')).toBeVisible();

    await bar.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').click();
    await expect(bar).toBeHidden();
  });
});
