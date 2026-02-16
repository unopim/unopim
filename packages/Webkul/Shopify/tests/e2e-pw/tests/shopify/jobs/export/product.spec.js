import { test, expect } from '@playwright/test';
test.use({ storageState: 'storage/auth.json' }); // Reuse login session
// test.use({ launchOptions: { slowMo: 1000 } }); // Slow down actions by 1 second

test.describe('UnoPim Shopify setting tab Navigation', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to the Shopify Credentials Page
        await page.goto('/admin/settings/data-transfer/exports');
    });
    test('Navigate to export settings and click Create Export', async ({ page }) => {
        // Navigate to the target URL

        // Verify current URL
        await expect(page).toHaveURL('/admin/settings/data-transfer/exports');

        // Click on the "Create Export" button
        await page.click('a.primary-button[href$="/exports/create"]');

        // Expect URL to change after clicking the button
        await expect(page).toHaveURL('http://localhost:8000/admin/settings/data-transfer/exports/create');
        // await page.click('button[type="submit"]');

        await page.click('#export-type .multiselect__select');
        await page.click('li.multiselect__element#null-0');

        // Click the save button
        await page.click('button[type="submit"]');

        // Validate required field messages
        const codeValidation = await page.locator('p.text-red-600:has-text("The Code field is required")').isVisible();
        expect(codeValidation).toBeTruthy();

        console.log('Validated required fields for Shopify Category type');
    })
});