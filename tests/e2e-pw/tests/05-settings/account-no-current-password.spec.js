const { test, expect } = require('../../utils/fixtures');

test.describe('My Account without the current password', () => {
  test('saves a name change while the current password field stays empty', async ({ adminPage }) => {
    await adminPage.goto('/admin/account', { waitUntil: 'networkidle' });

    const name = adminPage.locator('input[name="name"]');
    const currentPassword = adminPage.locator('input[name="current_password"]');

    const savedName = await name.inputValue();
    const newName = `${savedName} NP`;

    await name.fill(newName);

    await expect(currentPassword).toHaveValue('');
    await expect(adminPage.locator('.unsaved-bar')).toBeVisible();

    await adminPage.locator('.unsaved-bar').getByRole('button', { name: 'Save changes' }).click();

    await expect(adminPage.locator('#app').getByText(/updated successfully/i)).toBeVisible({ timeout: 20000 });

    await adminPage.reload({ waitUntil: 'networkidle' });
    await expect(name).toHaveValue(newName);

    await name.fill(savedName);
    await adminPage.locator('.unsaved-bar').getByRole('button', { name: 'Save changes' }).click();
    await expect(adminPage.locator('#app').getByText(/updated successfully/i)).toBeVisible({ timeout: 20000 });
  });
});
