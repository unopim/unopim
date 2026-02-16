import { test, expect } from '@playwright/test';

test.use({ storageState: 'storage/auth.json' }); // Reuse login session
// test.use({ launchOptions: { slowMo: 1000 } }); // Slow down actions by 1 second


test.describe('UnoPim Shopify setting tab Navigation', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to the Shopify Credentials Page
        await page.goto('admin/shopify/credentials');
        await page.getByRole('link', { name: 'Settings', exact: true }).click()
    });
    test('Verify page loads correctly', async ({ page }) => {
        await expect(page).toHaveURL('http://localhost:8000/admin/shopify/export/settings/2');
    });

    test('Toggle Named Tags Export setting', async ({ page }) => {
        const toggle = await page.locator('.relative > .rounded-full').first();
        await toggle.check();
        await expect(toggle).toBeChecked();
        await toggle.uncheck();
        await expect(toggle).not.toBeChecked();
    });

    test('Toggle Attribute Name in Tags Export setting', async ({ page }) => {
        const toggle = await page.locator('div:nth-child(3) > .relative > .rounded-full');
        await toggle.check();
        await expect(toggle).toBeChecked();
        await toggle.uncheck();
        await expect(toggle).not.toBeChecked();
    });


    test('Enable toggle and select option from dependent dropdown', async ({ page }) => {

        // Toggle: "Do you want to pull through the attribute name as well in tags?"
        const toggle = await page.locator('div:nth-child(3) > .relative > .rounded-full');

        // Enable the toggle if not already enabled
        if (!(await toggle.isChecked())) {
            await toggle.click();
        }

        // Wait for the dropdown to be visible
        const dropdown = page.locator('#tagSeprator .multiselect__select');
        await expect(dropdown).toBeVisible();

        // Click the dropdown to reveal options
        await dropdown.click();

        // Get all options in the dropdown
        const options = page.locator('#tagSeprator .multiselect__content li');
        const optionsCount = await options.count();
        expect(optionsCount).toBeGreaterThan(0);

        console.log('Available options:');
        for (let i = 0; i < optionsCount; i++) {
            const text = await options.nth(i).textContent();
            console.log(`- ${text}`);
        }

        // Select the desired option, e.g., "( ) Space"
        const optionToSelect = page.locator('#tagSeprator .multiselect__option', { hasText: '( ) Space' });
        await optionToSelect.click();

        // Verify the selected option
        const selected = await page.locator('#tagSeprator .multiselect__single').textContent();
        expect(selected).toBe('( ) Space');
        console.log(`Selected option: ${selected}`);
    });

    test('Verify dropdown options and select one for both fields', async ({ page }) => {
        // Function to verify and select dropdown options
        const verifyAndSelectDropdown = async (dropdownId, optionText) => {
            const dropdown = page.locator(`${dropdownId} .multiselect__select`);
            await dropdown.click();

            const options = page.locator(`${dropdownId} .multiselect__content li`);
            const optionsCount = await options.count();
            expect(optionsCount).toBeGreaterThan(0);

            console.log(`Options for ${dropdownId}:`);
            for (let i = 0; i < optionsCount; i++) {
                const text = await options.nth(i).textContent();
                console.log(`- ${text}`);
            }

            const optionToSelect = page.locator(`${dropdownId} .multiselect__option`, { hasText: optionText });
            await optionToSelect.click();

            const selected = await page.locator(`${dropdownId} .multiselect__single`).textContent();
            expect(selected).toBe(optionText);
            console.log(`Selected option for ${dropdownId}: ${selected}`);
        };
    });


    test('Toggle value of option name in other setting', async ({ page }) => {
        const toggle = await page.locator('div:nth-child(3) .relative .rounded-full');
        await toggle.check();
        await expect(toggle).toBeChecked();
        await toggle.uncheck();
        await expect(toggle).not.toBeChecked();
    });

    test('Back button should navigate to credentials page', async ({ page }) => {
        await page.getByRole('link', { name: 'Back' }).click();
        await expect(page).toHaveURL(/credentials/);
    });

});

