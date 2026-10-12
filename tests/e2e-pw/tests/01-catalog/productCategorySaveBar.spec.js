const { test, expect } = require('../../utils/fixtures');

const PRODUCT_EDIT_ID = process.env.PRODUCT_EDIT_ID || 14;

test.describe('Product Edit - Category Assignment Save Bar (#1234)', () => {
  test.setTimeout(90000);

  test('should show Save changes and Discard after assigning a category', async ({ adminPage }) => {
    await adminPage.goto(`/admin/catalog/products/edit/${PRODUCT_EDIT_ID}`, { waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(1500);

    await expect(adminPage.locator('.unsaved-bar')).toHaveCount(0);

    await adminPage.locator('div.box-shadow').filter({ hasText: 'Categories' }).first().click();
    await adminPage.waitForTimeout(1000);

    const drawer = adminPage.locator('.fixed[data-section-id="categories"]');
    await expect(drawer).toBeVisible();

    const unchecked = drawer.locator('input[name="__categories_tree[]"]:not(:checked)').first();
    const inputId = await unchecked.getAttribute('id');

    await drawer.locator(`label[for="${inputId}"]`).click();
    await adminPage.waitForTimeout(1500);

    const bar = adminPage.locator('.unsaved-bar');
    await expect(bar).toBeVisible();
    await expect(bar.getByRole('button', { name: 'Save changes' })).toBeVisible();
    await expect(bar.getByRole('button', { name: 'Discard' })).toBeVisible();
  });
});
