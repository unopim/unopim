import { test, expect } from '@playwright/test';
test.use({ storageState: 'storage/auth.json' });
// test.use({ launchOptions: { slowMo: 500 } }); // Slow down actions by 1 second
// Reuse login session

test.describe('Shopify Credentials Page', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the Shopify Credentials Page
    await page.goto('admin/shopify/credentials');
  });

  test('Verify Shopify Credentials page title is visible', async ({ page }) => {
    await expect(page.locator('p:text("Shopify Credentials")')).toBeVisible();
  });

  test('Click on Create Credential button', async ({ page }) => {
    await page.locator('button.primary-button:has-text("Create Credential")').click();
    // await expect(page.locator('.fixed.inset-0.bg-gray-500')).toBeVisible(); // Verify modal opened
  });

  test('Verify search functionality is present', async ({ page }) => {

    await expect(page.getByRole('textbox', { name: 'Search' })).toBeVisible();

    // Fill the search input field
    await page.fill('input[name="search"]', 'Test Shop');

    // Verify search results message appears
    await expect(page.locator('p:has-text("0 Results")')).toBeVisible();
  });

  test('Click on Filter button', async ({ page }) => {
    await page.getByText('Filter', { exact: true }).click();
    // await expect(page.locator('.z-10.hidden')).not.toHaveClass(/hidden/);
  });

  test('Verify pagination dropdown', async ({ page }) => {
    await page.locator('button:has-text("10")').click();
    // await page.locator('li:has-text("50")').click();
    await page.getByText('50', { exact: true }).click();
    await expect(page.locator('button:has-text("50")')).toBeVisible();
  });

  test('Verify table headers', async ({ page }) => {
    const headers = ['Shopify URL', 'API Version', 'Enable', 'Actions'];
    for (const header of headers) {
      await expect(page.getByText(header)).toBeVisible({ timeout: 10000 });
    }
  });

  test('Verify No Records Available message', async ({ page }) => {
    await expect(page.locator('p:text("No Records Available.")')).toBeVisible();
  });
});

test.describe.serial('Shopify Create credential Page', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the Shopify Credentials Page
    await page.goto('admin/shopify/credentials');
  });

  test('Checked credential form and validation', async ({ page }) => {
    await page.getByRole('button', { name: 'Create Credential' }).click();
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('The Shopify URL field is')).toBeVisible();
    await expect(page.locator('#app')).toContainText('The Shopify URL field is required');
    await expect(page.getByText('The Admin API access token')).toBeVisible();
    await expect(page.locator('#app')).toContainText('The Admin API access token field is required');
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).click();
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).fill('tesst');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('The Admin API access token')).toBeVisible();
    await expect(page.locator('#app')).toContainText('The Admin API access token field is required');
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).click();
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).fill('');
    await page.getByRole('textbox', { name: 'Admin API access token' }).click();
    await page.getByRole('textbox', { name: 'Admin API access token' }).fill('fdssdfsdf');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('The Shopify URL field is')).toBeVisible();
    await expect(page.locator('#app')).toContainText('The Shopify URL field is required');
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).click();
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).fill('sfasdfasdfas');
    await page.getByRole('textbox', { name: 'Admin API access token' }).click();
    await page.getByRole('textbox', { name: 'Admin API access token' }).fill('fdssdfsdffasdfasdfasdf');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('Invalid URL')).toBeVisible();
    await expect(page.locator('#app')).toContainText('Invalid URL');
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).click();
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).fill('http://shopify.demo,com');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('Invalid Credential').first()).toBeVisible();
    await expect(page.locator('#app')).toContainText('Invalid Credential');
    await expect(page.getByText('Invalid Credential').nth(1)).toBeVisible();
    await expect(page.locator('#app')).toContainText('Invalid Credential');
    await expect(page.getByText('Save')).toBeVisible();
    // await page.getByRole('button', { name: 'Save' }).click();

  });

  test('Credential creation with the valid data', async ({ page }) => {
    await page.getByRole('button', { name: 'Create Credential' }).click();
    await page.getByRole('textbox', { name: 'http://demo.myshopify.com' }).fill('http://quickstart-c2b9e6cf.myshopify.com');
    await page.getByRole('textbox', { name: 'Admin API access token' }).fill(process.env.SHOPIFY_ACCESS_TOKEN || 'shpat_test_placeholder_token');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByRole('banner')).toBeVisible();
    await expect(page.locator('body')).toContainText('Credential Created Success');
    await page.getByRole('link', { name: 'Back' }).click();
    await expect(page.locator('#app')).toContainText('Yes');
    await page.getByText('1 Results').click();
    await expect(page.getByText('1 Results')).toBeVisible();
  });

  test('Credential edit and required validation', async ({ page }) => {
    await expect(page.getByTitle('Edit')).toBeVisible();
    await page.getByTitle('Edit').click();
    const currentUrl = page.url();
    await expect(currentUrl).toMatch(/\/admin\/shopify\/credentials\/edit\/\d+$/);
    await page.getByText('Save').click();
    await page.waitForSelector('text=The Location List field is required', { state: 'visible' });
    // Now assert visibility
    await expect(page.getByText('The Location List field is required')).toBeVisible();
    // await page.locator('input[name="locations"]')
    await expect(page.locator('#app')).toContainText('The Location List field is required');
    await page.locator('div').filter({ hasText: /^Location List$/ }).click();
    await page.getByText('Snow City Warehouse').click();
    await page.getByText('Select Locales').first().click();
    await page.getByRole('option').getByText('English (United States)').click();
    await page.getByText('Select Locales').click();
    await page.locator('(//span[contains(text(), "English (United States)")])[2]').click();
    await page.getByRole('button', { name: 'Save' }).click();
    await page.getByText('Credential Updated Success');
    await expect(page.getByText('Credential Updated Success')).toBeVisible();
  });

  test('Delete the credential', async ({ page }) => {
    await expect(page.locator('#app')).toContainText('http://quickstart-c2b9e6cf.myshopify.com');
    await expect(page.getByTitle('Delete')).toBeVisible();
    await page.getByTitle('Delete').click();
    await expect(page.getByText('Are you sure you want to')).toBeVisible();
    await expect(page.locator('#app')).toContainText('Are you sure you want to delete?');
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText('Credential Deleted Success')).toBeVisible();
    await expect(page.locator('#app')).toContainText('');
    await expect(page.getByText('No Records Available.')).toBeVisible();
    await expect(page.locator('#app')).toContainText('No Records Available.');
  });

});
