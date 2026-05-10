const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

/**
 * Create a simple product and return to the product listing.
 */
async function createSimpleProduct(adminPage, sku) {
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.waitForLoadState('networkidle');

  // Select type
  const typeWrapper = adminPage.locator('input[name="type"]').locator('..');
  await typeWrapper.locator('.multiselect__tags').click();
  await typeWrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
  await adminPage.getByRole('option', { name: 'Simple' }).first().click();
  await adminPage.keyboard.press('Escape');

  // Select attribute family (pick first available)
  const familyWrapper = adminPage.locator('input[name="attribute_family_id"]').locator('..');
  await familyWrapper.locator('.multiselect__tags').click();
  await familyWrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
  await familyWrapper
    .locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
    .first()
    .click();
  await adminPage.keyboard.press('Escape');

  await adminPage.locator('input[name="sku"]').fill(sku);
  await adminPage.getByRole('button', { name: 'Save Product' }).click();
  await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await adminPage.waitForLoadState('networkidle').catch(() => {});
  return sku;
}

/**
 * Delete a product by SKU.
 */
async function deleteProductBySku(adminPage, sku) {
  await navigateTo(adminPage, 'products');
  const searchInput = adminPage.getByPlaceholder('Search').first();
  await searchInput.waitFor({ state: 'visible', timeout: 30000 });
  await searchInput.fill(sku);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('load');
  await adminPage.waitForTimeout(500);

  const deleteIcon = adminPage.locator('span[title="Delete"]').first();
  const visible = await deleteIcon.isVisible({ timeout: 3000 }).catch(() => false);
  if (!visible) return;
  await deleteIcon.click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.waitForLoadState('networkidle').catch(() => {});
}

/**
 * Call the product datagrid endpoint with given filter params and return the response.
 */
async function getDatagridResponse(adminPage, params) {
  const url = new URL('/admin/catalog/products', adminPage.url());
  for (const [key, value] of Object.entries(params)) {
    if (Array.isArray(value)) {
      value.forEach(v => url.searchParams.append(`${key}[]`, v));
    } else {
      url.searchParams.set(key, value);
    }
  }

  return adminPage.request.get(url.toString(), {
    headers: { 'X-Requested-With': 'XMLHttpRequest', accept: 'application/json' },
  });
}

test.describe('Product Grid Filter - PostgreSQL compatibility', () => {
  test('38 - combined search (all) and status filter returns 200 not 500', async ({ adminPage }) => {
    test.setTimeout(90000);
    const sku = `filtst-${generateUid()}`;
    await createSimpleProduct(adminPage, sku);

    await navigateTo(adminPage, 'products');
    await adminPage.waitForLoadState('networkidle');

    // Request the DataGrid with both "all" (search) and "status" filters combined.
    // Before the fix this generated broken SQL that caused a 500 in PostgreSQL.
    const response = await getDatagridResponse(adminPage, {
      all: sku,
      status: ['1'],
    });

    expect(response.status()).toBe(200);

    // Cleanup
    await deleteProductBySku(adminPage, sku);
  });

  test('39 - combined search (all) and type filter returns 200 not 500', async ({ adminPage }) => {
    test.setTimeout(90000);
    const sku = `filtty-${generateUid()}`;
    await createSimpleProduct(adminPage, sku);

    await navigateTo(adminPage, 'products');
    await adminPage.waitForLoadState('networkidle');

    // "all" + "type" combination
    const response = await getDatagridResponse(adminPage, {
      all: sku,
      type: ['simple'],
    });

    expect(response.status()).toBe(200);

    // Cleanup
    await deleteProductBySku(adminPage, sku);
  });

  test('40 - "all" search in product grid returns matching product', async ({ adminPage }) => {
    test.setTimeout(90000);
    const sku = `srchall-${generateUid()}`;
    await createSimpleProduct(adminPage, sku);

    await navigateTo(adminPage, 'products');
    await adminPage.waitForLoadState('networkidle');

    const searchInput = adminPage.getByPlaceholder('Search').first();
    await searchInput.waitFor({ state: 'visible', timeout: 30000 });
    await searchInput.fill(sku);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('load');
    await adminPage.waitForTimeout(1000);

    // The search result should show 1 result
    await expect(adminPage.locator('#app').getByText('1 Results')).toBeVisible({ timeout: 20000 });
    await expect(adminPage.locator('#app').getByText(sku)).toBeVisible();

    // Cleanup
    await deleteProductBySku(adminPage, sku);
  });
});
