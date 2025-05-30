import { test, expect } from '@playwright/test';
test.describe('UnoPim Test cases', () => {

  // Before each test, launch browser and navigate to the login page
  test.beforeEach(async ({ page }) => {

    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
  });

test('Create User with empty name field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await page.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await page.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await page.getByRole('option', { name: 'America/New_York (-04:00)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await page.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/The Name field is required/i)).toBeVisible();
});

test('Create User with empty Email field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await page.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await page.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await page.getByRole('option', { name: 'America/New_York (-04:00)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await page.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/The Email field is required/i)).toBeVisible();
});

test('Create User with empty password field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await page.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await page.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await page.getByRole('option', { name: 'America/New_York (-04:00)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await page.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/The Password field is required/i)).toBeVisible();
});

test('Create User with empty confirm password field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('');
  await page.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await page.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await page.getByRole('option', { name: 'America/New_York (-04:00)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await page.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/The Password field is required/i)).toBeVisible();
});

test('Create User with different password in confirm password field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('testing456');
  await page.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await page.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await page.getByRole('option', { name: 'America/New_York (-04:00)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await page.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/The Password field confirmation does not match/i)).toBeVisible();
});

test('Create User with empty UI locale field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await page.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await page.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await page.getByRole('option', { name: 'America/New_York (-04:00)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await page.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/The UI Locale field is required/i)).toBeVisible();
});

test('Create User with empty Timezone field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('testing456');
  await page.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await page.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/The Timezone field is required/i)).toBeVisible();
});

test('Create User with empty Role field', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('testing456');
  await page.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await page.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await page.getByRole('option', { name: 'America/New_York (-04:00)' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/The Role field is required/i)).toBeVisible();
});

test('Create User', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await page.getByRole('button', { name: 'Create User' }).click();
  await page.getByRole('textbox', { name: 'Name' }).click();
  await page.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await page.getByRole('textbox', { name: 'email@example.com' }).click();
  await page.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await page.getByRole('textbox', { name: 'Password', exact: true }).click();
  await page.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await page.getByRole('textbox', { name: 'Confirm Password' }).click();
  await page.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await page.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await page.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await page.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await page.getByRole('option', { name: 'America/New_York (-04:00)' }).locator('span').first().click();
  await page.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await page.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/User created successfully/i)).toBeVisible();
});

test('should allow User search', async ({ page }) => {
    await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
    await page.getByRole('textbox', { name: 'Search' }).click();
    await page.getByRole('textbox', { name: 'Search' }).type('testing@example.com');
    await page.keyboard.press('Enter');
    await expect(page.locator('text=testing@example.com')).toBeVisible();
  });

test('Update User', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  const itemRow = page.locator('div', { hasText: 'Testing' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await page.locator('.relative > .rounded-full').click();
  await page.getByRole('button', { name: 'Save User' }).click();
  await expect(page.getByText(/User updated successfully/i)).toBeVisible();
});

test('Delete User', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  const itemRow = page.locator('div', { hasText: 'Testing' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.click('button:has-text("Delete")');
  await expect(page.getByText(/User deleted successfully/i)).toBeVisible();
});

test('Delete Default User', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  const itemRow = page.locator('div', { hasText: 'John Doe' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await page.click('button:has-text("Delete")');
  await expect(page.getByText(/Last User delete failed/i)).toBeVisible();
});
});