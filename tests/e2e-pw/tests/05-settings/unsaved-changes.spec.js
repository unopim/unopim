const { test, expect } = require('../../utils/fixtures');
const { clickSave } = require('../../utils/helpers');

test.describe('Unsaved changes bar', () => {
  const URL = '/admin/configuration/system-settings/system.email';

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

    await field.fill(original + 'X');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    await adminPage.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
    await expect(bar(adminPage)).toBeHidden({ timeout: 10000 });
    await expect(field).toHaveValue(original);

    await field.fill(original + 'Y');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });
    await clickSave(adminPage, 'Save changes');
    await adminPage.waitForLoadState('networkidle').catch(() => {});

    await gotoSettings(adminPage);
    await expect(bar(adminPage)).toBeHidden();
    await expect(firstField(adminPage)).toHaveValue(original + 'Y');

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

    const inFormSave = field.locator(
      'xpath=ancestor::*[contains(@class,"unsaved-root")][1]//button[@type="submit"]',
    );
    await expect(inFormSave).toBeHidden();

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

    await adminPage.locator('a[href$="/admin/dashboard"]').first().click({ timeout: 5000 });
    await expect(adminPage.getByText('Leave this page?', { exact: false })).toBeVisible({ timeout: 5000 });
    expect(nativeDialog).toBe(false);
    expect(adminPage.url()).toContain('configuration/system-settings');

    await adminPage.getByRole('button', { name: 'Stay on page' }).click();
    await expect(adminPage.getByText('Leave this page?', { exact: false })).toBeHidden();
    expect(adminPage.url()).toContain('configuration/system-settings');

    await adminPage.locator('a[href$="/admin/dashboard"]').first().click({ timeout: 5000 });
    await adminPage.getByRole('button', { name: 'Leave' }).click({ timeout: 5000 });
    await adminPage.waitForURL('**/admin/dashboard', { timeout: 15000 });
    expect(nativeDialog).toBe(false);
  });

  test('confirm survives when the tracked page was entered via SPA navigation', async ({ adminPage }) => {
    let nativeDialog = false;
    adminPage.on('dialog', async (d) => { nativeDialog = true; await d.dismiss().catch(() => {}); });

    await adminPage.goto('/admin/dashboard', { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});

    await adminPage.locator('a[href$="/admin/configuration/system-settings"]').first().click({ timeout: 15000 });
    await adminPage.locator('a[href$="/admin/configuration/system-settings/system.email"]').first().click({ timeout: 15000 });

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();

    await field.fill(original + 'X');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    const urlBefore = adminPage.url();

    await adminPage.locator('a[href$="/admin/dashboard"]').first().click({ timeout: 5000 });

    const confirm = adminPage.getByText('Leave this page?', { exact: false });
    await expect(confirm).toBeVisible({ timeout: 5000 });

    await adminPage.waitForTimeout(2000);
    await expect(confirm).toBeVisible();
    expect(adminPage.url()).toBe(urlBefore);

    await adminPage.getByRole('button', { name: 'Stay on page' }).click();
    await expect(confirm).toBeHidden();
    expect(adminPage.url()).toBe(urlBefore);
    await expect(field).toHaveValue(original + 'X');
    expect(nativeDialog).toBe(false);

    await adminPage.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
  });

  test('a successful save redirect does not raise a spurious unsaved prompt', async ({ adminPage }) => {
    await gotoSettings(adminPage);

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();

    await field.fill(original + 'Q');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    await clickSave(adminPage, 'Save changes');
    await adminPage.waitForLoadState('networkidle').catch(() => {});

    await expect(adminPage.getByText('Leave this page?', { exact: false })).toBeHidden();

    await gotoSettings(adminPage);
    await firstField(adminPage).fill(original);
    await clickSave(adminPage, 'Save changes');
    await adminPage.waitForLoadState('networkidle').catch(() => {});
  });

  test('navigating away while dirty registers a beforeunload guard', async ({ adminPage }) => {
    await gotoSettings(adminPage);

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();
    await field.fill(original + 'Z');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    const guarded = await adminPage.evaluate(() => {
      const e = new Event('beforeunload', { cancelable: true });
      window.dispatchEvent(e);
      return e.defaultPrevented;
    });
    expect(guarded).toBe(true);

    await adminPage.getByRole('button', { name: 'Discard' }).click();
    await adminPage.locator('button.danger-button').first().click().catch(() => {});
  });

  test('confirm stays open on the user edit page after a save fails validation', async ({ adminPage }) => {
    await adminPage.goto('/admin/settings/users/edit/1', { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});

    const email = adminPage.locator('input[name="email"]');
    await email.waitFor({ state: 'visible', timeout: 15000 });
    const originalEmail = await email.inputValue();

    await email.fill('not-an-email');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    await clickSave(adminPage, 'Save changes');

    await expect(bar(adminPage)).toBeVisible({ timeout: 15000 });
    await expect(adminPage).toHaveURL(/\/settings\/users\/edit\/1/);

    const urlBefore = adminPage.url();

    await adminPage.locator('a[href$="/admin/settings/users"]:visible').first().click({ timeout: 10000 });

    const confirm = adminPage.getByText('Leave this page?', { exact: false });
    await expect(confirm).toBeVisible({ timeout: 10000 });

    await adminPage.waitForTimeout(3000);
    await expect(confirm).toBeVisible();
    expect(adminPage.url()).toBe(urlBefore);

    await adminPage.getByRole('button', { name: 'Stay on page' }).click();
    await expect(confirm).toBeHidden();
    expect(adminPage.url()).toBe(urlBefore);

    await email.fill(originalEmail);
  });
});
