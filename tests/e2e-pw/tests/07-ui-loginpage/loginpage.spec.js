const { test, expect } = require('../../utils/fixtures');
const UNOPIM_URL = 'http://127.0.0.1:8000/admin/login';
const email = 'admin@example.com';
const password = 'admin123';
const invalidEmail = 'admin123@example.com';
const invalidPassword = 'admintest';


test.describe('Login adminPage', () => {
test('Logout Check', async ({ adminPage }) => {
  await adminPage.click('button.rounded-full');
  await adminPage.getByRole('link', { name: 'Logout' }).click();
  await expect(adminPage).toHaveURL(UNOPIM_URL);
});

test('Error for invalid email and password', async ({ adminPage }) => {
  await adminPage.fill('input[name=email]', invalidEmail);
  await adminPage.fill('input[name=password]', invalidPassword);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.locator('.gap-12 > .text-sm')).toContainText(
  'Please check your credentials and try again.'
  );
});

test('Error for invalid email and valid password', async ({ adminPage }) => {
  await adminPage.fill('input[name=email]', invalidEmail);
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.locator('.gap-12 > .text-sm')).toContainText(
  'Please check your credentials and try again.'
  );
});

test('Error for valid email and invalid password', async ({ adminPage }) => {
  await adminPage.fill('input[name=email]', email);
  await adminPage.fill('input[name=password]', invalidPassword);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.locator('.gap-12 > .text-sm')).toContainText(
  'Please check your credentials and try again.'
  );
});

test('Error for empty username and password', async ({ adminPage }) => {
  await adminPage.click('.primary-button');
  await expect(adminPage.locator(':nth-child(1) > .mt-1')).toContainText(
  'The Email Address field is required'
  );
  await expect(adminPage.locator('.relative > .mt-1')).toContainText(
  'The Password field is required'
  );
});

test('Error for empty email', async ({ adminPage }) => {
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage.locator('.mt-1')).toContainText(
  'The Email Address field is required'
  );
});

test('Error for empty password', async ({ adminPage }) => {
  await adminPage.fill('input[name=email]', email);
  await adminPage.click('.primary-button');
  await expect(adminPage.locator('.relative > .mt-1')).toContainText(
  'The Password field is required'
  );
});

test('Error for password less than 6 characters', async ({ adminPage }) => {
  await adminPage.fill('input[name=password]', 'in123');
  await adminPage.click('.primary-button');
  await expect(
  adminPage.locator('input[name="password"]').locator('..').locator('p.mt-1')
  ).toContainText('The Password field must be at least 6 characters');
});

test('Click visibility toggle for showing the password', async ({ adminPage }) => {
  await adminPage.fill('input[name=password]', 'in123');
  await adminPage.click('#visibilityIcon');
  const inputType = await adminPage.getAttribute('input[name=password]', 'type');
  console.log('Password input type after toggle:', inputType);
});

test('Login with valid credentials', async ({ adminPage }) => {
  await adminPage.fill('input[name=email]', email);
  await adminPage.fill('input[name=password]', password);
  await adminPage.press('input[name=password]', 'Enter');
  await expect(adminPage).toHaveURL(/\/dashboard/);
});
});
