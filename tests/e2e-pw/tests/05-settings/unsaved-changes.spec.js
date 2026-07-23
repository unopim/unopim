const { test, expect } = require('../../utils/fixtures');
const { clickSave } = require('../../utils/helpers');

/**
 * Global unsaved-changes system — verified on the system-settings page, whose
 * SMTP/Debug config forms are plain POST forms with text fields.
 *
 * Field selection is scoped to `.unsaved-root` (the tracker wrapper) so the
 * global header search box is never picked up.
 */
test.describe('Unsaved changes bar', () => {
  const URL = '/admin/configuration/system/system.email';

  const gotoSettings = (page) =>
    page.goto(URL, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});

  const bar = (page) => page.getByText('You have unsaved changes');
  const firstField = (page) =>
    page.locator('.unsaved-root input[type="text"], .unsaved-root textarea').first();

  test('bar appears on edit, Discard reverts, Save persists', async ({ adminPage }) => {
    await gotoSettings(adminPage);

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();

    // Edit → bar appears.
    await field.fill(original + 'X');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    // Discard → bar gone, value restored.
    await adminPage.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
    await expect(bar(adminPage)).toBeHidden({ timeout: 10000 });
    await expect(field).toHaveValue(original);

    // Edit again → Save changes → reload → persisted, bar gone.
    await field.fill(original + 'Y');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });
    await clickSave(adminPage, 'Save changes');
    await adminPage.waitForLoadState('networkidle').catch(() => {});

    await gotoSettings(adminPage);
    await expect(bar(adminPage)).toBeHidden();
    await expect(firstField(adminPage)).toHaveValue(original + 'Y');

    // Cleanup: restore original.
    await firstField(adminPage).fill(original);
    await clickSave(adminPage, 'Save changes');
    await adminPage.waitForLoadState('networkidle').catch(() => {});
  });

  test('subtitle counts the modified section', async ({ adminPage }) => {
    await gotoSettings(adminPage);

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();

    await field.fill(original + 'S');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });
    await expect(adminPage.getByText(/1 (section|field)s? modified/)).toBeVisible({ timeout: 10000 });

    await adminPage.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
  });

  test('dirty field shows an Unsaved chip that clears on discard', async ({ adminPage }) => {
    await gotoSettings(adminPage);

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();

    await field.fill(original + 'C');
    const group = field.locator('xpath=ancestor::*[@data-control-group][1]');
    await expect(group.locator('.unsaved-badge').first()).toBeVisible({ timeout: 10000 });

    await adminPage.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
    await expect(group.locator('.unsaved-badge').first()).toBeHidden({ timeout: 10000 });
  });

  test('tracked form has no in-form save button; only the bar saves', async ({ adminPage }) => {
    await gotoSettings(adminPage);

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();

    // The tracked form's own submit button is removed entirely — even while pristine.
    const inFormSave = field.locator(
      'xpath=ancestor::*[contains(@class,"unsaved-root")][1]//button[@type="submit"]',
    );
    await expect(inFormSave).toBeHidden();

    // Editing raises the bar, whose "Save changes" is the sole save control.
    await field.fill(original + 'H');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });
    await expect(inFormSave).toBeHidden();
    await expect(adminPage.getByRole('button', { name: 'Save changes' })).toBeVisible();

    await adminPage.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
    await expect(inFormSave).toBeHidden();
  });

  test('clicking a menu link while dirty shows the in-app confirm, not a silent reload', async ({ adminPage }) => {
    let nativeDialog = false;
    adminPage.on('dialog', async (d) => { nativeDialog = true; await d.dismiss().catch(() => {}); });

    await gotoSettings(adminPage);
    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    await field.click();
    await field.type('x');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    // Click the Dashboard sidebar link → intercepted with UnoPim's confirm modal.
    await adminPage.locator('a[href$="/admin/dashboard"]').first().click({ timeout: 5000 });
    await expect(adminPage.getByText('Leave this page?', { exact: false })).toBeVisible({ timeout: 5000 });
    expect(nativeDialog).toBe(false);
    expect(adminPage.url()).toContain('configuration/system');

    // Disagree ("Stay on page") keeps you on the page.
    await adminPage.getByRole('button', { name: 'Stay on page' }).click();
    await expect(adminPage.getByText('Leave this page?', { exact: false })).toBeHidden();
    expect(adminPage.url()).toContain('configuration/system');

    // Agree ("Leave") navigates away (no native prompt thanks to the bypass).
    await adminPage.locator('a[href$="/admin/dashboard"]').first().click({ timeout: 5000 });
    await adminPage.getByRole('button', { name: 'Leave' }).click({ timeout: 5000 });
    await adminPage.waitForURL('**/admin/dashboard', { timeout: 15000 });
    expect(nativeDialog).toBe(false);
  });

  test('navigating away while dirty registers a beforeunload guard', async ({ adminPage }) => {
    await gotoSettings(adminPage);

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();
    await field.fill(original + 'Z');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    // Automation suppresses the native dialog; assert a cancelable beforeunload
    // handler is registered (defaultPrevented) while dirty.
    const guarded = await adminPage.evaluate(() => {
      const e = new Event('beforeunload', { cancelable: true });
      window.dispatchEvent(e);
      return e.defaultPrevented;
    });
    expect(guarded).toBe(true);

    // Cleanup.
    await adminPage.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
  });
});
