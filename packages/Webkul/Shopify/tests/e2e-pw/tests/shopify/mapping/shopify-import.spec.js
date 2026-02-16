import { test, expect } from '@playwright/test';

test.use({ storageState: 'storage/auth.json' });

const mappingElements = [
    { field: 'Name [title]', inputName: 'title', placeholder: 'Name' },
    { field: 'Description [descriptionHtml]', inputName: 'descriptionHtml', placeholder: 'Description' },
    { field: 'Price [price]', inputName: 'price', placeholder: 'Price' },
    { field: 'Weight [weight]', inputName: 'weight', placeholder: 'Weight' },
    { field: 'Quantity [inventoryQuantity]', inputName: 'inventoryQuantity', placeholder: 'Quantity' },
    { field: 'Inventory Tracked [inventoryTracked]', inputName: 'inventoryTracked', placeholder: 'Inventory Tracked' },
    { field: 'Allow Purchase Out of Stock [inventoryPolicy]', inputName: 'inventoryPolicy', placeholder: 'Allow Purchase Out of Stock' },
    { field: 'Vendor [vendor]', inputName: 'vendor', placeholder: 'Vendor' },
    { field: 'Product Type [productType]', inputName: 'productType', placeholder: 'Product Type' },
    { field: 'Tags [tags]', inputName: 'tags', placeholder: 'Tags' },
    { field: 'Barcode [barcode]', inputName: 'barcode', placeholder: 'Barcode' },
    { field: 'Compare Price [compareAtPrice]', inputName: 'compareAtPrice', placeholder: 'Compare Price' },
    { field: 'Seo Title [metafields_global_title_tag]', inputName: 'metafields_global_title_tag', placeholder: 'Seo Title' },
    { field: 'Seo Description [metafields_global_description_tag]', inputName: 'metafields_global_description_tag', placeholder: 'Seo Description' },
    { field: 'Handle [handle]', inputName: 'handle', placeholder: 'Handle' },
    { field: 'Taxable [taxable]', inputName: 'taxable', placeholder: 'Taxable' },
    { field: 'Cost per item [cost]', inputName: 'cost', placeholder: 'Cost per item' }
];

test.describe('UnoPim Shopify import mapping tab Navigation', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to the Shopify Credentials Page
        await page.goto('admin/shopify/credentials');
        await page.getByRole('link', { name: 'Import Mappings' }).click();
        await expect(page.url()).toMatch(/\/admin\/shopify\/import\/mapping\/[0-9]+$/);
    });

    // Playwright test to map fields


    test('Map Shopify Fields', async ({ page }) => {

        for (const element of mappingElements) {
            console.log(`Mapping ${element.field}`);

            const input = page.locator(`input[name="${element.inputName}"]`);
        }

        const saveButton = page.getByRole('button', { name: 'Save' });
        await saveButton.click();
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('#app')).toContainText('The Choose Family field is required');
        await page.locator('div').filter({ hasText: /^Choose Family$/ }).click();
        await page.getByText('Default', { exact: true }).click();
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Mapping saved successfully')).toBeVisible();

    });

    test('should navigate to Shopify Import mapping page and fill import mapping form', async ({ page }) => {
        await expect(page.getByRole('link', { name: 'General' })).toBeVisible();
        await expect(page.locator('#app')).toContainText('General');
        await expect(page.getByRole('paragraph').filter({ hasText: 'Import Mappings' })).toBeVisible();
        await expect(page.locator('#app')).toContainText('Import Mappings');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Import Mapping saved successfully')).toBeVisible();
        await expect(page.locator('#app')).toContainText('Import Mapping saved successfully');
        await page.locator('div').filter({ hasText: /^Name$/ }).click();
        await page.getByText('Name', { exact: true }).click();
        await page.getByText('Description', { exact: true }).click();
        await page.locator('div').filter({ hasText: /^Price$/ }).click();
        await page.getByText('Price', { exact: true }).click();
        const mediaTypeDropdown = page.locator('#type .multiselect__select');
        await mediaTypeDropdown.click();
        await page.getByText('Gallery', { exact: true }).click();
        const pLocator = page.locator('p', { hasText: 'Media Attributes' });
        const multiselect = pLocator.locator('..').locator('.multiselect');
        await expect(multiselect).toBeVisible();
        const hasDisabledClass = await multiselect.evaluate(el => el.classList.contains('multiselect--disabled'));
        expect(hasDisabledClass).toBe(false);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('#app')).toContainText('Import Mapping saved successfully');
        await page.getByRole('link', { name: 'Back' }).click();
        await page.getByRole('link', { name: 'Import Mappings' }).click();
    });
});
