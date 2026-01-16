// utils/login.js

export async function login(page) {
  // Use the base URL from environment variable
  await page.goto(`${process.env.UNOPIM_URL}`);
  await page.getByRole('textbox', { name: 'Email Address' }).fill(process.env.ADMIN_USERNAME);
  await page.getByRole('textbox', { name: 'Password' }).fill(process.env.ADMIN_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.waitForLoadState('networkidle');
}
