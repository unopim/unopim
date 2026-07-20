const { test, expect } = require('../../utils/fixtures');
const { navigateTo, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Multiple-webhooks admin CRUD coverage: list, create (+validation), the
 * three-tab edit page (General / Logs / History), the Test button, and delete.
 */
test.describe('UnoPim Webhooks (multiple) CRUD', () => {
  const unique = () => `PW Hook ${Date.now()}${Math.floor(Math.random() * 1000)}`;

  async function gotoCreate(page) {
    await navigateTo(page, 'webhook');
    await page.getByRole('link', { name: 'Create Webhook' }).click();
    await page.waitForURL(/\/admin\/configuration\/webhook\/create/, { timeout: 20000 });
  }

  async function selectEvent(page, label) {
    const ms = page.locator('.multiselect').filter({ has: page.locator('input[name="events"]') });
    await ms.locator('.multiselect__tags').click();
    const option = page.getByRole('option', { name: label }).first();
    await option.waitFor({ state: 'visible', timeout: 10000 });
    await option.click();
  }

  async function createWebhook(page, name, url = 'https://example.com/hook') {
    await gotoCreate(page);
    await page.locator('input[name="name"]').fill(name);
    await page.locator('input[name="url"]').fill(url);
    await selectEvent(page, 'Product Created');
    await clickSaveAndExpect(page, 'Save', /created successfully/i, /\/admin\/configuration\/webhook\/edit\/\d+/);
  }

  test('list page loads with heading and create button', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    await expect(adminPage).toHaveURL(/.*\/admin\/configuration\/webhook$/);
    await expect(adminPage.locator('#app').getByText('Webhooks', { exact: true }).first()).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Create Webhook' })).toBeVisible();
  });

  test('create page shows all fields', async ({ adminPage }) => {
    await gotoCreate(adminPage);
    await expect(adminPage.locator('input[name="name"]')).toBeVisible();
    await expect(adminPage.locator('input[name="url"]')).toBeVisible();
    await expect(adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="events"]') })).toBeVisible();
    await expect(adminPage.locator('input[name="secret"]')).toBeVisible();
    await expect(adminPage.getByRole('button', { name: 'Send Test' })).toBeVisible();
    // Save button is intentionally hidden until the form is dirty (unsaved-changes tracking).
    await adminPage.locator('input[name="name"]').fill('dirty');
    await expect(adminPage.getByRole('button', { name: /^Save/ }).first()).toBeVisible({ timeout: 10000 });
  });

  test('shows validation errors when required fields are empty', async ({ adminPage }) => {
    await gotoCreate(adminPage);
    // Make the form dirty so the save affordance appears, but leave url/events empty.
    await adminPage.locator('input[name="name"]').fill(unique());
    await adminPage.getByRole('button', { name: /^Save/ }).first().click();
    await expect(adminPage.locator('#app').getByText(/required/i).first()).toBeVisible({ timeout: 10000 });
    await expect(adminPage).toHaveURL(/\/create/);
  });

  test('creates a webhook and lands on the edit page', async ({ adminPage }) => {
    const name = unique();
    await createWebhook(adminPage, name);
    await expect(adminPage).toHaveURL(/\/admin\/configuration\/webhook\/edit\/\d+/);
    await expect(adminPage.locator('input[name="name"]')).toHaveValue(name);
  });

  test('edit page exposes General, Logs and History tabs', async ({ adminPage }) => {
    await createWebhook(adminPage, unique());
    await expect(adminPage.getByRole('link', { name: 'General' })).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Logs' })).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'History' })).toBeVisible();
  });

  test('Logs tab shows the per-webhook log grid', async ({ adminPage }) => {
    await createWebhook(adminPage, unique());
    await adminPage.getByRole('link', { name: 'Logs' }).click();
    await adminPage.waitForURL(/[?&]logs=1/, { timeout: 20000 });
    await expect(adminPage.locator('#app').getByText('No Records Available.')).toBeVisible({ timeout: 15000 });
  });

  test('History tab shows history only, not the logs grid', async ({ adminPage }) => {
    await createWebhook(adminPage, unique());
    await adminPage.getByRole('link', { name: 'History' }).click();
    await adminPage.waitForURL(/[?&]history=1/, { timeout: 20000 });
    // Regression: the logs datagrid must NOT bleed into the History tab.
    await expect(adminPage.getByPlaceholder(/Search by SKU or user/i)).toHaveCount(0);
  });

  test('Test button reports an outcome via a flash message', async ({ adminPage }) => {
    await gotoCreate(adminPage);
    await adminPage.locator('input[name="url"]').fill('https://example.com/hook');
    await adminPage.getByRole('button', { name: 'Send Test' }).click();
    await expect(adminPage.getByRole('alert').first()).toBeVisible({ timeout: 20000 });
  });

  test('deletes a webhook from the grid', async ({ adminPage }) => {
    const name = unique();
    await createWebhook(adminPage, name);

    await navigateTo(adminPage, 'webhook');
    const search = adminPage.getByPlaceholder('Search').first();
    await search.fill(name);
    await search.press('Enter');
    await adminPage.waitForTimeout(1500);

    await adminPage.locator('.icon-delete').first().click();
    await adminPage.getByRole('button', { name: /Agree|Yes|Ok|Delete/i }).first().click().catch(() => {});
    await expect(adminPage.locator('#app').getByText(/deleted successfully/i)).toBeVisible({ timeout: 20000 });
  });
});
