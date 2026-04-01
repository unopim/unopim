const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim MyAccount', () => {
test('My Account', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();
  await adminPage.getByRole('link', { name: 'My Account' }).click();
  await adminPage.waitForLoadState('networkidle');
  const fileInput = adminPage.locator('input[type="file"]');
  await fileInput.setInputFiles('assets/john doe.jpeg');
  await adminPage.getByRole('textbox', { name: 'Current Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Current Password' }).fill('admin123');
  await adminPage.getByRole('button', { name: 'Save Account' }).click();
});
});
