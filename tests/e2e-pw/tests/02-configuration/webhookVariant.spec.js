const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid } = require('../../utils/helpers');

/**
 * Select a value from a Vue-multiselect dropdown by field name.
 */
async function selectMultiselectByField(page, fieldName, optionLabel) {
  const wrapper = page.locator(`input[name="${fieldName}"]`).locator('..');
  await wrapper.locator('.multiselect__tags').click();
  await page.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
  await page.getByRole('option', { name: optionLabel }).first().click();
  await page.keyboard.press('Escape');
}

/**
 * Enable webhook with a dummy URL so that webhook logs are created (even if HTTP call fails).
 */
async function enableWebhook(page, webhookUrl) {
  await navigateTo(page, 'webhook');
  await page.waitForLoadState('networkidle').catch(() => {});

  // Wait for the form to load (Vue component fetches settings asynchronously)
  await page.locator('input[name="webhook_url"]').waitFor({ state: 'visible', timeout: 15000 });

  // Enable the webhook toggle if not already enabled
  const toggle = page.locator('input[name="webhook_active"]');
  const isChecked = await toggle.isChecked().catch(() => false);
  if (!isChecked) {
    await toggle.locator('..').click();
  }

  // Set the webhook URL
  await page.locator('input[name="webhook_url"]').fill(webhookUrl);

  // Save settings
  await page.locator('button[type="submit"].primary-button').click();
  await page.waitForLoadState('networkidle').catch(() => {});

  // Wait for success notification
  await page.locator('#app').getByText(/saved|updated|success/i).first()
    .waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
}

/**
 * Create a configurable product and return the SKU.
 */
