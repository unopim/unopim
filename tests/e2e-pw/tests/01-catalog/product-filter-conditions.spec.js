const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

async function openFilterDrawer(adminPage) {
  await navigateTo(adminPage, 'products');
  await adminPage.evaluate(() => localStorage.removeItem('datagrids'));
  await adminPage.reload({ waitUntil: 'networkidle' });
  await adminPage.getByText('Filter', { exact: true }).click();
}

async function addAttributeFilter(adminPage, index, label) {
  await adminPage.getByRole('button', { name: 'Add Filter' }).click();
  await adminPage.locator('p').filter({ hasText: new RegExp(`^${label}$`) }).first().click();

  return adminPage.locator(`[data-attribute-filter="${index}"]`);
}

async function chooseFromDropdown(block, hook, optionLabel) {
  await block.locator(hook).click();
  await block.getByRole('listitem').filter({ hasText: new RegExp(`^${optionLabel}$`) }).click();
}

async function resultCount(adminPage) {
  const text = await adminPage.locator('#app').getByText(/\d+ Results?/).first().innerText();

  return parseInt(text, 10);
}

test.describe('Product DataGrid attribute filters', () => {

  test('Adding an attribute renders operator and value inputs, not a bare input', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await expect(price.locator('[data-filter-operator]')).toBeVisible();
    await expect(price.locator('[data-filter-value]')).toBeVisible();

    await expect(price.locator('[data-filter-currency]')).toBeVisible();
  });

  test('Operator uses the styled dropdown, not a native select', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await expect(price.locator('select')).toHaveCount(0);

    await price.locator('[data-filter-operator]').click();

    await expect(price.getByRole('listitem').filter({ hasText: /^Greater than$/ })).toBeVisible();
  });

  test('A price offers numeric operators', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await price.locator('[data-filter-operator]').click();

    for (const operator of ['Equals', 'Less than', 'Greater than', 'Between', 'Is empty', 'Is not empty']) {
      await expect(price.getByRole('listitem').filter({ hasText: new RegExp(`^${operator}$`) })).toBeVisible();
    }
  });

  test('Greater than actually narrows the results', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const before = await resultCount(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await chooseFromDropdown(price, '[data-filter-currency]', 'US Dollar');
    await chooseFromDropdown(price, '[data-filter-operator]', 'Greater than');
    await price.locator('[data-filter-value]').fill('100');

    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');

    const after = await resultCount(adminPage);

    expect(after).toBeGreaterThan(0);
    expect(after).toBeLessThan(before);
  });

  test('Less than actually narrows the results', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const before = await resultCount(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await chooseFromDropdown(price, '[data-filter-currency]', 'US Dollar');
    await chooseFromDropdown(price, '[data-filter-operator]', 'Less than');
    await price.locator('[data-filter-value]').fill('100');

    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');

    const after = await resultCount(adminPage);

    expect(after).toBeGreaterThan(0);
    expect(after).toBeLessThan(before);
  });

  test('Between shows a second value input', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await chooseFromDropdown(price, '[data-filter-operator]', 'Between');

    await expect(price.locator('[data-filter-value]')).toBeVisible();
    await expect(price.locator('[data-filter-value2]')).toBeVisible();
  });

  test('Switching from Between back to a single-value operator drops the second input', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await chooseFromDropdown(price, '[data-filter-operator]', 'Between');
    await expect(price.locator('[data-filter-value2]')).toBeVisible();

    await chooseFromDropdown(price, '[data-filter-operator]', 'Greater than');

    await expect(price.locator('[data-filter-value]')).toBeVisible();
    await expect(price.locator('[data-filter-value2]')).toHaveCount(0);
  });

  test('Is empty needs no value input', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await chooseFromDropdown(price, '[data-filter-operator]', 'Is empty');

    await expect(price.locator('[data-filter-value]')).toHaveCount(0);
  });

  test('An applied condition survives a reload', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await chooseFromDropdown(price, '[data-filter-currency]', 'US Dollar');
    await chooseFromDropdown(price, '[data-filter-operator]', 'Greater than');
    await price.locator('[data-filter-value]').fill('100');

    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');

    const applied = await resultCount(adminPage);

    await adminPage.reload({ waitUntil: 'networkidle' });
    await adminPage.getByText('Filter', { exact: true }).click();

    const reopened = adminPage.locator('[data-attribute-filter="price"]');

    await expect(reopened.locator('[data-filter-operator]')).toContainText('Greater than');
    await expect(reopened.locator('[data-filter-currency]')).toContainText('US Dollar');
    await expect(reopened.locator('[data-filter-value]')).toHaveValue('100');

    expect(await resultCount(adminPage)).toBe(applied);
  });

  test('Removing the filter restores the full list', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const before = await resultCount(adminPage);

    const price = await addAttributeFilter(adminPage, 'price', 'Price');

    await chooseFromDropdown(price, '[data-filter-currency]', 'US Dollar');
    await chooseFromDropdown(price, '[data-filter-operator]', 'Greater than');
    await price.locator('[data-filter-value]').fill('100');

    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');

    expect(await resultCount(adminPage)).toBeLessThan(before);

    await adminPage.getByText('Filter', { exact: true }).click();
    await adminPage.locator('[data-attribute-filter="price"] .icon-cancel').click();
    await adminPage.locator('.primary-button').filter({ hasText: 'Apply' }).click();
    await adminPage.waitForLoadState('networkidle');

    expect(await resultCount(adminPage)).toBe(before);
  });
});
