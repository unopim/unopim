import { test, expect } from '@playwright/test';

const email = 'admin@example.com';
const password = 'admin123';
const invalidEmail = 'admin123@example.com';
const invalidPassword = 'admintest';

test.describe('Login Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://127.0.0.1:8000/admin/login');
  });

  test('Login with valid credentials', async ({ page }) => {
    await page.fill('input[name=email]', email);
    await page.fill('input[name=password]', password);
    await page.press('input[name=password]', 'Enter');
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test('Error for invalid email and password', async ({ page }) => {
    await page.fill('input[name=email]', invalidEmail);
    await page.fill('input[name=password]', invalidPassword);
    await page.press('input[name=password]', 'Enter');
    await expect(page.locator('.gap-12 > .text-sm')).toContainText('Please check your credentials and try again.');
  });

  test('Error for invalid email and valid password', async ({ page }) => {
    await page.fill('input[name=email]', invalidEmail);
    await page.fill('input[name=password]', password);
    await page.press('input[name=password]', 'Enter');
    await expect(page.locator('.gap-12 > .text-sm')).toContainText('Please check your credentials and try again.');
  });

  test('Error for valid email and invalid password', async ({ page }) => {
    await page.fill('input[name=email]', email);
    await page.fill('input[name=password]', invalidPassword);
    await page.press('input[name=password]', 'Enter');
    await expect(page.locator('.gap-12 > .text-sm')).toContainText('Please check your credentials and try again.');
  });

  test('Error for empty username and password', async ({ page }) => {
    await page.click('.primary-button');
    await expect(page.locator(':nth-child(1) > .mt-1')).toContainText('The Email Address field is required');
    await expect(page.locator('.relative > .mt-1')).toContainText('The Password field is required');
  });

  test('Error for empty email', async ({ page }) => {
    await page.fill('input[name=password]', password);
    await page.press('input[name=password]', 'Enter');
    await expect(page.locator('.mt-1')).toContainText('The Email Address field is required');
  });

  test('Error for empty password', async ({ page }) => {
    await page.fill('input[name=email]', email);
    await page.click('.primary-button');
    await expect(page.locator('.relative > .mt-1')).toContainText('The Password field is required');
  });

  test('Error for password less than 6 characters', async ({ page }) => {
    await page.fill('input[name=password]', 'in123');
    await page.click('.primary-button');
    await expect(
      page.locator('input[name="password"]').locator('..').locator('p.mt-1')
    ).toContainText('The Password field must be at least 6 characters');
  });

  test('Click visibility toggle for showing the password', async ({ page }) => {
    await page.fill('input[name=password]', 'in123');
    await page.click('#visibilityIcon');
    const inputType = await page.getAttribute('input[name=password]', 'type');
    console.log('Password input type after toggle:', inputType);
  });
});
