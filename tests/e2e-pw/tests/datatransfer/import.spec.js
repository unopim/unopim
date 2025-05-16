import { test, expect } from '@playwright/test';
test.describe('UnoPim Import Jobs', () => {

    // Before each test, launch browser and navigate to the login page
    test.beforeEach(async ({ page }) => {

        await page.goto('http://127.0.0.1:8000/admin/login');
        await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
        await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
        await page.getByRole('button', { name: 'Sign In' }).click();
    });

    test('Create WooCommerce Category Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        await page.getByRole('link', { name: 'Create Import' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).fill('WooCommerce Category Import');
        await page.locator('div').filter({ hasText: /^Categories$/ }).click();
        await page.getByRole('option', { name: 'WooCommerce Category Import' }).locator('span').first().click();
        await page.locator('input[name="filters[credential]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'http://192.168.15.238/' }).locator('span').first().click();
        await page.locator('input[name="filters[locale]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'English (United States) Press' }).locator('span').first().click();
        await page.getByRole('button', { name: 'Save Import' }).click();
        await expect(page.getByText(/Import created successfully/i)).toBeVisible();
    });

    test('Delete WooCommerce category Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        const itemRow = await page.locator('div', { hasText: 'WooCommerce Category Import' });
        await itemRow.locator('span[title="Delete"]').first().click();        
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(/Import deleted successfully/i)).toBeVisible();
      });


    test('Create WooCommerce Attribute Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        await page.getByRole('link', { name: 'Create Import' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).fill('WooCommerce Attribute Import');
        await page.locator('div').filter({ hasText: /^Categories$/ }).click();
        await page.getByRole('option', { name: 'WooCommerce Attribute Import' }).locator('span').first().click();
        await page.locator('input[name="filters[credential]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'http://192.168.15.238/' }).locator('span').first().click();
        await page.locator('input[name="filters[locale]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
        await page.getByRole('button', { name: 'Save Import' }).click();
        await expect(page.getByText(/Import created successfully/i)).toBeVisible();
    });


    test('Delete WooCommerce Attribute Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        const itemRow = await page.locator('div', { hasText: 'WooCommerce Attribute Import' });
        await itemRow.locator('span[title="Delete"]').first().click();        
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(/Import deleted successfully/i)).toBeVisible();
      });


    test('Create WooCommerce Product Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        await page.getByRole('link', { name: 'Create Import' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).fill('WooCommerce Product Import');
        await page.locator('div').filter({ hasText: /^Categories$/ }).click();
        await page.getByRole('option', { name: 'WooCommerce Product Import' }).locator('span').first().click();
        await page.locator('input[name="filters[credential]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'http://192.168.15.238/' }).locator('span').first().click();
        await page.locator('input[name="filters[channel]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
        await page.locator('input[name="filters[locale]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
        await page.locator('input[name="filters[currency]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'Indian Rupee' }).locator('span').first().click();
        await page.locator('input[name="filters[family]"]').locator('..').locator('.multiselect__placeholder').click();
        await page.getByRole('option', { name: 'Default' }).locator('span').first().click();
        await page.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
        await page.getByRole('button', { name: 'Save Import' }).click();
        await expect(page.getByText(/Import created successfully/i)).toBeVisible();
    });


    test('Delete WooCommerce Product Import', async ({ page }) => {
        await page.getByRole('link', { name: ' Data Transfer' }).click();
        await page.getByRole('link', { name: 'Imports' }).click();
        const itemRow = await page.locator('div', { hasText: 'WooCommerce Product Import' });
        await itemRow.locator('span[title="Delete"]').first().click();        
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(/Import deleted successfully/i)).toBeVisible();
      });
});