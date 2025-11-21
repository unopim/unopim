const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Test cases', () => {
test('Create User with empty name field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await adminPage.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/The Name field is required/i)).toBeVisible();
});

test('Create User with empty Email field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await adminPage.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/The Email field is required/i)).toBeVisible();
});

test('Create User with empty password field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await adminPage.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/The Password field is required/i)).toBeVisible();
});

test('Create User with empty confirm password field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await adminPage.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/The Password field is required/i)).toBeVisible();
});

test('Create User with different password in confirm password field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('testing456');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await adminPage.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/The Password field confirmation does not match/i)).toBeVisible();
});

test('Create User with empty UI locale field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await adminPage.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/The UI Locale field is required/i)).toBeVisible();
});

test('Create User with empty Timezone field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('testing456');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/The Timezone field is required/i)).toBeVisible();
});

test('Create User with empty Role field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('testing456');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await adminPage.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/The Role field is required/i)).toBeVisible();
});

test('Create User', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('button', { name: 'Create User' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).click();
  await adminPage.getByRole('textbox', { name: 'Name' }).fill('Testing');
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
  await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill('testing@example.com');
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Password', exact: true }).fill('testing123');
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Confirm Password' }).fill('testing123');
  await adminPage.locator('div').filter({ hasText: /^UI Locale$/ }).click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^Timezone$/ }).click();
  await adminPage.getByRole('textbox', { name: 'timezone-searchbox' }).fill('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('div').filter({ hasText: /^Role$/ }).nth(1).click();
  await adminPage.getByRole('option', { name: 'Administrator' }).locator('span').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/User created successfully/i)).toBeVisible();
});

test('should allow User search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('testing@example');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=testing@example.com', {exact:true})).toBeVisible();
});

test('Update User', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Testing' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.getByText(/User updated successfully/i)).toBeVisible();
});

test('Delete User', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Testing' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.getByText(/User deleted successfully/i)).toBeVisible();
});

test('Delete Default User', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Example' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.getByText(/Last User delete failed/i)).toBeVisible();
});
});

