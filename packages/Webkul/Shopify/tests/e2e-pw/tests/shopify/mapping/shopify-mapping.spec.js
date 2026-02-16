import { test, expect } from '@playwright/test';

test.use({ storageState: 'storage/auth.json' }); // Reuse login session

// Define all mapping elements from Mapping page.
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

test.describe('UnoPim Shopify mapping tab Navigation', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to the Shopify Credentials Page
        await page.goto('admin/shopify/credentials');
        await page.getByRole('link', { name: 'Export Mappings' }).click();
        await expect(page.url()).toMatch(/\/admin\/shopify\/export\/mapping\/[0-9]+$/);
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
      await expect(page.locator('#app')).toContainText('The Unit Weight field is required');
      await expect(page.locator('#app')).toContainText('The Unit Volume field is required');
      await expect(page.locator('#app')).toContainText('The Unit Dimension field is required');
      await page.locator('div').filter({ hasText: /^Unit Weight$/ }).click();
      await page.getByText('kg', { exact: true }).click();
      await page.locator('div').filter({ hasText: /^Unit Volume$/ }).click();
      await page.getByText('L', { exact: true }).click();
      await page.locator('div').filter({ hasText: /^Unit Dimension$/ }).click();
      await page.getByText('cm', { exact: true }).click();
      await page.getByRole('button', { name: 'Save' }).click();
      await expect(page.getByText('Mapping saved successfully')).toBeVisible();

    });

    test('should navigate to Shopify mapping page and fill export mapping form', async ({ page }) => {
        await expect(page.getByRole('link', { name: 'General' })).toBeVisible();
        await expect(page.locator('#app')).toContainText('General');
        await expect(page.getByRole('paragraph').filter({ hasText: 'Export Mappings' })).toBeVisible();
        await expect(page.locator('#app')).toContainText('Export Mappings');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Export Mapping saved successfully')).toBeVisible();
        await expect(page.locator('#app')).toContainText('Export Mapping saved successfully');
        await page.locator('div').filter({ hasText: /^Name$/ }).click();
        await page.getByText('Name', { exact: true }).click();
        await page.getByText('Description', { exact: true }).click();
        await page.locator('div').filter({ hasText: /^Price$/ }).click();
        await page.locator('#default_productType').click();
        await page.locator('#default_productType').clear();
        await page.locator('#default_productType').fill('unopim');
        await page.locator('#default_productType').click();
        await page.locator('#default_tags').click();
        await page.locator('#default_tags').fill('shopify');
        const mediaTypeDropdown = page.locator('#mediaType .multiselect__select');
        await mediaTypeDropdown.click();
        await page.getByText('Gallery', { exact: true }).click();
        const pLocator = page.locator('p', { hasText: 'Media Attributes' });
        const multiselect = pLocator.locator('..').locator('.multiselect');
        await expect(multiselect).toBeVisible();
        const hasDisabledClass = await multiselect.evaluate(el => el.classList.contains('multiselect--disabled'));
        expect(hasDisabledClass).toBe(false);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('#app')).toContainText('Export Mapping saved successfully');
        await page.getByRole('link', { name: 'Back' }).click();
        await page.getByRole('link', { name: 'Export Mappings' }).click();
    });
});