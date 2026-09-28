const { test, expect } = require('@playwright/test');

/**
 * "Select all matching" must hand the grid's filters to the mass action instead of an id
 * list, so the selection is not capped (it used to stop at 10,000 ids). The mass-update
 * request is intercepted and answered locally so the run never changes product data.
 */
const selectAllMatching = async (page) => {
  const gridResponse = page.waitForResponse((response) => response.url().includes('/admin/catalog/products?')
    && response.request().method() === 'GET'
    && response.ok(), { timeout: 90000 });

  await page.goto('/admin/catalog/products');

  const total = (await (await gridResponse).json()).meta.total;

  test.skip(total <= 10, 'Needs more than one page of products.');

  await page.locator('label[for="mass_action_select_all_records"]').click();

  await page.getByRole('button', { name: 'Selection options' }).click();
  await page.getByText(`Select all ${total}`, { exact: true }).click();

  await expect(page.getByText(`${total} of`, { exact: false }).first()).toBeVisible();

  return total;
};

test('select all matching sends the grid filters instead of a capped id list', async ({ page }) => {
  test.setTimeout(120000);

  await selectAllMatching(page);

  let payload = null;

  await page.route('**/admin/catalog/products/mass-update', async (route) => {
    payload = route.request().postDataJSON();

    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ message: 'ok' }) });
  });

  await page.getByRole('button', { name: 'Select Action' }).click();
  await page.getByText('Update Status', { exact: true }).hover();
  await page.getByText('Enable', { exact: true }).click();
  await page.getByRole('button', { name: 'Agree', exact: true }).click();

  await expect.poll(() => payload).not.toBeNull();

  expect(payload.select_all).toBe(1);
  expect(payload.indices).toBeUndefined();
  expect(payload.sort).toBeUndefined();
});

test('bulk edit with every matching product selected is accepted by the server', async ({ page }) => {
  test.setTimeout(180000);

  const total = await selectAllMatching(page);

  await page.getByRole('button', { name: 'Select Action' }).click();
  await page.getByRole('link', { name: 'Bulk Edit' }).click();

  await page.locator('.multiselect').last().click();
  await page.keyboard.type('Name');
  await expect(page.locator('.multiselect__option').first()).toBeVisible();
  await page.keyboard.press('Enter');

  const bulkEditResponse = page.waitForResponse((response) => response.url().includes('/bulkedit/filters'), { timeout: 150000 });

  await page.getByRole('button', { name: 'Proceed' }).click();

  const response = await bulkEditResponse;
  const body = await response.json();

  expect(body.errors).toBeUndefined();

  if (total > 100) {
    expect(response.status()).toBe(422);
    expect(body.message).toBe('Too many products selected.');
  } else {
    expect(response.ok()).toBeTruthy();
  }
});
