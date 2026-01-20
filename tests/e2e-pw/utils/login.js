// utils/login.js

export async function login(page) {
  // Use the base URL from environment variable
  await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.waitForLoadState('networkidle');
}
