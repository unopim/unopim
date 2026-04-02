const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('UnoPim Dashboard Navigation', () => {
test('Shows Dashboard link and goes to dashboard Page', async ({ adminPage }) => {
  await navigateTo(adminPage, 'dashboard');
  await expect(adminPage).toHaveURL(/\/admin\/dashboard/);
});

test('Goes to Products Page under Catalog', async ({ adminPage }) => {
  await navigateTo(adminPage, 'products');
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/products/);
});

test('Goes to Categories Page under Catalog', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categories');
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/categories/);
});

test('Goes to Category field Page under Catalog', async ({ adminPage }) => {
  await navigateTo(adminPage, 'categoryFields');
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/category-fields/);
});

test('Goes to Attribute Page under Catalog', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributes');
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributes/);
});

test('Goes to Attribute Group Page under Catalog', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeGroups');
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/attributegroups/);
});

test('Goes to Attribute Families Page under Catalog', async ({ adminPage }) => {
  await navigateTo(adminPage, 'attributeFamilies');
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/families/);
});

test('Goes to Job Tracker under Data Transfer', async ({ adminPage }) => {
  await adminPage.goto('/admin/settings/data-transfer/tracker', { waitUntil: 'domcontentloaded' });
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/tracker/);
});

test('Goes to Import under Data Transfer', async ({ adminPage }) => {
  await navigateTo(adminPage, 'imports');
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports/);
});

test('Goes to Export under Data Transfer', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/exports/);
});

test('Goes to Locales Page under Settings', async ({ adminPage }) => {
  await navigateTo(adminPage, 'locales');
  await expect(adminPage).toHaveURL(/\/admin\/settings\/locales/);
});

test('Goes to Currencies Page under Settings', async ({ adminPage }) => {
  await navigateTo(adminPage, 'currencies');
  await expect(adminPage).toHaveURL(/\/admin\/settings\/currencies/);
});

test('Goes to Channels Page under Settings', async ({ adminPage }) => {
  await navigateTo(adminPage, 'channels');
  await expect(adminPage).toHaveURL(/\/admin\/settings\/channels/);
});

test('Goes to Users Page under Settings', async ({ adminPage }) => {
  await navigateTo(adminPage, 'users');
  await expect(adminPage).toHaveURL(/\/admin\/settings\/users/);
});

test('Goes to Roles Page under Settings', async ({ adminPage }) => {
  await navigateTo(adminPage, 'roles');
  await expect(adminPage).toHaveURL(/\/admin\/settings\/roles/);
});

test('Goes to Magic AI Page under Configuration', async ({ adminPage }) => {
  await adminPage.goto('/admin/magic-ai/platform', { waitUntil: 'domcontentloaded' });
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage).toHaveURL(/\/admin\/magic-ai\/platform/);
});

test('Goes to Integrations Page under Configuration', async ({ adminPage }) => {
  await navigateTo(adminPage, 'integrations');
  await expect(adminPage).toHaveURL(/\/admin\/integrations\/api-keys/);
});
});
