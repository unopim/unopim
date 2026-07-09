const { test, expect } = require('../../utils/fixtures');

test.describe('System Settings hub', () => {
    test('lists grouped rows, filters on search, and navigates into a row', async ({ adminPage }) => {
        await adminPage.goto('/admin/settings/system');

        const appearance = adminPage.getByRole('link', { name: /Appearance/i });
        await expect(appearance).toBeVisible();

        // Search filters rows and hides non-matching ones.
        await adminPage.locator('input[data-settings-search]').fill('debug');
        await expect(adminPage.getByRole('link', { name: /Debug/i })).toBeVisible();
        await expect(appearance).toBeHidden();

        // Clear the filter, then navigate into the Appearance row.
        await adminPage.locator('input[data-settings-search]').fill('');
        await expect(appearance).toBeVisible();
        await appearance.click();
        await expect(adminPage).toHaveURL(/\/admin\/settings\/appearance/);
    });

    test('opens the generic fields editor for the debug row', async ({ adminPage }) => {
        await adminPage.goto('/admin/settings/system');

        await adminPage.getByRole('link', { name: /Debug/i }).click();

        // Lands on the generic editor for the debug group and renders its field.
        await expect(adminPage).toHaveURL(/\/admin\/settings\/system\/system\.debug/);
        await expect(adminPage.getByText(/Debug/i).first()).toBeVisible();
    });
});
