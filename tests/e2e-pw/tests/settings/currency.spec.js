const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Test cases', () => {
test('Create Currency with empty Code field', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  await adminPage.getByRole('button', { name: 'Create Currency' }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('');
  await adminPage.getByRole('textbox', { name: 'Symbol' }).click();
  await adminPage.getByRole('textbox', { name: 'Symbol' }).fill('₫');
  await adminPage.getByRole('textbox', { name: 'Decimal' }).click();
  await adminPage.getByRole('textbox', { name: 'Decimal' }).fill('2');
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Currency' }).click();
  await expect(adminPage.getByText(/The Code field is required/i)).toBeVisible();
});

test('Create Currency with Code less than 3 character', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  await adminPage.getByRole('button', { name: 'Create Currency' }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('gh');
  await adminPage.getByRole('textbox', { name: 'Symbol' }).click();
  await adminPage.getByRole('textbox', { name: 'Symbol' }).fill('₫');
  await adminPage.getByRole('textbox', { name: 'Decimal' }).click();
  await adminPage.getByRole('textbox', { name: 'Decimal' }).fill('2');
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Currency' }).click();
  await expect(adminPage.getByText(/The code must be at least 3 characters./i)).toBeVisible();
});

test('Create Currency with Code more than 3 character', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  await adminPage.getByRole('button', { name: 'Create Currency' }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('ghdn');
  await adminPage.getByRole('textbox', { name: 'Symbol' }).click();
  await adminPage.getByRole('textbox', { name: 'Symbol' }).fill('₫');
  await adminPage.getByRole('textbox', { name: 'Decimal' }).click();
  await adminPage.getByRole('textbox', { name: 'Decimal' }).fill('2');
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Currency' }).click();
  await expect(adminPage.getByText(/The code may not be greater than 3 characters./i)).toBeVisible();
});

test('Create Currency ', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  await adminPage.getByRole('button', { name: 'Create Currency' }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).click();
  await adminPage.getByRole('textbox', { name: 'Code', exact: true }).fill('VND');
  await adminPage.getByRole('textbox', { name: 'Symbol' }).click();
  await adminPage.getByRole('textbox', { name: 'Symbol' }).fill('₫');
  await adminPage.getByRole('textbox', { name: 'Decimal' }).click();
  await adminPage.getByRole('textbox', { name: 'Decimal' }).fill('2');
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Currency' }).click();
  await expect(adminPage.getByText(/Currency created successfully/i)).toBeVisible();
});

test('should allow Currency search', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code or id' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code or id' }).type('VN');
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('text=VND', {exact:true})).toBeVisible();
});

test('Update Currency ', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code or id' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code or id' }).type('vnd');
  await adminPage.keyboard.press('Enter');
  const itemRow = adminPage.locator('div', { hasText: 'VNDVietnamese Dong' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByRole('textbox', { name: 'Decimal' }).click();
  await adminPage.getByRole('textbox', { name: 'Decimal' }).fill('5');
  await adminPage.locator('label[for="status"]').click();
  await adminPage.getByRole('button', { name: 'Save Currency' }).click();
  await expect(adminPage.getByText(/Currency updated successfully/i)).toBeVisible();
});

test('Delete Currency ', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code or id' }).click();
  await adminPage.getByRole('textbox', { name: 'Search by code or id' }).type('vnd');
  await adminPage.keyboard.press('Enter');
  const itemRow = adminPage.locator('div', { hasText: 'VNDVietnamese Dong' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/Currency deleted successfully/i)).toBeVisible();
});

test('Delete Enable Currency ', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  const itemRow = adminPage.locator('div', { hasText: 'Enabled' });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.getByText(/You cannot delete a currency linked to a channel/i)).toBeVisible();
});
});

