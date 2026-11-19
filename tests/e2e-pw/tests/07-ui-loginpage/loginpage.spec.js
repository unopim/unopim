const { test, expect } = require('../../utils/fixtures');
const { login } = require('../../utils/login');
const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const UNOPIM_URL = `${BASE_URL}/admin/login`;
const email = 'admin@example.com';
const password = 'admin123';
const invalidEmail = 'admin123@example.com';
const invalidPassword = 'admintest';

/*
 * Start every test signed out. Logging out of the shared stored session would
 * end it on the server for every other worker that reuses it.
 */
test.use({ storageState: { cookies: [], origins: [] } });

/**
 * Submit the logout form directly, bypassing the Vue dropdown.
 * The logout link uses onclick="document.getElementById('adminLogout').submit()"
 * which is unreliable via click() because the dropdown requires Vue to toggle isActive first.
 */
async function logout(page) {
  await page.waitForSelector('#adminLogout', { state: 'attached', timeout: 15000 });
  await page.evaluate(() => {
    const form = document.getElementById('adminLogout');
    if (form) form.submit();
  });
  await page.waitForURL(/admin\/login/, { timeout: 15000 });
}

async function goToLoginPage(page) {
  await page.goto('/admin/login', { waitUntil: 'load', timeout: 30000 });
}


test.describe('Login Page', () => {

test('Logout Check', async ({ page }) => {
  await login(page);
  await page.goto('/admin/dashboard', { waitUntil: 'load', timeout: 30000 });
  await logout(page);
  await expect(page).toHaveURL(UNOPIM_URL);
});

test('Error for invalid email and password', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=email]', invalidEmail);
  await page.fill('input[name=password]', invalidPassword);
  await page.press('input[name=password]', 'Enter');
  await expect(page.getByRole('alert')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for invalid email and valid password', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=email]', invalidEmail);
  await page.fill('input[name=password]', password);
  await page.press('input[name=password]', 'Enter');
  await expect(page.getByRole('alert')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for valid email and invalid password', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=email]', email);
  await page.fill('input[name=password]', invalidPassword);
  await page.press('input[name=password]', 'Enter');
  await expect(page.getByRole('alert')).toContainText(
    'Please check your credentials and try again.'
  );
});

test('Error for empty username and password', async ({ page }) => {
  await goToLoginPage(page);
  await page.click('.primary-button');
  await expect(
    page.locator('input[name="email"]').locator('..').locator('p.mt-1')
  ).toContainText('The Email Address field is required');
  await expect(page.locator('.relative > .mt-1')).toContainText(
    'The Password field is required'
  );
});

test('Error for empty email', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=password]', password);
  await page.press('input[name=password]', 'Enter');
  await expect(
    page.locator('input[name="email"]').locator('..').locator('p.mt-1')
  ).toContainText('The Email Address field is required');
});

test('Error for empty password', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=email]', email);
  await page.click('.primary-button');
  await expect(page.locator('.relative > .mt-1')).toContainText(
    'The Password field is required'
  );
});

test('Login does not impose a password min-length, only validates credentials', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=email]', 'nouser@example.com');
  await page.fill('input[name=password]', 'in123');
  await page.click('.primary-button');
  // Login no longer enforces a min-length on the password; a short password is
  // accepted for submission and fails on credentials, not a field-length error.
  await expect(page.getByRole('alert')).toContainText(/credentials/i);
});

test('Click visibility toggle for showing the password', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=password]', 'in123');
  await page.click('#visibilityIcon');
  const inputType = await page.getAttribute('input[name=password]', 'type');
  expect(inputType).toBe('text');
});

test('Email field should be preserved after failed login', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=email]', email);
  await page.fill('input[name=password]', invalidPassword);
  await page.press('input[name=password]', 'Enter');
  await expect(page.getByRole('alert')).toContainText(
    'Please check your credentials and try again.'
  );
  const emailValue = await page.inputValue('input[name=email]');
  expect(emailValue).toBe(email);
  // The password field is intentionally NOT repopulated after a failed login
  // (no old('password') binding) — re-echoing a submitted password is a security
  // anti-pattern. Assert it is cleared.
  const passwordValue = await page.inputValue('input[name=password]');
  expect(passwordValue).toBe('');
});

test('Login with valid credentials', async ({ page }) => {
  await goToLoginPage(page);
  await page.fill('input[name=email]', email);
  await page.fill('input[name=password]', password);
  await page.press('input[name=password]', 'Enter');
  await expect(page).toHaveURL(/\/admin\//);  
});
});
