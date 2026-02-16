import { test, expect } from '@playwright/test';

test.use({ storageState: 'storage/auth.json' }); // Reuse login session

test.describe('UnoPim Shopify Plugin Navigation', () => {
    test('should navigate to Shopify credentials page', async ({ page }) => {
        // Go directly to the admin dashboard (User is already logged in)
        await page.goto('/admin/dashboard');

        // Click the Shopify plugin link
        const shopifyLink = page.getByRole('link', { name: /Shopify/i });
        await shopifyLink.click();
        // Verify navigation to the Shopify credentials page
        await expect(page).toHaveURL('http://localhost:8000/admin/shopify/credentials');

        // Verify the Shopify icon and text are visible
        await expect(page.locator('.icon-shopify')).toBeVisible();
        await expect(shopifyLink.locator('text=Shopify')).toBeVisible();
        await expect(page).toHaveTitle(/Shopify/i);

    });
});
