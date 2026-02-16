import { test, expect } from '@playwright/test';
test.use({ storageState: 'storage/auth.json' });
// test.use({ launchOptions: { slowMo: 500 } }); // Slow down actions by 1 second
// Reuse login session

test.describe('Shopify Metafield definitions Page', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the Shopify Credentials Page
    await page.goto('admin/shopify/metafields');
  });

  test('Verify Shopify Metafield definitions page title is visible', async ({ page }) => {
    await expect(page.locator('p:text("Metafield definitions")')).toBeVisible();
  });

  test('Click on Add definition button', async ({ page }) => {
    await page.locator('button.primary-button:has-text("Add definition")').click();
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
    const headers = ['Used For', 'Unopim Attribute', 'Definition name'];
    for (const header of headers) {
      await expect(page.getByText(header)).toBeVisible({ timeout: 10000 });
    }
  });

  test('Verify No Records Available message', async ({ page }) => {
    await expect(page.locator('p:text("No Records Available.")')).toBeVisible();
  });
});

test.describe.serial('Shopify Create Metafield Definition Page', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the Shopify Credentials Page
    await page.goto('admin/shopify/metafields');
  });

  test('Checked Metafield Definition used for Product form and validation', async ({ page }) => {
    await page.getByRole('button', { name: 'Add definition' }).click();
    await page.evaluate(() => {
      const bar = document.querySelector('.phpdebugbar');
      if (bar) bar.style.display = 'none';
    });
    await page.getByRole('button', { name: /Save/ }).click();
    await expect(page.locator('#app')).toContainText('The Metafield definitions field is required');
    await expect(page.locator('#app')).toContainText('The UnoPim Attribute field is required');
    await expect(page.locator('#app')).toContainText('The Type field is required');
    await expect(page.locator('#app')).toContainText('The Definition Name field is required');
    await expect(page.locator('#app')).toContainText('The Namespace and key field is required');
    await page.locator('#ownerType .multiselect__select').click();
    await page.locator('#ownerType .multiselect__element span').getByText('Products', { exact: true }).click();
    await page.locator('#code .multiselect__select').click();
    await page.getByText('Name', { exact: true }).click();
    await page.locator('#type .multiselect__select').click();
    await page.getByText('Single line text', { exact: true }).click();
    await page.locator('input[name="attribute"]').fill('Name');
    await page.locator('input[name="name_space_key"]').fill('custom.name');
    await page.evaluate(() => {
      const bar = document.querySelector('.phpdebugbar');
      if (bar) bar.style.display = 'none';
    });
    await page.getByRole('button', { name: /Save/ }).click();
    await page.getByText('Create Metafield Definition successfully');
    await expect(page.getByText('Create Metafield Definition successfully')).toBeVisible();

  });

  test('Metafield Definition edit required validation', async ({ page }) => {
    await expect(page.getByTitle('Edit')).toBeVisible();
    await page.getByTitle('Edit').click();
    const currentUrl = page.url();
    await expect(currentUrl).toMatch(/\/admin\/shopify\/metafields\/edit\/\d+$/);
    const pLocator = page.locator('label', { hasText: 'Used For' });
    const multiselect = pLocator.locator('..').locator('.multiselect');
    const hasDisabledClass = await multiselect.evaluate(el => el.classList.contains('multiselect--disabled'));
    expect(hasDisabledClass).toBe(true);
    const input = page.locator('input[name="code"]');
    await expect(input).toHaveValue('name');
    await expect(input).toHaveAttribute('readonly', '');
    const ContentTypeName = page.locator('input[name="ContentTypeName"]');
    await expect(ContentTypeName).toBeDisabled();
    await expect(ContentTypeName).toHaveValue('Single line text');

    const definitionNameInput = page.locator('input[name="attribute"]');
    await expect(definitionNameInput).toBeVisible();
    await definitionNameInput.fill('');
    await definitionNameInput.fill('New Definition Name');
    const nsKeyInput = page.locator('input[name="name_space_key"]');
    await expect(nsKeyInput).toBeVisible();
    await expect(nsKeyInput).toBeDisabled();
    await expect(nsKeyInput).toHaveValue('custom.name');

    const descriptionInput = page.locator('input[name="description"]');
    await expect(descriptionInput).toBeVisible();
    await descriptionInput.fill('');
    await descriptionInput.fill('Test metafield description');

    const filterTogglePin = page.locator('input#pin');
    const toggleLabelPin = page.locator('label[for="pin"]');
    await expect(filterTogglePin).toBeChecked();
    await toggleLabelPin.click();
    await expect(filterTogglePin).not.toBeChecked();
    await toggleLabelPin.click();
    await expect(filterTogglePin).toBeChecked();

    const filterToggle = page.locator('input#adminFilterable');
    const toggleLabel = page.locator('label[for="adminFilterable"]');
    await expect(filterToggle).not.toBeChecked();
    await toggleLabel.click();
    await expect(filterToggle).toBeChecked();
    await toggleLabel.click();
    await expect(filterToggle).not.toBeChecked();


    const filterToggleSmart = page.locator('input#smartCollectionCondition');
    const toggleLabelSmart = page.locator('label[for="smartCollectionCondition"]');
    await expect(filterToggleSmart).not.toBeChecked();
    await toggleLabelSmart.click();
    await expect(filterToggleSmart).toBeChecked();
    await toggleLabelSmart.click();
    await expect(filterToggleSmart).not.toBeChecked();

    const filterToggleStore = page.locator('input#storefronts');
    const toggleLabelStore = page.locator('label[for="storefronts"]');
    await expect(filterToggleStore).toBeChecked();
    await toggleLabelStore.click();
    await expect(filterToggleStore).not.toBeChecked();
    await toggleLabelStore.click();
    await expect(filterToggleStore).toBeChecked();

    await page.getByText('Save').click();
    await page.getByText('MetaField Definition Updated successfully');
    await expect(page.getByText('MetaField Definition Updated successfully')).toBeVisible();
  });

  test('Delete the Metafield Definition', async ({ page }) => {
    await expect(page.locator('#app')).toContainText('Products');
    await expect(page.getByTitle('Delete')).toBeVisible();
    await page.getByTitle('Delete').click();
    await expect(page.getByText('Are you sure you want to')).toBeVisible();
    await expect(page.locator('#app')).toContainText('Are you sure you want to delete?');
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText('Metafield Definition Deleted successfully')).toBeVisible();
    await expect(page.locator('#app')).toContainText('');
    await expect(page.getByText('No Records Available.')).toBeVisible();
    await expect(page.locator('#app')).toContainText('No Records Available.');
  });

});
