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

test('a queued select-all action flashes its message and reloads the grid', async ({ page }) => {
  test.setTimeout(120000);

  await selectAllMatching(page);

  let requests = 0;

  await page.route('**/admin/catalog/products/mass-update', async (route) => {
    requests++;

    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ message: 'Status update queued' }) });
  });

  await page.getByRole('button', { name: 'Select Action' }).click();
  await page.getByText('Update Status', { exact: true }).hover();
  await page.getByText('Enable', { exact: true }).click();

  const reload = page.waitForResponse((response) => response.url().includes('/admin/catalog/products?')
    && response.request().method() === 'GET', { timeout: 90000 });

  await page.getByRole('button', { name: 'Agree', exact: true }).click();

  await expect(page.getByText('Status update queued', { exact: true })).toBeVisible();

  expect((await reload).ok()).toBeTruthy();
  await expect(page.getByRole('button', { name: 'Filter' })).toBeVisible();
  expect(requests).toBe(1);
});

test('quick export with every matching product selected is queued from the grid filters', async ({ page }) => {
  test.setTimeout(120000);

  await selectAllMatching(page);

  let payload = null;

  await page.route('**/admin/catalog/products/quick-export/queue', async (route) => {
    payload = route.request().postDataJSON();

    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ message: 'Export queued' }) });
  });

  await page.getByRole('button', { name: 'Quick Export' }).first().click();
  await page.getByRole('button', { name: 'Quick Export', exact: true }).last().click();

  await expect(page.getByText('Export queued', { exact: true })).toBeVisible();

  expect(payload.select_all).toBe(1);
  expect(payload.format).toBe('xls');
  expect(payload.productIds).toBeUndefined();
  expect(payload.indices).toBeUndefined();
});

test('mass delete with every matching product selected sends the grid filters after the delete confirmation', async ({ page }) => {
  test.setTimeout(120000);

  await selectAllMatching(page);

  let payload = null;

  await page.route('**/admin/catalog/products/mass-delete', async (route) => {
    payload = route.request().postDataJSON();

    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ message: 'Deletion queued' }) });
  });

  await page.getByRole('button', { name: 'Select Action' }).click();
  await page.getByText('Delete', { exact: true }).first().click();

  await expect(page.getByText('Confirm Deletion', { exact: true })).toBeVisible();

  await page.getByRole('button', { name: 'Delete', exact: true }).last().click();

  await expect(page.getByText('Deletion queued', { exact: true })).toBeVisible();

  expect(payload.select_all).toBe(1);
  expect(payload.indices).toBeUndefined();
  expect(payload.sort).toBeUndefined();
});
