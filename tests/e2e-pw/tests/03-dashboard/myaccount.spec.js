const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim MyAccount', () => {
test('My Account', async ({ adminPage }) => {
  await adminPage.getByRole('button', { name: 'E' }).click();
  await adminPage.getByRole('link', { name: 'My Account' }).click();
  const fileInput = adminPage.locator('input[type="file"]');
  await fileInput.setInputFiles('assets/john doe.jpeg');
  await adminPage.getByRole('textbox', { name: 'Current Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Current Password' }).fill('admin123');
  await adminPage.getByRole('button', { name: 'Save Account' }).click();
});
});

