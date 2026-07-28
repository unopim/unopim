const { test, expect } = require('../../utils/fixtures');

test.describe('My Account discard', () => {
  test('discarding reverts text, password and multiselect fields', async ({ adminPage }) => {
    await adminPage.goto('/admin/account', { waitUntil: 'networkidle' });

    const name = adminPage.locator('input[name="name"]');
    const currentPassword = adminPage.locator('input[name="current_password"]');
    const timezone = adminPage.locator('input[name="timezone"][type="hidden"]');

    const savedName = await name.inputValue();
    const savedTimezone = await timezone.inputValue();

    expect(savedTimezone).not.toBe('');

    await name.fill(`${savedName} CHANGED`);
    await currentPassword.fill('admin123');

    const group = adminPage.locator('[data-control-group]:has(input[name="timezone"])');

    await group.scrollIntoViewIfNeeded();
    await adminPage.locator('.unsaved-bar').evaluate((bar) => (bar.style.pointerEvents = 'none'));

    await group.locator('[role="combobox"]').click();
    await group.locator('input[name="timezone"][type="text"]').fill('Kolkata');
    await group.locator('[role="option"]').first().click();

    await expect(timezone).toHaveValue('Asia/Kolkata');
    await expect(adminPage.locator('.unsaved-bar')).toBeVisible();

    await adminPage.locator('.unsaved-bar').evaluate((bar) => (bar.style.pointerEvents = ''));
    await adminPage.locator('.unsaved-bar').getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').click();

    await expect(adminPage.locator('.unsaved-bar')).toBeHidden();

    await expect(name).toHaveValue(savedName);
    await expect(currentPassword).toHaveValue('');
    await expect(timezone).toHaveValue(savedTimezone);
  });
});