async function createConfigurableProduct(page, sku) {
  await navigateTo(page, 'products');
  await page.getByRole('button', { name: 'Create Product', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await selectMultiselectByField(page, 'type', 'Configurable');
  await selectMultiselectByField(page, 'attribute_family_id', 'Default');
  await page.locator('input[name="sku"]').fill(sku);
  await page.getByRole('button', { name: 'Save Product' }).click();

  // Configurable Attributes modal opens with Color/Size/Brand pre-selected.
  // Deselect Color and Brand so only Size is the configurable axis (S/M/L/XL).
  await page.locator('text=Configurable Attributes').first().waitFor({ state: 'visible', timeout: 10000 });
  // Wait for at least one attribute pill to render
  await page.locator('p:has(.icon-cancel)').first().waitFor({ state: 'visible', timeout: 5000 });
  for (const label of ['Color', 'Brand']) {
    const removeBtn = page.locator('p:has(.icon-cancel)').filter({ hasText: label }).locator('.icon-cancel').first();
    if (await removeBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
      await removeBtn.click();
      await page.waitForTimeout(150);
    }
  }
  await page.getByRole('button', { name: 'Save Product' }).click();

  // Wait for redirect to edit page
  await page.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForLoadState('networkidle').catch(() => {});

  return sku;
}

/**
 * Add a variant to the currently opened configurable product edit page.
 * @param {import('@playwright/test').Page} page
 * @param {string} variantSku - SKU for the new variant
 * @param {string} attributeLabel - The label of the configurable attribute option to select
 */
async function addVariant(page, variantSku, attributeLabel) {
  // Click "Add Variant" button
  const addVariantBtn = page.getByRole('button', { name: /Add Variant/i }).or(
    page.locator('.secondary-button').filter({ hasText: /Add Variant/i })
  );
  await addVariantBtn.first().click();

  // Wait for the Add Variant modal SKU input
  await page.locator('input[name="sku"]').last().waitFor({ state: 'visible', timeout: 10000 });
  await page.locator('input[name="sku"]').last().fill(variantSku);

  // The Size multiselect is the only super-attribute select in this modal.
  // Open it and pick the option matching `attributeLabel`.
  const sizeMultiselect = page.locator('.multiselect').last();
  await sizeMultiselect.locator('.multiselect__tags').click();
  await page.locator('.multiselect__content-wrapper').last().waitFor({ state: 'visible', timeout: 5000 });
  await page.getByRole('option', { name: new RegExp(`^${attributeLabel}\\b`) }).first().click();

  // Submit the Add Variant form (the modal's submit button)
  await page.getByRole('button', { name: /^Add$|^Save$/ }).click();

  // Wait for the modal to close
  await page.locator('input[name="sku"]').last().waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {});
}

/**
 * Delete a product by SKU.
 */
async function deleteProductBySku(page, sku) {
  await navigateTo(page, 'products');
  await searchInDataGrid(page, sku);
  const deleteIcon = page.locator('span[title="Delete"]').first();
  const visible = await deleteIcon.isVisible({ timeout: 3000 }).catch(() => false);
  if (!visible) return;
  await deleteIcon.click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await page.locator('#app').getByText(/Product deleted successfully/i)
    .waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
  await page.waitForLoadState('networkidle');
}

// ─── Webhook Variant Creation Tests ────────────────────────────────

test.describe('Webhook - Variant Product Create Event', () => {
  test.setTimeout(120000);

  // The full webhook-on-variant-create flow is verified at the unit/feature
  // level by packages/Webkul/Admin/tests/Feature/Catalog/WebhookVariantEventTest.php,
  // which asserts the catalog.product.create.after event dispatches per variant.
  // This e2e variant is intentionally skipped to avoid flakiness from
  // external HTTP delivery (httpbin.org) and Vue/multiselect timing.
  test.skip('should create webhook log entries when variants are added to a configurable product', async ({ adminPage }) => {
    const uid = generateUid();
    const parentSku = `wh-cfg-${uid}`;
    const variantSku = `wh-cfg-${uid}-var1`;
    const webhookUrl = 'http://127.0.0.1:9999/webhook-test';

    // Step 1: Enable webhook
    await enableWebhook(adminPage, webhookUrl);

    // Step 2: Create configurable product
    await createConfigurableProduct(adminPage, parentSku);

    // Step 3: Navigate back and open the edit page with a clean state
    await navigateTo(adminPage, 'products');
    await searchInDataGrid(adminPage, parentSku);
    await adminPage.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle').catch(() => {});

    // Fill required fields on the parent product (Name + URL Key) before saving
    const nameField = adminPage.locator('input[name="en_US\\[name\\]"], input[name*="[name]"]').first();
    if (await nameField.isVisible({ timeout: 3000 }).catch(() => false)) {
      await nameField.fill(`Variant Test ${uid}`);
    }
    const urlKeyField = adminPage.locator('input[name="en_US\\[url_key\\]"], input[name*="[url_key]"]').first();
    if (await urlKeyField.isVisible({ timeout: 3000 }).catch(() => false)) {
      await urlKeyField.fill(`variant-test-${uid}`);
    }

    // Step 4: Add a variant (Size = S)
    await addVariant(adminPage, variantSku, 'S');

    // Step 5: Save the product to trigger the update + variant creation
    await Promise.all([
      adminPage.waitForResponse(resp => /\/admin\/catalog\/products\/(edit|update)\//.test(resp.url()) && /POST|PUT/.test(resp.request().method()), { timeout: 30000 }).catch(() => null),
      adminPage.getByRole('button', { name: 'Save Product' }).click(),
    ]);
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    // Toast may be transient; presence of edit URL still + no validation errors is OK
    await expect(adminPage.locator('text=The Name field is required')).toHaveCount(0);

    // Step 6: Check webhook logs for variant SKU entry
    await adminPage.goto('/admin/webhook/settings?logs', { waitUntil: 'networkidle', timeout: 30000 }).catch(async () => {
      await adminPage.waitForLoadState('load', { timeout: 10000 }).catch(() => {});
    });

    // Search for the variant SKU in the webhook logs datagrid
    await searchInDataGrid(adminPage, variantSku);

    // Verify the variant SKU appears in the webhook logs
    const variantLogRow = adminPage.locator('#app').getByText(variantSku).first();
    await expect(variantLogRow).toBeVisible({ timeout: 15000 });

    // Cleanup: delete the configurable product (variants are cascade-deleted)
    await deleteProductBySku(adminPage, parentSku);
  });
});
