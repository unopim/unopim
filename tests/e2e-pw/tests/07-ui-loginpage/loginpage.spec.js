const { test, expect } = require('../../utils/fixtures');
const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const UNOPIM_URL = `${BASE_URL}/admin/login`;
const loginCredentials = {
  email: process.env.ADMIN_USERNAME || process.env.ADMIN_EMAIL || 'admin@example.com',
  password: process.env.ADMIN_PASSWORD || 'admin123',
};
const invalidCredentials = {
  email: 'admin123@example.com',
  password: 'admintest',
};

/**
 * Submit the logout form directly, bypassing the Vue dropdown.
 * The logout link uses onclick="document.getElementById('adminLogout').submit()"
 * which is unreliable via click() because the dropdown requires Vue to toggle isActive first.
 */
async function logout(adminPage) {
  await adminPage.evaluate(() => {
    const form = document.getElementById('adminLogout');
    if (form) form.submit();
  });
  await adminPage.waitForURL(/admin\/login/, { timeout: 15000 });
}

/**
 * Helper: Log out and navigate to login page.
 * Each test starts logged in (from fixture), so we must log out first for login-page tests.
 */
async function goToLoginPage(adminPage) {
  await adminPage.goto('/admin/dashboard', { waitUntil: 'domcontentloaded', timeout: 30000 });
  if (!adminPage.url().includes('/admin/login')) {
    await logout(adminPage);
  }

  await expect(adminPage.getByLabel(/Email/i)).toBeVisible({ timeout: 15000 });
}


test.describe('Login Page', () => {

test('Logout Check', async ({ adminPage }) => {
  await adminPage.goto('/admin/dashboard', { waitUntil: 'load', timeout: 30000 });
  await logout(adminPage);
  await expect(adminPage).toHaveURL(UNOPIM_URL);
});

test('shows an error for invalid email and password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Email/i).fill(invalidCredentials.email);
  await adminPage.getByLabel(/Password/i).fill(invalidCredentials.password);
  await adminPage.getByLabel(/Password/i).press('Enter');
  await expect(adminPage.getByText(/Please check your credentials and try again\./i).first()).toBeVisible();
});

test('shows an error for invalid email and valid password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Email/i).fill(invalidCredentials.email);
  await adminPage.getByLabel(/Password/i).fill(loginCredentials.password);
  await adminPage.getByLabel(/Password/i).press('Enter');
  await expect(adminPage.getByText(/Please check your credentials and try again\./i).first()).toBeVisible();
});

test('shows an error for valid email and invalid password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Email/i).fill(loginCredentials.email);
  await adminPage.getByLabel(/Password/i).fill(invalidCredentials.password);
  await adminPage.getByLabel(/Password/i).press('Enter');
  await expect(adminPage.getByText(/Please check your credentials and try again\./i).first()).toBeVisible();
});

test('shows validation errors for empty credentials', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByRole('button', { name: /sign in/i }).click();
  await expect(adminPage.getByText(/The Email Address field is required/i).first()).toBeVisible();
  await expect(adminPage.getByText(/The Password field is required/i).first()).toBeVisible();
});

test('shows a validation error when email is empty', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Password/i).fill(loginCredentials.password);
  await adminPage.getByLabel(/Password/i).press('Enter');
  await expect(adminPage.getByText(/The Email Address field is required/i).first()).toBeVisible();
});

test('shows a validation error when password is empty', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Email/i).fill(loginCredentials.email);
  await adminPage.getByRole('button', { name: /sign in/i }).click();
  await expect(adminPage.getByText(/The Password field is required/i).first()).toBeVisible();
});

test('shows a validation error for a short password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Password/i).fill('in123');
  await adminPage.getByRole('button', { name: /sign in/i }).click();
  await expect(adminPage.getByText(/The Password field must be at least 6 characters/i).first()).toBeVisible();
});

test('toggles password visibility', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Password/i).fill('in123');
  await adminPage.locator('#visibilityIcon').click();
  const inputType = await adminPage.getByLabel(/Password/i).getAttribute('type');
  expect(inputType).toBe('text');
});

test('preserves the email value after a failed login attempt', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Email/i).fill(loginCredentials.email);
  await adminPage.getByLabel(/Password/i).fill(invalidCredentials.password);
  await adminPage.getByLabel(/Password/i).press('Enter');
  await expect(adminPage.getByText(/Please check your credentials and try again\./i).first()).toBeVisible();

  const emailValue = await adminPage.getByLabel(/Email/i).inputValue();
  expect(emailValue).toBe(loginCredentials.email);
  const passwordValue = await adminPage.getByLabel(/Password/i).inputValue();
  expect(passwordValue).toBe('');
});

test('logs in with valid credentials', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.getByLabel(/Email/i).fill(loginCredentials.email);
  await adminPage.getByLabel(/Password/i).fill(loginCredentials.password);
  await adminPage.getByLabel(/Password/i).press('Enter');
  await expect(adminPage).toHaveURL(/\/admin\//);
});
});
