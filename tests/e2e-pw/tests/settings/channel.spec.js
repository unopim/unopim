import { test, expect } from '@playwright/test';
test.describe('UnoPim Channel test', () => {
test.beforeEach(async ({ page }) => {
  await page.goto('/admin/dashboard');
});

test('Create Channel with empty Code', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await page.getByRole('link', { name: 'Create Channel' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('div').filter({ hasText: /^Select Root Category$/ }).click();
  await page.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await page.locator('div').filter({ hasText: /^Select Locales$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Select currencies$/ }).click();
  await page.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Channel' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
});

test('Create Channel with empty Root Category', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await page.getByRole('link', { name: 'Create Channel' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await page.locator('div').filter({ hasText: /^Select Locales$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Select currencies$/ }).click();
  await page.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Channel' }).click();
  await expect(page.getByText('The Root Category field is required')).toBeVisible();
});

test('Create Channel with empty Locales field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await page.getByRole('link', { name: 'Create Channel' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await page.locator('div').filter({ hasText: /^Select Root Category$/ }).click();
  await page.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await page.locator('div').filter({ hasText: /^Select currencies$/ }).click();
  await page.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Channel' }).click();
  await expect(page.getByText('The Locales field is required')).toBeVisible();
});

test('Create Channel with empty Currency field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await page.getByRole('link', { name: 'Create Channel' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await page.locator('div').filter({ hasText: /^Select Root Category$/ }).click();
  await page.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await page.locator('div').filter({ hasText: /^Select Locales$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Channel' }).click();
  await expect(page.getByText('The Currencies field is required')).toBeVisible();
});

test('Create Channel with all required field empty', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await page.getByRole('link', { name: 'Create Channel' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await page.getByRole('button', { name: 'Save Channel' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
  await expect(page.getByText('The Root Category field is required')).toBeVisible();
  await expect(page.getByText('The Locales field is required')).toBeVisible();
  await expect(page.getByText('The Currencies field is required')).toBeVisible();
});

test('Create Channel', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await page.getByRole('link', { name: 'Create Channel' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await page.locator('div').filter({ hasText: /^Select Root Category$/ }).click();
  await page.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await page.locator('div').filter({ hasText: /^Select Locales$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Select currencies$/ }).click();
  await page.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Channel' }).click();
  await expect(page.getByText(/Channel created successfully/i)).toBeVisible();
});

test('Create Channel with same Code', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await page.getByRole('link', { name: 'Create Channel' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('eCommerce');
  await page.locator('div').filter({ hasText: /^Select Root Category$/ }).click();
  await page.getByRole('option', { name: '[root]' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('E-Commerce');
  await page.locator('div').filter({ hasText: /^Select Locales$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Select currencies$/ }).click();
  await page.getByRole('option', { name: 'US Dollar' }).locator('span').first().click();
  await page.getByRole('button', { name: 'Save Channel' }).click();
  await expect(page.getByText('The Code has already been taken.')).toBeVisible();
});

test('should allow Channel search', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('eCommerce');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=E-Commerce')).toBeVisible();
});

test('Update Channel', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  const itemRow = page.locator('div', { hasText: 'eCommerceE-Commerce' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Mobile');
  await page.getByRole('button', { name: 'Save Channel' }).click();
  await expect(page.getByText(/Update Channel Successfully/i)).toBeVisible();
});

test('Delete Channel', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  const itemRow = page.locator('div', { hasText: 'eCommerceMobile' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Channel deleted successfully/i)).toBeVisible();
});

test('Delete Default Channel', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  const itemRow = page.locator('div', { hasText: '[root]' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/You can't delete the channel "default" because your PIM needs to have at least one channel./i)).toBeVisible();
});
});

