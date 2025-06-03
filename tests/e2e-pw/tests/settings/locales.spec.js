import { test, expect } from '@playwright/test';
test.describe('UnoPim Test cases', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto('http://127.0.0.1:8000/admin/login');
        await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
        await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
        await page.getByRole('button', { name: 'Sign In' }).click();
    });

    test('Delete Locale', async ({ page }) => {
        await page.getByRole('link', { name: ' Settings' }).click();
        await page.getByRole('link', { name: 'Locales' }).click();
        await page.getByRole('textbox', { name: 'Search by code' }).click();
        await page.getByRole('textbox', { name: 'Search by code' }).type('af_ZA');
        await page.keyboard.press('Enter');
        const itemRow = page.locator('div', { hasText: 'af_ZAAfrikaans (South Africa)' });
        await itemRow.locator('span[title="Delete"]').first().click();
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(/Locale deleted successfully/i)).toBeVisible();
    });

    test('Create locale with empty Code field', async ({ page }) => {
        await page.getByRole('link', { name: ' Settings' }).click();
        await page.getByRole('link', { name: 'Locales' }).click();
        await page.getByRole('button', { name: 'Create Locale' }).click();
        await page.getByRole('textbox', { name: 'Code', exact: true }).fill('');
        await page.locator('label[for="status"]').click();
        await page.getByRole('button', { name: 'Save Locale' }).click();
        await expect(page.getByText(/The Code field is required/i)).toBeVisible();
    });

    test('Create locale with existing Code value', async ({ page }) => {
        await page.getByRole('link', { name: ' Settings' }).click();
        await page.getByRole('link', { name: 'Locales' }).click();
        await page.getByRole('button', { name: 'Create Locale' }).click();
        await page.getByRole('textbox', { name: 'Code', exact: true }).fill('en_US');
        await page.locator('label[for="status"]').click();
        await page.getByRole('button', { name: 'Save Locale' }).click();
        await expect(page.getByText(/The code has already been taken./i)).toBeVisible();
    });

    test('Create locale', async ({ page }) => {
        await page.getByRole('link', { name: ' Settings' }).click();
        await page.getByRole('link', { name: 'Locales' }).click();
        await page.getByRole('button', { name: 'Create Locale' }).click();
        await page.getByRole('textbox', { name: 'Code', exact: true }).fill('af_ZA');
        await page.locator('label[for="status"]').click();
        await page.getByRole('button', { name: 'Save Locale' }).click();
        await expect(page.getByText(/Locale created successfully/i)).toBeVisible();
    });

    test('Update Locale ', async ({ page }) => {
        await page.getByRole('link', { name: ' Settings' }).click();
        await page.getByRole('link', { name: 'Locales' }).click();
        await page.getByRole('textbox', { name: 'Search by code' }).click();
        await page.getByRole('textbox', { name: 'Search by code' }).type('af_ZA');
        await page.keyboard.press('Enter');
        const itemRow = page.locator('div', { hasText: 'af_ZAAfrikaans (South Africa)' });
        await itemRow.locator('span[title="Edit"]').first().click();
        await page.locator('label[for="status"]').click();
        await page.getByRole('button', { name: 'Save Locale' }).click();
        await expect(page.getByText(/Locale Updated successfully/i)).toBeVisible();
    });

    test('Delete Enable Locale', async ({ page }) => {
        await page.getByRole('link', { name: ' Settings' }).click();
        await page.getByRole('link', { name: 'Locales' }).click();
        const itemRow = page.locator('div', { hasText: 'Enabled' });
        await itemRow.locator('span[title="Delete"]').first().click();
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(/You cannot delete a locale linked to a channel or user/i)).toBeVisible();
    });
});
