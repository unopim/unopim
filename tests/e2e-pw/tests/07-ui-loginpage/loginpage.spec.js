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
 * Helper: Log out and navigate to login page.
 * Each test starts logged in (from fixture), so we must log out first for login-page tests.
 */
async function goToLoginPage(adminPage) {
  await adminPage.goto('/admin/login', { waitUntil: 'domcontentloaded' });
  // If we're already on the login page (session expired), we're done
  if (adminPage.url().includes('/admin/login')) {
    await adminPage.waitForLoadState('networkidle');
    return;
  }
  // Otherwise log out
  await adminPage.click('button.rounded-full');
  await adminPage.getByRole('link', { name: 'Logout' }).click();
  await expect(adminPage).toHaveURL(UNOPIM_URL);
}


test.describe('Login Page', () => {

test('Logout Check', async ({ adminPage }) => {
  await adminPage.goto('/admin/dashboard', { waitUntil: 'load', timeout: 30000 });
  await adminPage.click('button.rounded-full');
  await adminPage.getByRole('link', { name: 'Logout' }).click();
  await expect(adminPage).toHaveURL(UNOPIM_URL);
});

test('Error for invalid email and password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', invalidEmail);
  await adminPage.fill('input[name=password]', invalidPassword);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.locator('.gap-12 > .text-sm')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for invalid email and valid password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', invalidEmail);
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.locator('.gap-12 > .text-sm')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for valid email and invalid password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', email);
  await adminPage.fill('input[name=password]', invalidPassword);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.locator('.gap-12 > .text-sm')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for empty username and password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.click('.primary-button');
  await expect(adminPage.locator(':nth-child(1) > .mt-1')).toContainText(
    'The Email Address field is required'
  );
  await expect(adminPage.locator('.relative > .mt-1')).toContainText(
    'The Password field is required'
  );
});

test('Error for empty email', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.locator('.mt-1')).toContainText(
    'The Email Address field is required'
  );
});

test('Error for empty password', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', email);
  await adminPage.click('.primary-button');
  await expect(adminPage.locator('.relative > .mt-1')).toContainText(
    'The Password field is required'
  );
});

test('Error for password less than 6 characters', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=password]', 'in123');
  await adminPage.click('.primary-button');
  await expect(
    adminPage.locator('input[name="password"]').locator('..').locator('p.mt-1')
  ).toContainText('The Password field must be at least 6 characters');
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
  // Wait for redirect back to login page with error
  await expect(adminPage.locator('.gap-12 > .text-sm')).toContainText(
    'Please check your credentials and try again.'
  );
  // Email should be preserved
  const emailValue = await adminPage.inputValue('input[name=email]');
  expect(emailValue).toBe(email);
  // Password should be cleared
  const passwordValue = await adminPage.inputValue('input[name=password]');
  expect(passwordValue).toBe('');
});

test('Login with valid credentials', async ({ adminPage }) => {
  await goToLoginPage(adminPage);
  await adminPage.fill('input[name=email]', email);
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage).toHaveURL(/\/admin\//);  // Redirects to last visited admin page or dashboard

  // Persist this valid session back to the shared storage state so subsequent
  // test files don't inherit the invalidated session from earlier logout tests.
  await adminPage.context().storageState({ path: STORAGE_STATE });
});
});
