import { test, expect } from '@playwright/test';
test.describe('UnoPim Dashboard Navigation', () => {
test.beforeEach(async ({ page }) => {
  await page.goto('/admin/dashboard');
});

test('Shows Dashboard link and goes to dashboard page', async ({ page }) => {
  await expect(page.locator('.icon-dashboard')).toBeVisible();
  await page.getByRole('link', { name: ' Dashboard' }).click();
  await expect(page).toHaveURL(/\/admin\/dashboard/);
});

test('Goes to Products page under Catalog', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Products' }).click();
  await expect(page).toHaveURL(/\/admin\/catalog\/products/);
});

test('Goes to Categories page under Catalog', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Categories' }).click();
  await expect(page).toHaveURL(/\/admin\/catalog\/categories/);
});

test('Goes to Category field page under Catalog', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Category Fields' }).click();
  await expect(page).toHaveURL(/\/admin\/catalog\/category-fields/);
});

test('Goes to Attribute page under Catalog', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await expect(page).toHaveURL(/\/admin\/catalog\/attributes/);
});

test('Goes to Attribute Group page under Catalog', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attribute Groups' }).click();
  await expect(page).toHaveURL(/\/admin\/catalog\/attributegroups/);
});

test('Goes to Attribute Families page under Catalog', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attribute Families' }).click();
  await expect(page).toHaveURL(/\/admin\/catalog\/families/);
});

test('Goes to Job Tracker under Data Transfer', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Job Tracker' }).click();
  await expect(page).toHaveURL(/\/admin\/settings\/data-transfer\/tracker/);
});

test('Goes to Import under Data Transfer', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Imports' }).click();
  await expect(page).toHaveURL(/\/admin\/settings\/data-transfer\/imports/);
});

test('Goes to Export under Data Transfer', async ({ page }) => {
  await page.getByRole('link', { name: ' Data Transfer' }).click();
  await page.getByRole('link', { name: 'Exports' }).click();
  await expect(page).toHaveURL(/\/admin\/settings\/data-transfer\/exports/);
});

test('Goes to Locales page under Settings', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Locales' }).click();
  await expect(page).toHaveURL(/\/admin\/settings\/locales/);
});

test('Goes to Currencies page under Settings', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Currencies' }).click();
  await expect(page).toHaveURL(/\/admin\/settings\/currencies/);
});

test('Goes to Channels page under Settings', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Channels' }).click();
  await expect(page).toHaveURL(/\/admin\/settings\/channels/);
});

test('Goes to Users page under Settings', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Users' }).click();
  await expect(page).toHaveURL(/\/admin\/settings\/users/);
});

test('Goes to Roles page under Settings', async ({ page }) => {
  await page.getByRole('link', { name: ' Settings' }).click();
  await page.getByRole('link', { name: 'Roles' }).click();
  await expect(page).toHaveURL(/\/admin\/settings\/roles/);
});

test('Goes to Magic AI page under Configuration', async ({ page }) => {
  await page.getByRole('link', { name: ' Configuration' }).click();
  await page.getByRole('link', { name: 'Magic AI' }).click();
  await expect(page).toHaveURL(/\/admin\/configuration\/general\/magic_ai/);
});

test('Goes to Integrations page under Configuration', async ({ page }) => {
  await page.getByRole('link', { name: ' Configuration' }).click();
  await page.getByRole('link', { name: 'Integrations' }).click();
  await expect(page).toHaveURL(/\/admin\/integrations\/api-keys/);
});
});

