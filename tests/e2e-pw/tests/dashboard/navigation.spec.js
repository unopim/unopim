const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Dashboard Navigation', () => {
test('Shows Dashboard link and goes to dashboard Page', async ({ adminPage }) => {
  await expect(adminPage.locator('.icon-dashboard')).toBeVisible();
  await adminPage.getByRole('link', { name: ' Dashboard' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/dashboard/);
});

test('Goes to Products Page under Catalog', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Products' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/products/);
});

test('Goes to Categories Page under Catalog', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Categories' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/categories/);
});

test('Goes to Category field Page under Catalog', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/category-fields/);
});

test('Goes to Attribute Page under Catalog', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributes/);
});

test('Goes to Attribute Group Page under Catalog', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributegroups/);
});

test('Goes to Attribute Families Page under Catalog', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/families/);
});

test('Goes to Job Tracker under Data Transfer', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Job Tracker' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/tracker/);
});

test('Goes to Import under Data Transfer', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Imports' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports/);
});

test('Goes to Export under Data Transfer', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Data Transfer' }).click();
  await adminPage.getByRole('link', { name: 'Exports' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/exports/);
});

test('Goes to Locales Page under Settings', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Locales' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/locales/);
});

test('Goes to Currencies Page under Settings', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Currencies' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/currencies/);
});

test('Goes to Channels Page under Settings', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Channels' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/channels/);
});

test('Goes to Users Page under Settings', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Users' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/users/);
});

test('Goes to Roles Page under Settings', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Settings' }).click();
  await adminPage.getByRole('link', { name: 'Roles' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/roles/);
});

test('Goes to Magic AI Page under Configuration', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Magic AI' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/configuration\/general\/magic_ai/);
});

test('Goes to Integrations Page under Configuration', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: ' Configuration' }).click();
  await adminPage.getByRole('link', { name: 'Integrations' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/integrations\/api-keys/);
});
});

