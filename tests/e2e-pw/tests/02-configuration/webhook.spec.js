const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('UnoPim Webhook test cases', () => {

  test('Check the webhook option after installation', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    await expect(adminPage).toHaveURL(/.*\/admin\/webhook\/settings/);
  });

  test('Check that webhook is clickable', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    await expect(adminPage.getByRole('link', { name: 'Webhook' })).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Webhook' })).toBeEnabled();
  });

  test('Check the url of the webhook page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    await expect(adminPage).toHaveURL(/.*\/admin\/webhook\/settings/);
  });

  test('Check the page after clicking webhook', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    await expect(adminPage).toHaveURL(/.*\/admin\/webhook\/settings/);
    await expect(adminPage.locator('#app').getByText('Webhook Settings', { exact: true })).toBeVisible();
  });

  test('Check the fields in the webhook page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    await expect(adminPage.locator('label[for="webhook_active"]')).toBeVisible();
    await expect(adminPage.locator('label[for="webhook_active"]')).toBeEnabled();
    const webhookUrlField = adminPage.locator('input[name="webhook_url"]');
    await expect(webhookUrlField).toBeVisible();
    await expect(webhookUrlField).toBeEnabled();
    await expect(adminPage.getByRole('button', { name: 'Save' })).toBeVisible();
    await expect(adminPage.getByRole('button', { name: 'Save' })).toBeEnabled();
  });

  test('Check saving webhook settings with empty field', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    await adminPage.locator('input[name="webhook_url"]').fill('');
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.locator('#app').getByText('The Webhook URL field is required')).toBeVisible();
  });

  test('Check by saving webhook URL with invalid random string', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const webhookUrlField = adminPage.locator('input[name="webhook_url"]');
    await webhookUrlField.fill('invalid-url');
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.locator('#app').getByText('The webhook url format is invalid.')).toBeVisible();
  });

  test('Check by saving webhook URL with valid URL', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const webhookUrlField = adminPage.locator('input[name="webhook_url"]');
    await webhookUrlField.fill('https://example.com/webhook');
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.locator('#app').getByText('Webhook settings saved successfully')).toBeVisible();
  });

  test('Check toggling the webhook active checkbox', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const webhookActiveCheckbox = adminPage.locator('label[for="webhook_active"]');
    const isCheckedBefore = await webhookActiveCheckbox.isChecked();
    await webhookActiveCheckbox.click();
    const isCheckedAfter = await webhookActiveCheckbox.isChecked();
    expect(isCheckedBefore).not.toBe(isCheckedAfter);
  });

  test('Check that webhook settings persist after saving', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const webhookUrlField = adminPage.locator('input[name="webhook_url"]');
    const webhookActiveCheckbox = adminPage.locator('label[for="webhook_active"]');
    const urlToSet = 'https://example.com/webhook';
    await webhookUrlField.fill(urlToSet);
    const isCheckedBefore = await webhookActiveCheckbox.isChecked();
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.locator('#app').getByText('Webhook settings saved successfully')).toBeVisible();
    await adminPage.reload();
    const savedUrl = await webhookUrlField.inputValue();
    const isCheckedAfter = await webhookActiveCheckbox.isChecked();
    expect(savedUrl).toBe(urlToSet);
    expect(isCheckedBefore).toBe(isCheckedAfter);
  });

  test('Check the log section in webhook page is visible and clickable', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const logSection = adminPage.getByRole('link', { name: 'Logs' });
    await expect(logSection).toBeVisible();
    await expect(logSection).toBeEnabled();
  });

  test('Check the URL of the log section in webhook page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const logSection = adminPage.getByRole('link', { name: 'Logs' });
    await logSection.click();
    await expect(adminPage).toHaveURL(/admin\/webhook\/settings.*logs/);
  });

  test('Check the content of the log section in webhook page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const logSection = adminPage.getByRole('link', { name: 'Logs' });
    await logSection.click();
    await expect(adminPage.locator('#app').getByText('Webhook Logs')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('No Records Available.')).toBeVisible();
  });

  test('Check the presence of columns in the log section of webhook page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const logSection = adminPage.getByRole('link', { name: 'Logs' });
    await logSection.click();
    await expect(adminPage.locator('#app').getByText('ID')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Date/Time')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('SKU')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('User', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Status')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Actions')).toBeVisible();
  });

  test('Check the webhook log filtering options', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const logSection = adminPage.getByRole('link', { name: 'Logs' });
    await logSection.click();
    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
    await expect(adminPage.locator('input[name="sku"]')).toBeVisible();
    await expect(adminPage.locator('input[name="user"]')).toBeVisible();
    await expect(adminPage.locator('input[name="status"]')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Save')).toBeVisible();
  });

  test('Check the search bar in the webhook log section', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const logSection = adminPage.getByRole('link', { name: 'Logs' });
    await logSection.click();
    const searchBar = adminPage.getByRole('textbox', { name: 'Search' });
    await expect(searchBar).toBeVisible();
    await expect(searchBar).toBeEnabled();
  });

  test('Check the history section on the webhook page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const historySection = adminPage.getByRole('link', { name: 'History' });
    await expect(historySection).toBeVisible();
    await expect(historySection).toBeEnabled();
  });

  test('Check the URL of the history section in webhook page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const historySection = adminPage.getByRole('link', { name: 'History' });
    await historySection.click();
    await expect(adminPage).toHaveURL(/admin\/webhook\/settings.*history/);
  });

  test('Check the column of the history section in webhook page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'webhook');
    const historySection = adminPage.getByRole('link', { name: 'History' });
    await historySection.click();
    await expect(adminPage.locator('#app').getByText('Date / Time')).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Version', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('User', { exact: true })).toBeVisible();
    await expect(adminPage.locator('#app').getByText('Actions')).toBeVisible();
  });

});
