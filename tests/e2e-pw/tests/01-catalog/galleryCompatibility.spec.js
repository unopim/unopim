const { execFileSync } = require('child_process');
const path = require('path');
const { test, expect } = require('@playwright/test');

test.use({ storageState: { cookies: [], origins: [] } });

test('gallery preserves existing wildcard and explicit MIME configurations', async ({ page }) => {
  const html = execFileSync('php', [], {
    cwd: path.resolve(__dirname, '../../../..'),
    encoding: 'utf8',
    timeout: 30_000,
    input: String.raw`<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo Illuminate\Support\Facades\Blade::render(<<<'BLADE'
<div data-gallery="default">
    <x-admin::media.gallery ref="defaultGallery" />
</div>
<div data-gallery="exact">
    <x-admin::media.gallery ref="exactGallery" :accepted-types="['application/octet-stream']" />
</div>
<div data-gallery="application">
    <x-admin::media.gallery ref="applicationGallery" :accepted-types="['application/*']" :accepted-extensions="['bin']" />
</div>
<div data-gallery="custom">
    <x-admin::media.gallery ref="customGallery" :accepted-extensions="['avif']" />
</div>
<div data-gallery="legacy">
    <v-media-gallery ref="legacyGallery"></v-media-gallery>
</div>
@stack('scripts')
BLADE);
`,
  });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));

  // Mount the real reusable component outside the product form using the published admin bundle.
  await page.goto('/admin/login');
  await page.waitForFunction(() => typeof window.createAdminApp === 'function');
  await page.evaluate(markup => {
    window.app.unmount();
    const root = document.getElementById('app');
    root.innerHTML = markup;
    window.createAdminApp();

    for (const script of root.querySelectorAll('script[type="module"]')) {
      new Function(script.textContent)();
      script.remove();
    }

    window.galleryTestApp = window.app.mount('#app');
  }, html);

  for (const [configuration, fileName, mimeType, accept] of [
    ['default', 'photo.avif', 'image/avif', 'image/*,video/*'],
    ['exact', 'file.bin', 'application/octet-stream', 'application/octet-stream'],
    ['application', 'file.bin', 'application/octet-stream', '.bin'],
    ['custom', 'photo.avif', 'image/avif', '.avif'],
    ['legacy', 'photo.avif', 'image/avif', 'image/*,video/*'],
  ]) {
    await test.step(`${configuration} caller accepts its configured type`, async () => {
      const picker = page.locator(`[data-gallery="${configuration}"] input[type="file"]`).first();
      await expect(picker).toHaveAttribute('accept', accept);
      await picker.setInputFiles({ name: fileName, mimeType, buffer: Buffer.from('sample') });
      await expect.poll(() => page.evaluate(ref => {
        return window.galleryTestApp.$refs[ref].images.map(image => image.name);
      }, `${configuration}Gallery`)).toEqual([fileName]);
    });
  }

  const compatibility = await page.evaluate(() => {
    const refs = window.galleryTestApp.$refs;

    return {
      extensionRestriction: refs.applicationGallery.isFileAccepted(new File(['sample'], 'file.txt', { type: 'application/octet-stream' })),
      customRestriction: refs.customGallery.isFileAccepted(new File(['sample'], 'photo.jpg', { type: 'image/jpeg' })),
      defaultRejectsPdf: refs.defaultGallery.isFileAccepted(new File(['sample'], 'file.pdf', { type: 'application/pdf' })),
      customMimeTypes: Object.keys(refs.customGallery.mimeTypes),
      applicationMimeTypes: Object.keys(refs.applicationGallery.mimeTypes),
      legacyMimeTypes: refs.legacyGallery.mimeTypes,
    };
  });

  expect(compatibility).toEqual({
    extensionRestriction: false,
    customRestriction: false,
    defaultRejectsPdf: false,
    customMimeTypes: ['avif'],
    applicationMimeTypes: ['bin'],
    legacyMimeTypes: {},
  });
  expect(errors).toEqual([]);
});
