import { test, expect } from '@playwright/test';
test.describe('UnoPim Import Jobs', () => {

    // Before each test, launch browser and navigate to the login page
    test.beforeEach(async ({ page }) => {

        await page.goto('http://127.0.0.1:8000/admin/login');
        await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
        await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
        await page.getByRole('button', { name: 'Sign In' }).click();
    });

    test('Create Product Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        await page.getByRole('link', { name: 'Create Import' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).fill('product Import');
        await page.locator('div').filter({ hasText: /^Categories$/ }).click();
        await page.getByRole('option', { name: 'Products' }).locator('span').first().click();
        const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
        await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1000products.csv');
        await page.getByRole('button', { name: 'Save Import' }).click();
        await expect(page.getByText(/Import created successfully/i)).toBeVisible();
    });

    test('Delete Product Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        const itemRow = await page.locator('div', { hasText: 'Product Import' });
        await itemRow.locator('span[title="Delete"]').first().click();        
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(/Import deleted successfully/i)).toBeVisible();
      });



    test('Create Category Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        await page.getByRole('link', { name: 'Create Import' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).fill('Category Import');
        const fileInput = page.locator('input[type="file"][id="36_dropzone-file"]');
        await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/1000products.csv');
        await page.getByRole('button', { name: 'Save Import' }).click();
        await expect(page.getByText(/Import created successfully/i)).toBeVisible();
    });


    test('Delete Category Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        const itemRow = await page.locator('div', { hasText: 'Category Import' });
        await itemRow.locator('span[title="Delete"]').first().click();        
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(/Import deleted successfully/i)).toBeVisible();
      });

});


