const { test, expect } = require('../../utils/fixtures');
const path = require('path');
const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const UNOPIM_URL = `${BASE_URL}/admin/login`;
const STORAGE_STATE = path.resolve(__dirname, '../../.state/admin-auth.json');
const email = 'admin@example.com';
const password = 'admin123';
const invalidEmail = 'admin123@example.com';
const invalidPassword = 'admintest';

/**
 * Submit the logout form directly, bypassing the Vue dropdown.
 * The logout link uses onclick="document.getElementById('adminLogout').submit()"
 * which is unreliable via click() because the dropdown requires Vue to toggle isActive first.
 */
async function logout(adminPage) {
  await adminPage.waitForSelector('#adminLogout', { state: 'attached', timeout: 15000 });
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
  await adminPage.goto('/admin/dashboard', { waitUntil: 'load', timeout: 30000 });
  if (!adminPage.url().includes('/admin/login')) {
    await logout(adminPage);
  }
  await adminPage.waitForLoadState('networkidle');
}


test.describe('Login Page', () => {

test('Logout Check', async ({ adminPage }) => {
  await adminPage.goto('/admin/dashboard', { waitUntil: 'load', timeout: 30000 });
  await logout(adminPage);
  await expect(adminPage).toHaveURL(UNOPIM_URL);
});

test('Error for invalid email and password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', invalidEmail);
  await adminPage.fill('input[name=password]', invalidPassword);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.getByRole('alert')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for invalid email and valid password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', invalidEmail);
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.getByRole('alert')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for valid email and invalid password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', email);
  await adminPage.fill('input[name=password]', invalidPassword);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.getByRole('alert')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for empty username and password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.click('.primary-button');
  await expect(
    adminPage.locator('input[name="email"]').locator('..').locator('p.mt-1')
  ).toContainText('The Email Address field is required');
  await expect(adminPage.locator('.relative > .mt-1')).toContainText(
    'The Password field is required'
  );
});

test('Error for empty email', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(
    adminPage.locator('input[name="email"]').locator('..').locator('p.mt-1')
  ).toContainText('The Email Address field is required');
});

test('Error for empty password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', email);
  await adminPage.click('.primary-button');
  await expect(adminPage.locator('.relative > .mt-1')).toContainText(
    'The Password field is required'
  );
});

test('Login does not impose a password min-length, only validates credentials', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', 'nouser@example.com');
  await adminPage.fill('input[name=password]', 'in123');
  await adminPage.click('.primary-button');
  // Login no longer enforces a min-length on the password; a short password is
  // accepted for submission and fails on credentials, not a field-length error.
  await expect(adminPage.getByRole('alert')).toContainText(/credentials/i);
});

test('Click visibility toggle for showing the password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=password]', 'in123');
  await adminPage.click('#visibilityIcon');
  const inputType = await adminPage.getAttribute('input[name=password]', 'type');
  expect(inputType).toBe('text');
});

test('Email field should be preserved after failed login', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', email);
  await adminPage.fill('input[name=password]', invalidPassword);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.getByRole('alert')).toContainText(
    'Please check your credentials and try again.'
  );
  const emailValue = await adminPage.inputValue('input[name=email]');
  expect(emailValue).toBe(email);
  // The password field is intentionally NOT repopulated after a failed login
  // (no old('password') binding) — re-echoing a submitted password is a security
  // anti-pattern. Assert it is cleared.
  const passwordValue = await adminPage.inputValue('input[name=password]');
  expect(passwordValue).toBe('');
});

test('Login with valid credentials', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', email);
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage).toHaveURL(/\/admin\//);  

  
  await adminPage.context().storageState({ path: STORAGE_STATE });
});
});
