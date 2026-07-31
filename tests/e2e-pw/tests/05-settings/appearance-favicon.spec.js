const { test, expect } = require('../../utils/fixtures');
const { clickSaveAndExpect } = require('../../utils/helpers');
const fs = require('fs');
const path = require('path');

test.describe('Appearance — favicon', () => {
  // Appearance settings live under the Configuration > System section, not a
  // standalone /admin/settings route.
  const SETTINGS_URL = '/admin/configuration/system/system.appearance';
  const JPEG_B64 = fs.readFileSync(path.resolve(__dirname, '../../assets/check.jpeg')).toString('base64');

  const gotoSettings = (page) =>
    page.goto(SETTINGS_URL, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});

  const faviconControl = (page) => page.locator('[data-media-control]').nth(1);

  const faviconImage = (page) => faviconControl(page).locator('img[src*="/storage/configuration/"]').first();


  async function dropFavicon(page, fileName, mimeType) {
    await faviconControl(page).locator('label').first().waitFor({ state: 'visible', timeout: 15000 });

    await page.evaluate(
      ({ b64, fileName, mimeType }) => {
        const bin = atob(b64);
        const arr = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) {
          arr[i] = bin.charCodeAt(i);
        }
        const file = new File([arr], fileName, { type: mimeType });
        const dt = new DataTransfer();
        dt.items.add(file);

        const control = document.querySelectorAll('[data-media-control]')[1];
        const target = control.querySelector('label') || control;
        target.dispatchEvent(new DragEvent('dragover', { bubbles: true, cancelable: true, dataTransfer: dt }));
        target.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer: dt }));
      },
      { b64: JPEG_B64, fileName, mimeType },
    );
  }

  async function deleteFaviconIfPresent(page) {
    const tile = faviconImage(page);
    if ((await tile.count()) === 0) {
      return;
    }

    await tile.hover();
    await faviconControl(page).locator('.icon-delete').first().click();

    await clickSaveAndExpect(page, 'Save changes', /Appearance updated successfully/i, /system-settings/);
    await gotoSettings(page);
  }

  test.afterEach(async ({ adminPage }) => {
    await gotoSettings(adminPage);
    await deleteFaviconIfPresent(adminPage);
  });

  test('the favicon control advertises the extensions the server accepts', async ({ adminPage }) => {
    await gotoSettings(adminPage);

    const advertised = await adminPage.evaluate(() =>
      [...document.querySelectorAll('input[type="file"]')].map((input) => input.accept),
    );

    expect(advertised.some((accept) => /jpeg/.test(accept || ''))).toBe(true);
  });

  test('a jpeg favicon uploads and reflects on the next load', async ({ adminPage }) => {
    await gotoSettings(adminPage);
    await deleteFaviconIfPresent(adminPage);

    await dropFavicon(adminPage, 'watch.jpeg', 'image/jpeg');

    await expect(faviconControl(adminPage).locator('img[src^="data:"]').first()).toBeVisible({ timeout: 10000 });

    await expect
      .poll(async () =>
        adminPage.evaluate(() => {
          const control = document.querySelectorAll('[data-media-control]')[1];

          return [...control.querySelectorAll('input[type="file"]')].some((i) => i.files && i.files.length > 0);
        }),
      )
      .toBe(true);

    await clickSaveAndExpect(adminPage, 'Save changes', /Appearance updated successfully/i, /system-settings/);

    await gotoSettings(adminPage);

    const favicon = faviconImage(adminPage);
    await expect(favicon).toBeVisible({ timeout: 15000 });

    await expect
      .poll(async () => favicon.evaluate((img) => img.complete && img.naturalWidth), { timeout: 15000 })
      .toBeGreaterThan(0);
  });

  test('an unsupported favicon type is refused with a visible warning', async ({ adminPage }) => {
    await gotoSettings(adminPage);
    await deleteFaviconIfPresent(adminPage);

    await dropFavicon(adminPage, 'watch.svg', 'image/svg+xml');

    await expect(adminPage.locator('#app').getByText(/are allowed/i).first()).toBeVisible({ timeout: 10000 });
  });
});
