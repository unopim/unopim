const { test, expect } = require('../../utils/fixtures');
const { clickSaveAndExpect } = require('../../utils/helpers');
const fs = require('fs');
const path = require('path');

/**
 * Appearance settings — logo upload / delete via drag-and-drop.
 *
 * Regression guard for the stale-cache bug: getConfigData() reads through the
 * cached CoreConfigRepository, but AppearanceController writes with raw Eloquent.
 * Without an explicit cache invalidation the uploaded logo kept serving the old
 * (or deleted) path, so uploads did not reflect and deletes never fell back to
 * the default UnoPim logo. These tests drive the real UI and assert the change
 * is visible on the very next page load — no manual cache:clear.
 *
 * Upload is performed by dropping a file on the "Add Image" tile so the test is
 * independent of whether MagicAI is enabled (which turns the click into a modal),
 * and it doubles as coverage for the drag-and-drop feature.
 */
test.describe('Appearance — admin logo', () => {
  const SETTINGS_URL = '/admin/settings/appearance';
  const FIXTURE_B64 = fs.readFileSync(path.resolve(__dirname, '../../assets/dotted.png')).toString('base64');

  const gotoSettings = (page) =>
    page.goto(SETTINGS_URL, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});

  const logoImage = (page) => page.locator('img[src*="/storage/configuration/"]').first();

  /** Drop a file onto the first "Add Image" tile and wait for the preview to render. */
  async function dropLogo(page) {
    await expect(page.getByText('Add Image').first()).toBeVisible({ timeout: 15000 });

    await page.evaluate((b64) => {
      const bin = atob(b64);
      const arr = new Uint8Array(bin.length);
      for (let i = 0; i < bin.length; i++) {
        arr[i] = bin.charCodeAt(i);
      }
      const file = new File([arr], 'logo.png', { type: 'image/png' });
      const dt = new DataTransfer();
      dt.items.add(file);
      const tile = [...document.querySelectorAll('label')].find((l) => /Add Image/.test(l.textContent));
      tile.dispatchEvent(new DragEvent('dragover', { bubbles: true, cancelable: true, dataTransfer: dt }));
      tile.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer: dt }));
    }, FIXTURE_B64);

    // Preview tile (data URL) confirms the dropped file was accepted.
    await expect(page.locator('img[src^="data:"]').first()).toBeVisible({ timeout: 10000 });

    // The item mounts and copies the File into its hidden multipart input; wait for
    // that to complete so the submit below actually carries the file (avoids a race).
    await expect
      .poll(async () =>
        page.evaluate(() =>
          [...document.querySelectorAll('input[type="file"]')].some((i) => i.files && i.files.length > 0),
        ),
      )
      .toBe(true);
  }

  /** Remove the currently uploaded logo via the tile's hover delete control, then save. */
  async function deleteLogoIfPresent(page) {
    const tile = logoImage(page);
    if (await tile.count() === 0) {
      return;
    }

    await tile.hover();
    await page.locator('.icon-delete').first().click();

    await clickSaveAndExpect(page, 'Save changes', /Appearance updated successfully/i, /system-settings/);
    await gotoSettings(page);
  }

  async function uploadLogoAndSave(page) {
    await dropLogo(page);
    await clickSaveAndExpect(page, 'Save changes', /Appearance updated successfully/i, /system-settings/);
  }

  test.afterEach(async ({ adminPage }) => {
    // Leave the instance on the default logo regardless of assertion outcome.
    await gotoSettings(adminPage);
    await deleteLogoIfPresent(adminPage);
  });

  test('dropped logo reflects on the next load without clearing cache', async ({ adminPage }) => {
    await gotoSettings(adminPage);
    await deleteLogoIfPresent(adminPage);

    await uploadLogoAndSave(adminPage);

    // Fresh navigation — proves the cached config read was invalidated on save.
    await gotoSettings(adminPage);

    const logo = logoImage(adminPage);
    await expect(logo).toBeVisible({ timeout: 15000 });

    // The served file must actually decode (naturalWidth > 0 == real 200, not a broken/cached path).
    await expect
      .poll(async () => logo.evaluate((img) => img.complete && img.naturalWidth), { timeout: 15000 })
      .toBeGreaterThan(0);
  });

  test('deleting the logo falls back to the default and restores the upload tile', async ({ adminPage }) => {
    await gotoSettings(adminPage);
    await deleteLogoIfPresent(adminPage);

    // Arrange: upload a logo so there is something to delete.
    await uploadLogoAndSave(adminPage);
    await gotoSettings(adminPage);
    await expect(logoImage(adminPage)).toBeVisible({ timeout: 15000 });

    // Act: delete it.
    await deleteLogoIfPresent(adminPage);

    // Assert: no custom logo left (fell back to default) and the browse tile is back.
    await expect(logoImage(adminPage)).toHaveCount(0);
    await expect(adminPage.getByText('Add Image').first()).toBeVisible({ timeout: 15000 });
  });
});
