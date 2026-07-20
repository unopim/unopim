const { test, expect } = require('@playwright/test');

// Self-logs-in against the target base URL. The admin login route is rate limited
// (5/min), so assertions are grouped into as few tests as possible to keep the
// number of logins low.
test.use({ storageState: { cookies: [], origins: [] } });

const crumb = (page) => page.locator('nav[aria-label="Breadcrumb"]');

test.describe('admin breadcrumbs', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/admin/login');
        await page.getByRole('textbox', { name: 'Email Address' }).fill(process.env.ADMIN_EMAIL || 'admin@example.com');
        await page.getByRole('textbox', { name: 'Password' }).fill(process.env.ADMIN_PASSWORD || 'admin123');
        await page.getByRole('button', { name: 'Sign In' }).click();
        await page.waitForURL(/\/admin\/dashboard/, { timeout: 20000 });
    });

    test('renders the correct breadcrumb for each page type', async ({ page }) => {
        // In-menu list page: parent group + current page.
        await page.goto('/admin/catalog/categories');
        await expect(crumb(page).first()).toHaveText(/Catalog\s*\/\s*Categories/);

        // Hub fields page: leaf kept even though its URL sits under the hub URL.
        await page.goto('/admin/configuration/system/system.email');
        await expect(crumb(page).first()).toHaveText(/Configuration\s*\/\s*System Settings\s*\/\s*Email/);

        // Off-menu hub page resolved to its sidebar parent.
        await page.goto('/admin/settings/appearance');
        await expect(crumb(page).first()).toHaveText(/Configuration\s*\/\s*System Settings\s*\/\s*Appearance/);

        // In-menu top-level page (Integrations) still shows the trail.
        await page.goto('/admin/configuration/integrations');
        await expect(crumb(page).first()).toContainText('Integrations');

        // Tabbed edit page: exactly one breadcrumb (tab panels don't add their own).
        await page.goto('/admin/catalog/attribute-families/edit/1?variants');
        await expect(crumb(page)).toHaveCount(1);
        await expect(crumb(page)).toContainText('Attribute Families');

        // Top-level page with no menu ancestor: no breadcrumb.
        await page.goto('/admin/dashboard');
        await expect(crumb(page)).toHaveCount(0);
    });

    test('keeps the breadcrumb after an ajax navigation between pages', async ({ page }) => {
        await page.goto('/admin/configuration/webhook');
        await page.locator('#unopim-sidebar a[href$="/admin/configuration/integrations"]').first().click();
        await page.waitForURL(/\/configuration\/integrations$/, { timeout: 15000 });
        await expect(crumb(page).first()).toContainText('Integrations');
    });
});
