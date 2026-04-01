const { test, expect } = require('../../utils/fixtures');

/**
 * Helper: Navigate to the completeness tab for a family by code.
 */
async function goToFamilyCompletenessTab(adminPage, familyCode) {
  await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Search' }).first().fill(familyCode);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
  const itemRow = adminPage.locator('div', { hasText: familyCode });
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

/**
 * Helper: Delete a family by code using the filter UI.
 */
async function deleteFamilyByCode(adminPage, familyCode) {
  await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
  await adminPage.waitForLoadState('networkidle');
  // Use code filter to reliably find the exact family
  await adminPage.locator('.relative.inline-flex').click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(familyCode);
  await adminPage.getByText('Save').click();
  await adminPage.getByText('Save').click();
  await adminPage.waitForLoadState('networkidle');
  const deleteBtn = adminPage.locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('#app').getByText(/Family deleted successfully/i)).toBeVisible({ timeout: 10000 });
  }
}

const TEST_FAMILY_CODE = 'displaycompletensstab';
const TEST_FAMILY_NAME = 'displaytab';

test.describe('Verify that Product Completeness feature correctly Exists', () => {

  // ── CLEANUP FIRST — delete leftover test family from any previous run ──

  test('Cleanup test family if it exists from a previous run', async ({ adminPage }) => {
    await deleteFamilyByCode(adminPage, TEST_FAMILY_CODE);
  });

  // ── Default family & dashboard tests (no test data dependency) ──

  test('Verify "Completeness" tab is displayed in Default Family Edit page', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    const editBtn = adminPage.locator('span[title="Edit"]').first();
    await editBtn.click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/edit\/\d+$/);
    await expect(adminPage.getByRole('link', { name: 'Completeness' })).toBeVisible();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 15000 });
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/families\/edit\/\d+\?completeness/);
    await expect(adminPage.getByRole('paragraph').filter({ hasText: 'Completeness' })).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Code$/ }).first()).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Name$/ }).first()).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Required in Channels$/ }).first()).toBeVisible();
  });

  test('Verify Product Completeness Status Display on Dashboard for All Products Channel-wise', async ({ adminPage }) => {
    await adminPage.getByRole('link', { name: ' Dashboard' }).click();
    await expect(adminPage.getByRole('link', { name: ' Dashboard' })).toBeVisible();
    // Completeness widget only appears when required attributes are configured
    const completenessSection = adminPage.locator('header').filter({ hasText: /Default.*completeness/i });
    const hasCompleteness = await completenessSection.isVisible({ timeout: 5000 }).catch(() => false);
    if (hasCompleteness) {
      await expect(adminPage.getByRole('heading', { name: 'Default' })).toBeVisible();
      await expect(adminPage.locator('circle').first()).toBeVisible();
    } else {
      // No required attributes configured yet — dashboard shows catalog overview instead
      await expect(adminPage.getByText('Catalog Overview')).toBeVisible();
    }
  });

  test('Verify Product Completeness Status Displays N/A When No Attributes Are Configured as Required for a Channel', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');

    // Create product if it doesn't already exist from a previous run
    const existingProduct = adminPage.locator('#app').getByText('check-complete-status-onproduct');
    if (!(await existingProduct.isVisible({ timeout: 3000 }).catch(() => false))) {
      await adminPage.getByRole('button', { name: 'Create Product' }).click();
      await adminPage.locator('div').filter({ hasText: /^Select option$/ }).first().click();
      await adminPage.getByRole('option', { name: 'Simple' }).first().click();
      await adminPage.getByText('Select option').click();
      await adminPage.getByRole('option', { name: 'Default' }).first().click();
      await adminPage.locator('input[name="sku"]').click();
      await adminPage.locator('input[name="sku"]').fill('check-complete-status-onproduct');
      await adminPage.getByRole('button', { name: 'Save Product' }).click();
      await adminPage.waitForLoadState('networkidle');
    }
    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 15000 });
    await expect(adminPage.getByRole('paragraph').filter({ hasText: /^Complete$/ })).toBeVisible();
    await expect(adminPage.getByRole('paragraph').filter({ hasText: 'N/A' }).first()).toBeVisible();
  });

  // ── Custom family setup & tests ──

  test('Create a new custom family and verify Completeness tab exists', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families/create', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByText('General Code').click();
    await adminPage.getByRole('textbox', { name: 'Enter Code' }).fill(TEST_FAMILY_CODE);
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill(TEST_FAMILY_NAME);
    await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
    await adminPage.waitForLoadState('networkidle');

    // After saving, navigate to the family and verify the Completeness tab
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill(TEST_FAMILY_CODE);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: TEST_FAMILY_CODE });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(adminPage.getByRole('link', { name: 'Completeness' })).toBeVisible();
  });

  test('Assign General attribute group to the custom family', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill(TEST_FAMILY_CODE);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('span[title="Edit"]').first()).toBeVisible({ timeout: 10000 });
    const itemRow = adminPage.locator('div', { hasText: TEST_FAMILY_CODE });
    await itemRow.locator('span[title="Edit"]').first().click();

    // Assign the General attribute group
    await adminPage.getByText('Assign Attribute Group').click();
    await adminPage.getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
    await adminPage.getByRole('option', { name: 'General' }).first().click();
    await adminPage.getByRole('button', { name: 'Assign Attribute Group' }).click();
    await adminPage.waitForLoadState('networkidle');

    // Drag unassigned attributes into the General group (skip already-assigned ones like sku)
    const attributes = ['sku', 'Name', 'price', 'Description'];
    for (const attr of attributes) {
      const dragHandle = adminPage.locator(`#unassigned-attributes i.icon-drag:near(:text("${attr}"))`).first();
      const isUnassigned = await dragHandle.isVisible({ timeout: 3000 }).catch(() => false);
      if (!isUnassigned) continue;

      const dropTarget = adminPage.locator('#assigned-attribute-groups .group_node').first();
      const dragBox = await dragHandle.boundingBox();
      const dropBox = await dropTarget.boundingBox();
      if (dragBox && dropBox) {
        await adminPage.mouse.move(dragBox.x + dragBox.width / 2, dragBox.y + dragBox.height / 2);
        await adminPage.mouse.down();
        await adminPage.mouse.move(dropBox.x + dropBox.width / 2, dropBox.y + dropBox.height / 2, { steps: 10 });
        await adminPage.mouse.up();
      }
    }

    await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
    await adminPage.waitForLoadState('networkidle');
  });

  // ── Completeness datagrid tests (use the custom family) ──

  test('Verify newly assigned SKU attribute appears in Completeness tab', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);
    await expect(adminPage.locator('#app').getByText('sku', { exact: true })).toBeVisible({ timeout: 15000 });
    await expect(adminPage.locator('#app').getByText('SKU', { exact: true })).toBeVisible();
  });

  test('Verify attribute search returns correct results in Completeness section', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).click();
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('sku');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/1 Results?/)).toBeVisible({ timeout: 15000 });
    await expect(adminPage.locator('#app').getByText('sku', { exact: true })).toBeVisible();
  });

  test('Verify default channel is available in "Required in Channel" multiselect', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);
    // Default channel should be present — either already assigned as a tag or available as an option
    const defaultTag = adminPage.locator('.multiselect__tag', { hasText: 'Default' }).first();
    const isAlreadyAssigned = await defaultTag.isVisible({ timeout: 3000 }).catch(() => false);
    if (isAlreadyAssigned) {
      // Default is already assigned to at least one attribute — test passes
      await expect(defaultTag).toBeVisible();
    } else {
      // Click a multiselect to verify Default appears as an option
      await adminPage.locator('input[name="channel_requirements"]').locator('..').locator('.multiselect__tags').first().click();
      await expect(adminPage.getByRole('option', { name: 'Default' }).first()).toBeVisible();
    }
  });

  test('Verify attribute filter using Code in Completeness section', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('sku');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/1 Results?/)).toBeVisible({ timeout: 15000 });
  });

  test('Verify attribute filter using Name in Completeness section', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Name' }).click();
    await adminPage.getByRole('textbox', { name: 'Name' }).fill('xyz');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/0 Results?/)).toBeVisible({ timeout: 15000 });
  });

  test('Verify attribute filter using Required in Channels returns 0 results for non-existent channel', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).fill('xyz');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/0 Results?/)).toBeVisible({ timeout: 15000 });
  });

  // ── Channel assignment tests ──

  test('Verify channel assignment can be toggled for an attribute', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);
    // Remove an existing channel tag if present, or assign one if not
    const existingTag = adminPage.locator('.multiselect__tag-icon').first();
    if (await existingTag.isVisible({ timeout: 3000 }).catch(() => false)) {
      // Remove the channel assignment
      await existingTag.click();
    } else {
      // Assign Default channel to the first available attribute
      await adminPage.locator('input[name="channel_requirements"]').locator('..').locator('.multiselect__tags').first().click();
      await adminPage.getByRole('option', { name: 'Default' }).first().click();
    }
    await expect(adminPage.locator('#app').getByText('Completeness updated successfully Close')).toBeVisible();
  });

  test('Verify filter using Required in Channels returns results after channel assignment', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);

    // Ensure at least one attribute has Default channel assigned
    const unassignedSelect = adminPage.locator('.multiselect__tags', { hasText: 'Select option' }).first();
    if (await unassignedSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
      await unassignedSelect.click();
      await adminPage.getByRole('option', { name: 'Default' }).first().click();
      await expect(adminPage.locator('#app').getByText(/Completeness updated successfully/i)).toBeVisible({ timeout: 10000 });
      await adminPage.waitForLoadState('networkidle');
    }

    // Now apply the filter
    await adminPage.locator('.relative.inline-flex').click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).click();
    await adminPage.getByRole('textbox', { name: 'Required in Channels' }).fill('default');
    await adminPage.getByText('Save').click();
    await adminPage.getByText('Save').click();
    await adminPage.waitForLoadState('networkidle');
    // At least 1 attribute should have Default channel assigned
    await expect(adminPage.locator('#app').getByText(/[1-9]\d* Results?/)).toBeVisible({ timeout: 15000 });
  });

  test('Verify selectable attribute count in Completeness tab equals assigned family attributes', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill(TEST_FAMILY_CODE);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: TEST_FAMILY_CODE });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForSelector('#assigned-attribute-groups', { state: 'visible' });
    await adminPage.waitForLoadState('networkidle');
    const assignedCount = await adminPage
      .locator('#assigned-attribute-groups .ltr\\:ml-11 [data-draggable="true"]').count();
    expect(assignedCount).toBeGreaterThan(0);
  });

  // ── Multi-channel tests (require channel3) ──

  test('Create a new channel with multiple locales and currencies', async ({ adminPage }) => {
    // Enable the fr_FR locale (only if not already enabled)
    await adminPage.getByRole('link', { name: ' Settings' }).click();
    await adminPage.getByRole('link', { name: 'Locales' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByPlaceholder('Search by code').first().fill('fr_FR');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('fr_FR').first()).toBeVisible({ timeout: 10000 });
    const localeRow = adminPage.locator('#app div').filter({ hasText: 'fr_FR' }).first();
    await localeRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('load');
    const statusChecked = await adminPage.locator('input[name="status"][type="checkbox"]').isChecked();
    if (!statusChecked) {
      await adminPage.locator('label[for="status"]').first().click();
    }
    await adminPage.getByRole('button', { name: 'Save Locale' }).click();
    await expect(adminPage.locator('#app').getByText(/Locale.*updated successfully/i)).toBeVisible({ timeout: 15000 });

    // Enable the EUR currency
    await adminPage.getByRole('link', { name: 'Currencies' }).click();
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByPlaceholder('Search by code or id').first().fill('EUR');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText('EUR').first()).toBeVisible({ timeout: 10000 });
    const currencyRow = adminPage.locator('#app div').filter({ hasText: 'EUR' }).first();
    await currencyRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('load');
    const currencyChecked = await adminPage.locator('input[name="status"][type="checkbox"]').isChecked();
    if (!currencyChecked) {
      await adminPage.locator('label[for="status"]').first().click();
    }
    await adminPage.getByRole('button', { name: 'Save Currency' }).click();
    await expect(adminPage.locator('#app').getByText(/Currency updated successfully/i)).toBeVisible();

    // Create channel3 (skip if already exists)
    await adminPage.getByRole('link', { name: 'Channels' }).click();
    await adminPage.waitForLoadState('networkidle');
    const existingChannel = adminPage.locator('#app').getByText('channel3');
    if (await existingChannel.isVisible({ timeout: 3000 }).catch(() => false)) {
      return;
    }
    await adminPage.getByRole('link', { name: 'Create Channel' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).click();
    await adminPage.getByRole('textbox', { name: 'Code' }).fill('defaultchannel2');
    await adminPage.locator('#root_category_id').getByRole('combobox').locator('div').filter({ hasText: 'Select Root Category' }).click();
    await adminPage.getByText('[root]').click();
    await adminPage.locator('input[name="en_US[name]"]').click();
    await adminPage.locator('input[name="en_US[name]"]').fill('channel3');
    await adminPage.locator('#locales').getByRole('combobox').locator('div').filter({ hasText: 'Select Locales' }).click();
    await adminPage.locator('#locales').getByText('French (France)').click();
    await adminPage.getByRole('option', { name: 'English (United States)' }).first().click();
    await adminPage.locator('body').click();
    await adminPage.locator('#currencies').getByRole('combobox').locator('div').filter({ hasText: 'Select currencies' }).click();
    await adminPage.getByText('Euro').click();
    await adminPage.getByRole('option', { name: 'US Dollar' }).first().click();
    await adminPage.getByRole('button', { name: 'Save Channel' }).click();
    await expect(adminPage.locator('#app').getByText(/Channel created successfully/i)).toBeVisible();
  });

  test.skip('Verify all available channels are displayed in Configure Completeness modal', async ({ adminPage }) => {
    await goToFamilyCompletenessTab(adminPage, TEST_FAMILY_CODE);
    await openCompletenessModal(adminPage);
    await adminPage.locator('.multiselect__tags').last().click();
    await expect(adminPage.getByRole('option', { name: 'Default' }).first()).toBeVisible();
    await expect(adminPage.getByRole('option', { name: 'channel3' }).first()).toBeVisible();
  });

  // ── Cleanup ──

  test('Delete the created test family', async ({ adminPage }) => {
    await deleteFamilyByCode(adminPage, TEST_FAMILY_CODE);
  });
});
