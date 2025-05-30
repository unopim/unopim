import { test, expect } from '@playwright/test';

test('My Account', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).click();
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).click();
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.getByRole('button', { name: 'J' }).click();
  await page.getByRole('link', { name: 'My Account' }).click();
  const fileInput = page.locator('input[type="file"]');
  await fileInput.setInputFiles('/home/users/pawan.kumar/Downloads/john doe.jpeg');
  await page.getByRole('textbox', { name: 'Current Password' }).click();
  await page.getByRole('textbox', { name: 'Current Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Save Account' }).click();
});

test('Logout Check', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).click();
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).click();
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.click('button.rounded-full');
  await page.getByRole('link', { name: 'Logout' }).click();
  await expect(page).toHaveURL('http://127.0.0.1:8000/admin/login');
});



