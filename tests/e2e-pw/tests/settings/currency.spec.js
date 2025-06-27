import { test, expect } from '@playwright/test';
test.describe('UnoPim Test cases', () => {
test.beforeEach(async ({ page }) => {
   await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
});

test('Create Currency with empty Code field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  await page.getByRole('button', { name: 'Create Currency' }).click();
  await page.getByRole('textbox', { name: 'Code', exact: true }).click();
  await page.getByRole('textbox', { name: 'Code', exact: true }).fill('');
  await page.getByRole('textbox', { name: 'Symbol' }).click();
  await page.getByRole('textbox', { name: 'Symbol' }).fill('₫');
  await page.getByRole('textbox', { name: 'Decimal' }).click();
  await page.getByRole('textbox', { name: 'Decimal' }).fill('2');
  await page.locator('label[for="status"]').click();
  await page.getByRole('button', { name: 'Save Currency' }).click();
  await expect(page.getByText(/The Code field is required/i)).toBeVisible();
});

test('Create Currency with Code less than 3 character', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  await page.getByRole('button', { name: 'Create Currency' }).click();
  await page.getByRole('textbox', { name: 'Code', exact: true }).click();
  await page.getByRole('textbox', { name: 'Code', exact: true }).fill('gh');
  await page.getByRole('textbox', { name: 'Symbol' }).click();
  await page.getByRole('textbox', { name: 'Symbol' }).fill('₫');
  await page.getByRole('textbox', { name: 'Decimal' }).click();
  await page.getByRole('textbox', { name: 'Decimal' }).fill('2');
  await page.locator('label[for="status"]').click();
  await page.getByRole('button', { name: 'Save Currency' }).click();
  await expect(page.getByText(/The code must be at least 3 characters./i)).toBeVisible();
});

test('Create Currency with Code more than 3 character', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  await page.getByRole('button', { name: 'Create Currency' }).click();
  await page.getByRole('textbox', { name: 'Code', exact: true }).click();
  await page.getByRole('textbox', { name: 'Code', exact: true }).fill('ghdn');
  await page.getByRole('textbox', { name: 'Symbol' }).click();
  await page.getByRole('textbox', { name: 'Symbol' }).fill('₫');
  await page.getByRole('textbox', { name: 'Decimal' }).click();
  await page.getByRole('textbox', { name: 'Decimal' }).fill('2');
  await page.locator('label[for="status"]').click();
  await page.getByRole('button', { name: 'Save Currency' }).click();
  await expect(page.getByText(/The code may not be greater than 3 characters./i)).toBeVisible();
});

test('Create Currency ', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  await page.getByRole('button', { name: 'Create Currency' }).click();
  await page.getByRole('textbox', { name: 'Code', exact: true }).click();
  await page.getByRole('textbox', { name: 'Code', exact: true }).fill('VND');
  await page.getByRole('textbox', { name: 'Symbol' }).click();
  await page.getByRole('textbox', { name: 'Symbol' }).fill('₫');
  await page.getByRole('textbox', { name: 'Decimal' }).click();
  await page.getByRole('textbox', { name: 'Decimal' }).fill('2');
  await page.locator('label[for="status"]').click();
  await page.getByRole('button', { name: 'Save Currency' }).click();
  await expect(page.getByText(/Currency created successfully/i)).toBeVisible();
});

test('should allow Currency search', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  await page.getByRole('textbox', { name: 'Search by code or id' }).click();
  await page.getByRole('textbox', { name: 'Search by code or id' }).type('VND');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=VND')).toBeVisible();
});

test('Update Currency ', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  await page.getByRole('textbox', { name: 'Search by code or id' }).click();
  await page.getByRole('textbox', { name: 'Search by code or id' }).type('vnd');
  await page.keyboard.press('Enter');
  const itemRow = page.locator('div', { hasText: 'VNDVietnamese Dong' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await page.getByRole('textbox', { name: 'Decimal' }).click();
  await page.getByRole('textbox', { name: 'Decimal' }).fill('5');
  await page.locator('label[for="status"]').click();
  await page.getByRole('button', { name: 'Save Currency' }).click();
  await expect(page.getByText(/Currency updated successfully/i)).toBeVisible();
});

test('Delete Currency ', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  await page.getByRole('textbox', { name: 'Search by code or id' }).click();
  await page.getByRole('textbox', { name: 'Search by code or id' }).type('vnd');
  await page.keyboard.press('Enter');
  const itemRow = page.locator('div', { hasText: 'VNDVietnamese Dong' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Currency deleted successfully/i)).toBeVisible();
});

test('Delete Enable Currency ', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  const itemRow = page.locator('div', { hasText: 'Enabled' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/You cannot delete a currency linked to a channel/i)).toBeVisible();
});
});

