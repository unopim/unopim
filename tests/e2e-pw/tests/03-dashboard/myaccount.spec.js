const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, clickAndWaitForPage } = require('../../utils/helpers');

test.describe('UnoPim MyAccount', () => {
test('My Account', async ({ adminPage }) => {
  await navigateTo(adminPage, 'dashboard');
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();
  await clickAndWaitForPage(adminPage, adminPage.getByRole('link', { name: 'My Account' }));
  // The AI agent panel carries its own "Attach image" file input when it renders.
  const fileInput = adminPage.locator('input[type="file"][id$="_imageInput"]');
  await fileInput.setInputFiles('assets/john doe.jpeg');
  await adminPage.getByRole('textbox', { name: 'Current Password' }).click();
  await adminPage.getByRole('textbox', { name: 'Current Password' }).fill('admin123');
  await clickSave(adminPage, 'Save Account');
});
});
