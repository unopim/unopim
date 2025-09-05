const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Test cases', () => {
test('Delete Locale', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Locales' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code' }).type('af_ZA');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForTimeout(100);
  const itemRow = adminPage.locator('div', { hasText: 'af_ZAAfrikaans (South Africa)' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Locale deleted successfully/i)).toBeVisible();
});

test('Create locale with empty Code field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Locales' }).click();
  await adminPage.getByRole('button', { name: 'Create Locale' }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('');
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Locale' }).click();
  await expect(adminPage.getByText(/The Code field is required/i)).toBeVisible();
});

test('Create locale with existing Code value', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Locales' }).click();
  await adminPage.getByRole('button', { name: 'Create Locale' }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('en_US');
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Locale' }).click();
  await expect(adminPage.getByText(/The code has already been taken./i)).toBeVisible();
});

test('Create locale', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Locales' }).click();
  await adminPage.getByRole('button', { name: 'Create Locale' }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('af_ZA');
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Locale' }).click();
  await expect(adminPage.getByText(/Locale created successfully/i)).toBeVisible();
});

test('Update Locale ', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Locales' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code' }).type('af_ZA');
  await adminPage.keyboard.press('Enter');
  const itemRow = adminPage.locator('div', { hasText: 'af_ZAAfrikaans (South Africa)' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Locale' }).click();
  await expect(adminPage.getByText(/Locale Updated successfully/i)).toBeVisible();
});

test('Delete Enable Locale', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Locales' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Enabled' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/You cannot delete a locale linked to a channel or user/i)).toBeVisible();
});
});

