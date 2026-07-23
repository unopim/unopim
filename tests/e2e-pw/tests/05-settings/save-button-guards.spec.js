const { test, expect } = require('../../utils/fixtures');

/**
 * Save-button hardening on tracked forms:
 *  - the unsaved-changes bar is the single save control (no duplicate button),
 *  - a slow/large save cannot be double-triggered into multiple AJAX requests.
 *
 * Verified on the system-settings email page — a tracked POST form with plain
 * text fields, so the bar activates on a real edit.
 */
test.describe('Save button guards', () => {
  const URL = '/admin/configuration/system/system.email';

  const goto = (page) =>
    page.goto(URL, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});

  const bar = (page) => page.getByText('You have unsaved changes');
  const firstField = (page) =>
    page.locator('.unsaved-root input[type="text"], .unsaved-root textarea').first();

  test('exactly one save button, and one AJAX request on rapid double-click', async ({ adminPage }) => {
    const errors = [];
    adminPage.on('pageerror', (e) => errors.push(e.message));

    await goto(adminPage);

    const field = firstField(adminPage);
    await field.waitFor({ state: 'visible', timeout: 15000 });
    const original = await field.inputValue();

    // Clean load: bar hidden, and no lingering in-form save button.
    await expect(bar(adminPage)).toBeHidden();

    // Real edit → bar appears with its single "Save changes" button.
    await field.fill(original + 'X');
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    const saveBtn = adminPage.getByRole('button', { name: 'Save changes' });
    await expect(saveBtn).toHaveCount(1);

    // Count the actual save requests fired at the update endpoint.
    let saveRequests = 0;
    adminPage.on('request', (req) => {
      if (req.method() === 'POST' && req.url().includes('/admin/configuration/system/')) {
        saveRequests += 1;
      }
    });

    // Hammer the button before the response can settle. Dispatch clicks directly so
    // three land inside the in-flight window and exercise the JS re-entrancy guard.
    await saveBtn.dispatchEvent('click');
    await saveBtn.dispatchEvent('click').catch(() => {});
    await saveBtn.dispatchEvent('click').catch(() => {});

    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.waitForTimeout(1000);

    expect(saveRequests, 'save must fire exactly one AJAX request').toBe(1);
    expect(errors, `JS errors: ${errors.join(' | ')}`).toHaveLength(0);

    // Cleanup: restore original value.
    await goto(adminPage);
    const restore = firstField(adminPage);
    await restore.waitFor({ state: 'visible', timeout: 15000 });
    if ((await restore.inputValue()) !== original) {
      await restore.fill(original);
      await adminPage.getByRole('button', { name: 'Save changes' }).click();
      await adminPage.waitForLoadState('networkidle').catch(() => {});
    }
  });
});
