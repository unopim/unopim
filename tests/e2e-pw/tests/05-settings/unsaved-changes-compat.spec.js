const { test, expect } = require('../../utils/fixtures');

/**
 * Cross-page compatibility for the global unsaved-changes system. For each
 * tracked page it makes a real (trusted) edit to the first text field in a
 * tracked form and asserts the bar appears — while checking that the page
 * shows NO false "unsaved" state on load and throws no uncaught JS error.
 */
test.describe('Unsaved changes — cross-page', () => {
  const PAGES = [
    { name: 'attribute create', url: '/admin/catalog/attributes/create' },
    { name: 'channel edit', url: '/admin/settings/channels/edit/1' },
  ];

  for (const pageDef of PAGES) {
    test(`${pageDef.name}: clean on load, bar on real edit, no JS errors`, async ({ adminPage }) => {
      const errors = [];
      adminPage.on('pageerror', (e) => errors.push(e.message));

      await adminPage.goto(pageDef.url, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
      await adminPage.waitForTimeout(1500);

      // No false positive: custom selects/multiselects populating after mount must
      // NOT raise the bar before the user touches anything.
      await expect(adminPage.getByText('You have unsaved changes')).toBeHidden();

      // A real edit to a text field raises the bar. Exclude multiselect search
      // inputs (hidden until their dropdown opens) and pick a visible field.
      const field = adminPage
        .locator('.unsaved-root input[type="text"]:not([readonly]):not(.multiselect__input):visible, .unsaved-root textarea:visible')
        .first();
      await field.waitFor({ state: 'visible', timeout: 15000 });
      await field.click();
      await field.type('x');

      await expect(adminPage.getByText('You have unsaved changes').first()).toBeVisible({ timeout: 10000 });

      await adminPage.getByRole('button', { name: 'Discard' }).first().click().catch(() => {});
      await adminPage.locator('button.danger-button').first().click().catch(() => {});
      expect(errors, `JS errors on ${pageDef.name}: ${errors.join(' | ')}`).toHaveLength(0);
    });
  }

  test('product edit: tracked, clean on load, non-text edit raises the bar, single save button', async ({ adminPage }) => {
    const errors = [];
    adminPage.on('pageerror', (e) => errors.push(e.message));

    await adminPage.goto('/admin/catalog/products/edit/1', { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
    await adminPage.waitForTimeout(2000);

    // Tracked form present, and custom widgets populating after mount must NOT
    // falsely raise the bar.
    await expect(adminPage.locator('.unsaved-root').first()).toBeAttached();
    await expect(adminPage.getByText('You have unsaved changes')).toBeHidden();

    // The legacy in-form save button is removed on tracked forms, even while clean.
    const inFormSave = adminPage.getByRole('button', { name: /Save Product/i });
    await expect(inFormSave).toBeHidden();

    // A non-text edit (a toggle switch) is a real change → bar appears. The switch's
    // input is sr-only with a label overlay, so force the click on the input itself.
    const checkbox = adminPage.locator('.unsaved-root input[type="checkbox"]').first();
    await checkbox.waitFor({ state: 'attached', timeout: 15000 });
    await checkbox.click({ force: true });

    await expect(adminPage.getByText('You have unsaved changes').first()).toBeVisible({ timeout: 10000 });

    // Only one save button: the bar's "Save changes".
    await expect(inFormSave).toBeHidden();
    await expect(adminPage.getByRole('button', { name: 'Save changes' })).toBeVisible();

    await adminPage.getByRole('button', { name: 'Discard' }).first().click().catch(() => {});
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
    expect(errors, `JS errors on product edit: ${errors.join(' | ')}`).toHaveLength(0);
  });

  test('account edit: header save button (associated via form=) is removed, only the bar saves', async ({ adminPage }) => {
    const errors = [];
    adminPage.on('pageerror', (e) => errors.push(e.message));

    await adminPage.goto('/admin/account', { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
    await adminPage.waitForTimeout(1500);

    await expect(adminPage.locator('.unsaved-root').first()).toBeAttached();
    await expect(adminPage.getByText('You have unsaved changes')).toBeHidden();

    // The account save button lives OUTSIDE the <form>, associated via form="account-edit-form".
    // The tracker must still remove it so the bar's "Save changes" is the only save button.
    const headerSave = adminPage.locator('button[form="account-edit-form"]');
    await expect(headerSave).toHaveCount(0);

    const field = adminPage
      .locator('.unsaved-root input[type="text"]:not([readonly]):visible, .unsaved-root input[type="email"]:visible')
      .first();
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();
    await field.click();
    await field.type('X');

    await expect(adminPage.getByText('You have unsaved changes').first()).toBeVisible({ timeout: 10000 });

    // Still exactly one save button: the bar's "Save changes".
    await expect(adminPage.locator('button[form="account-edit-form"]')).toHaveCount(0);
    await expect(adminPage.getByRole('button', { name: 'Save changes' })).toBeVisible();

    // Revert without persisting.
    await field.fill(original);
    await adminPage.getByRole('button', { name: 'Discard' }).first().click().catch(() => {});
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
    expect(errors, `JS errors on account edit: ${errors.join(' | ')}`).toHaveLength(0);
  });
});
