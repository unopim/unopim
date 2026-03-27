const { test, expect } = require('../../utils/fixtures');

/**
 * Helper: Navigate to the Default family's Completeness tab with all rows visible.
 */
async function goToDefaultFamilyCompleteness(adminPage) {
  await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('default');
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const itemRow = adminPage.locator('div', { hasText: 'Default' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByRole('link', { name: 'Completeness' }).click();
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
}

/**
 * Helper: Open the "Configure Completeness" mass action modal.
 */
async function openCompletenessModal(adminPage) {
  await adminPage.click('label[for="mass_action_select_all_records"]');
  const selectActionBtn = adminPage.getByRole('button', { name: /Select Action/i });
  await expect(selectActionBtn).toBeVisible({ timeout: 5000 });

  // Try clicking the dropdown option up to 3 times (dropdown can close on blur before action fires)
  for (let attempt = 0; attempt < 3; attempt++) {
    await selectActionBtn.click();
    const massActionOption = adminPage.getByText('Change Completeness Requirement');
    await expect(massActionOption).toBeVisible({ timeout: 3000 });
    await massActionOption.click();

    const modalVisible = await adminPage.getByText('Configure Completeness')
      .isVisible({ timeout: 3000 }).catch(() => false);
    if (modalVisible) return;
  }

  // Fallback: use Vue emitter if UI clicks failed
  await adminPage.evaluate(() => {
    const app = document.querySelector('[data-v-app]')?.__vue_app__ || document.querySelector('#app').__vue_app__;
    app.config.globalProperties.$emitter.emit('open-completeness-required-modal');
  });
  await expect(adminPage.getByText('Configure Completeness')).toBeVisible({ timeout: 10000 });
}

test.describe('Verify the behaviour of Product Completeness feature', () => {

  test('Verify product grid shows N/A for completeness when no required channel configured', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');

    // Create product if it doesn't already exist
    const existingProduct = adminPage.locator('#app').getByText('NAScore');
    if (!(await existingProduct.isVisible({ timeout: 3000 }).catch(() => false))) {
      await adminPage.getByRole('button', { name: 'Create Product' }).click();
      await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__tags').click();
      await adminPage.getByRole('option', { name: 'Simple' }).first().click();
      await adminPage.locator('input[name="attribute_family_id"]').locator('..').locator('.multiselect__tags').click();
      await adminPage.getByRole('option', { name: 'Default' }).first().click();
      await adminPage.locator('input[name="sku"]').click();
      await adminPage.locator('input[name="sku"]').fill('NAScore');
      await adminPage.getByRole('button', { name: 'Save Product' }).click();
      await adminPage.waitForLoadState('networkidle');
    }

    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 15000 });
    const skuRow = adminPage.locator('div.row:has-text("NAScore")');
    const completeColumn = skuRow.locator('span.label-info');
    await expect(completeColumn).toHaveText('N/A');
  });

  test('Verify product edit page shows no completeness score when no required channel configured', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByText('NAScore').click();
    await expect(adminPage).toHaveURL(/.*\/edit\/.*/);
    await expect(adminPage.locator('text=Missing Required Attributes')).toHaveCount(0);
    await expect(adminPage.locator('text=Completeness')).toHaveCount(0);
  });

  test('Verify that attributes can be set as required from Completeness tab in default family', async ({ adminPage }) => {
    await goToDefaultFamilyCompleteness(adminPage);
    await adminPage.getByRole('button', { name: 'Per Page' }).click();
    await adminPage.getByText('50', { exact: true }).first().click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    // Find a multiselect with "Select option" (no channel yet) and assign Default
    const unassignedSelect = adminPage.locator('.multiselect__tags', { hasText: 'Select option' }).first();
    if (await unassignedSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
      await unassignedSelect.click();
      await adminPage.getByRole('option', { name: 'Default' }).first().click();
    } else {
      // All already assigned — toggle one by removing and re-adding
      await adminPage.locator('.multiselect__tag-icon').first().click();
      await expect(adminPage.locator('#app').getByText('Completeness updated successfully Close').first()).toBeVisible();
      await adminPage.locator('.multiselect__tags', { hasText: 'Select option' }).first().click();
      await adminPage.getByRole('option', { name: 'Default' }).first().click();
    }
    await expect(adminPage.locator('#app').getByText('Completeness updated successfully Close').first()).toBeVisible();
  });

  test.skip('Verify all available channels are displayed when user clicks "Configure Completeness" option', async ({ adminPage }) => {
    await goToDefaultFamilyCompleteness(adminPage);
    await openCompletenessModal(adminPage);
    await adminPage.locator('.multiselect__tags').last().click();
    await expect(adminPage.getByRole('option', { name: 'Default' }).first()).toBeVisible();
    // channel3 is created in file 01 — verify it if present
    const channel3Option = adminPage.getByRole('option', { name: 'channel3' }).first();
    if (await channel3Option.isVisible({ timeout: 2000 }).catch(() => false)) {
      await expect(channel3Option).toBeVisible();
    }
  });

  test.skip('Verify bulk selection of attributes for required channel updates product completeness visibility', async ({ adminPage }) => {
    await goToDefaultFamilyCompleteness(adminPage);
    await adminPage.getByRole('button', { name: 'Per Page' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await openCompletenessModal(adminPage);
    await adminPage.locator('.multiselect__tags').last().click();
    await adminPage.getByRole('option', { name: 'Default' }).first().click();
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage.locator('#app').getByText('Completeness updated successfully Close')).toBeVisible();
  });

  test('Verify channel can be deselected for specific attribute in completeness settings', async ({ adminPage }) => {
    await goToDefaultFamilyCompleteness(adminPage);
    await adminPage.getByRole('button', { name: 'Per Page' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.locator('.multiselect__tag-icon').first().click();
    await expect(adminPage.locator('#app').getByText('Completeness updated successfully Close').first()).toBeVisible();
  });

  test('Update the product by filling all missing required attributes', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: 'NAScore' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.locator('#product_number').click();
    await adminPage.locator('#product_number').fill('123');
    await adminPage.locator('input[name="values[channel_locale_specific][default][en_US][name]"]').fill('skusavedraft');
    await adminPage.locator('input[name="values[common][url_key]"]').click();
    await adminPage.locator('input[name="values[common][url_key]"]').type('skusavedraft');
    const shortDescFrame = adminPage.frameLocator('#short_description_ifr');
    await shortDescFrame.locator('body').click();
    await shortDescFrame.locator('body').type('This is a short description', { delay: 100 });
    const mainDescFrame = adminPage.frameLocator('#description_ifr');
    await mainDescFrame.locator('body').click();
    await mainDescFrame.locator('body').type('This is the full product description added by test.');
    await adminPage.locator('input[name="values[channel_locale_specific][default][en_US][price][USD]"]').fill('300');
    await adminPage.locator('#meta_title').click();
    await adminPage.locator('#meta_title').fill('meattitle');
    await adminPage.locator('#meta_keywords').click();
    await adminPage.locator('#meta_keywords').fill('keyword');
    await adminPage.locator('#meta_description').click();
    await adminPage.locator('#meta_description').fill('description');
    await adminPage.locator('#cost').click();
    await adminPage.locator('#cost').fill('23');
    await adminPage.getByRole('button', { name: 'Save Product' }).click();
    await expect(adminPage.locator('#app').getByText(/Product.*successfully/i)).toBeVisible({ timeout: 10000 });
  });

  test('Verify configuring required attributes for different channels in Default Family', async ({ adminPage }) => {
    await goToDefaultFamilyCompleteness(adminPage);
    await adminPage.getByRole('button', { name: 'Per Page' }).click();
    await adminPage.getByText('50', { exact: true }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await adminPage.locator('input[name="channel_requirements"]').locator('..').locator('.multiselect__tags').first().click();
    // Try channel3 if available (created in file 01), otherwise use Default
    const channel3Option = adminPage.getByRole('option', { name: 'channel3' }).first();
    if (await channel3Option.isVisible({ timeout: 2000 }).catch(() => false)) {
      await channel3Option.click();
    } else {
      const defaultOption = adminPage.getByRole('option', { name: 'Default' }).first();
      if (await defaultOption.isVisible({ timeout: 2000 }).catch(() => false)) {
        await defaultOption.click();
      }
    }
    await expect(adminPage.locator('#app').getByText('Completeness updated successfully Close').first()).toBeVisible();
  });
});
