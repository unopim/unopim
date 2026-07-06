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
 * Navigate to the login page. Tests use the unauthenticated `guestPage`
 * fixture, so hitting an admin route redirects here; the logout fallback only
 * runs if a session is unexpectedly active.
 */
async function goToLoginPage(adminPage) {
  await adminPage.goto('/admin/dashboard', { waitUntil: 'domcontentloaded', timeout: 30000 });
  if (!adminPage.url().includes('/admin/login')) {
    await logout(adminPage);
  }

  await expect(adminPage.getByRole('textbox', { name: 'Email Address' })).toBeVisible({ timeout: 15000 });
}

test.describe('Login Page', () => {
  test('Logout Check', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Email Address' }).fill(loginCredentials.email);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill(loginCredentials.password);
    await adminPage.getByRole('button', { name: /sign in/i }).click();
    await adminPage.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 30000 });
    await logout(adminPage);
    await expect(adminPage).toHaveURL(UNOPIM_URL);
  });

  test('shows an error for invalid email and password', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Email Address' }).fill(invalidCredentials.email);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill(invalidCredentials.password);
    await adminPage.getByRole('textbox', { name: 'Password' }).press('Enter');
    await expect(adminPage.getByText(/Please check your credentials and try again\./i).first()).toBeVisible();
  });

  test('shows an error for invalid email and valid password', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Email Address' }).fill(invalidCredentials.email);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill(loginCredentials.password);
    await adminPage.getByRole('textbox', { name: 'Password' }).press('Enter');
    await expect(adminPage.getByText(/Please check your credentials and try again\./i).first()).toBeVisible();
  });

  test('shows an error for valid email and invalid password', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Email Address' }).fill(loginCredentials.email);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill(invalidCredentials.password);
    await adminPage.getByRole('textbox', { name: 'Password' }).press('Enter');
    await expect(adminPage.getByText(/Please check your credentials and try again\./i).first()).toBeVisible();
  });

  test('shows validation errors for empty credentials', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('button', { name: /sign in/i }).click();
    await expect(adminPage.getByText(/The Email Address field is required/i).first()).toBeVisible();
    await expect(adminPage.getByText(/The Password field is required/i).first()).toBeVisible();
  });

  test('shows a validation error when email is empty', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill(loginCredentials.password);
    await adminPage.getByRole('textbox', { name: 'Password' }).press('Enter');
    await expect(adminPage.getByText(/The Email Address field is required/i).first()).toBeVisible();
  });

  test('shows a validation error when password is empty', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Email Address' }).fill(loginCredentials.email);
    await adminPage.getByRole('button', { name: /sign in/i }).click();
    await expect(adminPage.getByText(/The Password field is required/i).first()).toBeVisible();
  });

  test('shows a validation error for a short password', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill('in123');
    await adminPage.getByRole('button', { name: /sign in/i }).click();
    await expect(adminPage.getByText(/The Password field must be at least 6 characters/i).first()).toBeVisible();
  });

  test('toggles password visibility', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill('in123');
    await adminPage.locator('#visibilityIcon').click();
    const inputType = await adminPage.getByRole('textbox', { name: 'Password' }).getAttribute('type');
    expect(inputType).toBe('text');
  });

  test('preserves the email value after a failed login attempt', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Email Address' }).fill(loginCredentials.email);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill(invalidCredentials.password);
    await adminPage.getByRole('textbox', { name: 'Password' }).press('Enter');
    await expect(adminPage.getByText(/Please check your credentials and try again\./i).first()).toBeVisible();

    const emailValue = await adminPage.getByRole('textbox', { name: 'Email Address' }).inputValue();
    expect(emailValue).toBe(loginCredentials.email);
    const passwordValue = await adminPage.getByRole('textbox', { name: 'Password' }).inputValue();
    expect(passwordValue).toBe('');
  });

  test('logs in with valid credentials', async ({ guestPage: adminPage }) => {
    await goToLoginPage(adminPage);
    await adminPage.getByRole('textbox', { name: 'Email Address' }).fill(loginCredentials.email);
    await adminPage.getByRole('textbox', { name: 'Password' }).fill(loginCredentials.password);
    await adminPage.getByRole('textbox', { name: 'Password' }).press('Enter');
    await expect(adminPage).toHaveURL(/\/admin\//);
  });
});
