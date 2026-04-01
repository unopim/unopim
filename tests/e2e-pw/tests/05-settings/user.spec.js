const { test, expect } = require('../../utils/fixtures');
test.describe('UnoPim Test cases', () => {
test('Create User with empty name field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  await adminPage.locator('input[name="timezone"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.keyboard.type('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/The Name field is required/i)).toBeVisible();
});

test('Create User with empty Email field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  await adminPage.locator('input[name="timezone"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.keyboard.type('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/The Email field is required/i)).toBeVisible();
});

test('Create User with empty password field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  await adminPage.locator('input[name="timezone"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.keyboard.type('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/The Password field is required/i)).toBeVisible();
});

test('Create User with empty confirm password field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  await adminPage.locator('input[name="timezone"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.keyboard.type('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/The Password field is required/i)).toBeVisible();
});

test('Create User with different password in confirm password field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  await adminPage.locator('input[name="timezone"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.keyboard.type('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/The Password field confirmation does not match/i)).toBeVisible();
});

test('Create User with empty UI locale field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="timezone"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.keyboard.type('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/The UI Locale field is required/i)).toBeVisible();
});

test('Create User with empty Timezone field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/The Timezone field is required/i)).toBeVisible();
});

test('Create User with empty Role field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  await adminPage.locator('input[name="timezone"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.keyboard.type('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/The Role field is required/i)).toBeVisible();
});

test('Create User', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
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
  await adminPage.locator('input[name="ui_locale_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'English (United States)' }).click();
  await adminPage.locator('input[name="timezone"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.keyboard.type('new_y');
  await adminPage.keyboard.press("Enter");
  await adminPage.locator('input[name="role_id"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/User created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('should allow User search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type('testing@example');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('#app').locator('text=testing@example.com')).toBeVisible();
});

test('Update User', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Testing' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save User' }).click();
  await expect(adminPage.locator('#app').getByText(/User updated successfully/i)).toBeVisible({ timeout: 15000 });
});

test('Delete User', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Testing' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.locator('#app').getByText(/User deleted successfully/i)).toBeVisible({ timeout: 15000 });
});

test('Delete Default User', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  const itemRow = adminPage.locator('#app').locator('div', { hasText: 'admin@example.com' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.click('button:has-text("Delete")');
  await expect(adminPage.locator('#app').getByText(/Last User delete failed/i)).toBeVisible();
});
});
